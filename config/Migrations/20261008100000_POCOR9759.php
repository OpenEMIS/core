<?php
use Migrations\AbstractMigration;

/**
 * POCOR-9759: "Update Existing Marks" on an Assessment Period, and the weight
 * snapshot that makes it actually work (Option B - a weight change with "No"
 * selected must not affect any already-calculated mark anywhere in the system;
 * "Yes" explicitly re-snapshots and recalculates).
 *
 * `assessment_item_results` is written from more places than any single
 * application-level hook could reliably cover (two separate CakePHP Table
 * classes mapped to this table, a raw-SQL duplicate-row branch inside the
 * existing save hook, and a completely separate Laravel REST API that writes
 * to this table directly). A DB trigger is the only mechanism that guarantees
 * every write is covered regardless of which application/table class performs
 * it - this repo already has precedent for this pattern (see POCOR7940,
 * POCOR8974).
 */
class POCOR9759 extends AbstractMigration
{
    const RESULTS_TABLE = 'assessment_item_results';
    const ARCHIVED_RESULTS_TABLE = 'assessment_item_results_archived';

    public function up()
    {
        // POCOR-9759: records whether changing `weight` should recalculate/overwrite
        // existing students' marks for this assessment period. Nullable with no
        // default so the field starts unselected ("- Select -") on both Add and Edit,
        // per the agreed requirement that it's mandatory with no implicit default.
        $periodsTable = $this->table('assessment_periods');
        if (!$periodsTable->hasColumn('update_existing_marks')) {
            $periodsTable->addColumn('update_existing_marks', 'integer', [
                'default' => null,
                'null' => true,
                'after' => 'weight',
            ])->update();
        }

        // POCOR-9759: freezes the assessment period weight used in every weighted-mark
        // calculation (Total Marks, PDF/Excel reports, Report Cards, GPA) at the moment
        // each mark is actually saved, instead of those calculations live-joining to
        // assessment_periods.weight and silently changing every time the period's
        // weight is edited - regardless of "Update Existing Marks".
        $this->addAppliedWeightColumn(self::RESULTS_TABLE);

        // Code review fix: `assessment_item_results_archived` is a copy of this table's
        // structure that ArchiveService::archive() (src/Service/ArchiveService.php:84)
        // populates with `INSERT IGNORE INTO $archived SELECT * FROM $live` - a
        // position-based column match. Without `applied_weight` added here too, in the
        // same position (`after marks`), archiving breaks on any install that already
        // has this table ("Column count doesn't match"), and reading archived marks via
        // getLastMark(['archive' => true]) breaks too, since buildSelectFields() always
        // selects `all_results.applied_weight` ("Unknown column"). Only touch it if the
        // archived table actually exists - plenty of installs haven't archived anything
        // yet and don't have it.
        if ($this->hasTable(self::ARCHIVED_RESULTS_TABLE)) {
            $this->addAppliedWeightColumn(self::ARCHIVED_RESULTS_TABLE);
        }

        // Backfill: every existing mark's frozen weight starts out equal to whatever
        // is live today, so no currently-displayed total changes at deploy time.
        $this->execute('
            UPDATE `assessment_item_results` air
            INNER JOIN `assessment_periods` ap ON ap.id = air.assessment_period_id
            SET air.applied_weight = ap.weight
            WHERE air.applied_weight IS NULL
        ');

        $this->execute('DROP TRIGGER IF EXISTS trigger_assessment_item_results_weight_insert');
        $this->execute('DROP TRIGGER IF EXISTS trigger_assessment_item_results_weight_update');

        // Every new mark - from the CakePHP entry grid, the import flow, or the
        // separate Laravel API - gets the period's current weight snapshotted at
        // insert time.
        $this->execute('
            CREATE TRIGGER trigger_assessment_item_results_weight_insert
            BEFORE INSERT ON assessment_item_results
            FOR EACH ROW
            BEGIN
                SET NEW.applied_weight = (
                    SELECT weight FROM assessment_periods WHERE id = NEW.assessment_period_id
                );
            END
        ');

        // Editing a mark is treated as a fresh calculation moment and re-snapshots
        // the current weight. The `NOT (NEW.marks <=> OLD.marks)` guard (null-safe,
        // so a mark going to/from NULL is still detected) means this does NOT fire
        // - and so does not fight - the explicit bulk applied_weight sync that
        // AssessmentPeriodsTable::afterSave() performs when "Update Existing Marks"
        // is Yes, since that update only touches applied_weight, never marks.
        $this->execute('
            CREATE TRIGGER trigger_assessment_item_results_weight_update
            BEFORE UPDATE ON assessment_item_results
            FOR EACH ROW
            BEGIN
                IF NOT (NEW.marks <=> OLD.marks) THEN
                    SET NEW.applied_weight = (
                        SELECT weight FROM assessment_periods WHERE id = NEW.assessment_period_id
                    );
                END IF;
            END
        ');
    }

    public function down()
    {
        $this->execute('DROP TRIGGER IF EXISTS trigger_assessment_item_results_weight_insert');
        $this->execute('DROP TRIGGER IF EXISTS trigger_assessment_item_results_weight_update');

        $periodsTable = $this->table('assessment_periods');
        if ($periodsTable->hasColumn('update_existing_marks')) {
            $periodsTable->removeColumn('update_existing_marks')->update();
        }

        // Code review fix: this used to DROP `assessment_item_results` and RENAME the
        // deploy-time backup over it, which discards every mark entered or changed
        // between deploy and rollback. `applied_weight` is an additive, nullable
        // column - a plain removeColumn() undoes the schema change without touching
        // any other column's data, so there's no need for a table-swap or a backup at
        // all (the backup tables and their population have been removed from up()).
        $resultsTable = $this->table(self::RESULTS_TABLE);
        if ($resultsTable->hasColumn('applied_weight')) {
            $resultsTable->removeColumn('applied_weight')->update();
        }

        if ($this->hasTable(self::ARCHIVED_RESULTS_TABLE)) {
            $archivedTable = $this->table(self::ARCHIVED_RESULTS_TABLE);
            if ($archivedTable->hasColumn('applied_weight')) {
                $archivedTable->removeColumn('applied_weight')->update();
            }
        }
    }

    private function addAppliedWeightColumn($tableName)
    {
        $table = $this->table($tableName);
        if (!$table->hasColumn('applied_weight')) {
            // Same type as assessment_periods.weight (decimal(6,5)) so no precision is lost.
            $table->addColumn('applied_weight', 'decimal', [
                'precision' => 6,
                'scale' => 5,
                'default' => null,
                'null' => true,
                'after' => 'marks',
            ])->update();
        }
    }
}
