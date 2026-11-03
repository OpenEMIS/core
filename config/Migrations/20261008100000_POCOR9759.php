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
    public function up()
    {
        // Backup tables
        $this->execute('CREATE TABLE IF NOT EXISTS `zz_9759_assessment_periods` LIKE `assessment_periods`');
        $this->execute('INSERT INTO `zz_9759_assessment_periods` SELECT * FROM `assessment_periods`');
        $this->execute('CREATE TABLE IF NOT EXISTS `zz_9759_assessment_item_results` LIKE `assessment_item_results`');
        $this->execute('INSERT INTO `zz_9759_assessment_item_results` SELECT * FROM `assessment_item_results`');

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
        $resultsTable = $this->table('assessment_item_results');
        if (!$resultsTable->hasColumn('applied_weight')) {
            // Same type as assessment_periods.weight (decimal(6,5)) so no precision is lost.
            $resultsTable->addColumn('applied_weight', 'decimal', [
                'precision' => 6,
                'scale' => 5,
                'default' => null,
                'null' => true,
                'after' => 'marks',
            ])->update();
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

        $this->execute('DROP TABLE IF EXISTS `assessment_item_results`');
        $this->execute('RENAME TABLE `zz_9759_assessment_item_results` TO `assessment_item_results`');

        // Remove backup table
        $this->execute('DROP TABLE IF EXISTS `zz_9759_assessment_periods`');
    }
}
