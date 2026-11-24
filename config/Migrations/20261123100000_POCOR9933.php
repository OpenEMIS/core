<?php
use Migrations\AbstractMigration;

/**
 * POCOR-9933: `update_existing_marks` is a mandatory field going forward (POCOR-9759), but
 * assessment periods saved before that field existed can still have it NULL. Functionally
 * this behaves identically to "No" in AssessmentPeriodsTable::beforeSave() - but the edit
 * form renders a required, unselected dropdown for these rows, forcing an unrelated Yes/No
 * decision the next time anyone edits that period for any reason.
 *
 * Scoped to non-past academic periods only, matching AssessmentPeriodsTable::isPastAcademicPeriod()
 * exactly (a period's own academic period end_date >= the currently-flagged academic period's
 * start_date) - weight changes are already blocked outright for past periods, so
 * update_existing_marks has no practical effect there and that historical data is left alone.
 */
class POCOR9933 extends AbstractMigration
{
    const JOINS = "
        INNER JOIN assessments a ON a.id = ap2.assessment_id
        INNER JOIN academic_periods acp ON acp.id = a.academic_period_id
    ";

    const SCOPE_WHERE = "
        WHERE ap2.update_existing_marks IS NULL
          AND acp.end_date >= (
              SELECT start_date FROM academic_periods
              WHERE editable = 1 AND visible > 0 AND `current` = 1 AND parent_id > 0
              ORDER BY start_date DESC LIMIT 1
          )
    ";

    public function up()
    {
        $this->execute('CREATE TABLE IF NOT EXISTS zz_9933_assessment_periods LIKE assessment_periods');
        $this->execute('INSERT INTO zz_9933_assessment_periods
            SELECT ap2.* FROM assessment_periods ap2 ' . self::JOINS . self::SCOPE_WHERE);

        $this->execute('UPDATE assessment_periods ap2 ' . self::JOINS . '
            SET ap2.update_existing_marks = 0 ' . self::SCOPE_WHERE);
    }

    public function down()
    {
        $this->execute(
            'UPDATE assessment_periods ap
             INNER JOIN zz_9933_assessment_periods bak ON bak.id = ap.id
             SET ap.update_existing_marks = bak.update_existing_marks'
        );
        $this->execute('DROP TABLE IF EXISTS zz_9933_assessment_periods');
    }
}
