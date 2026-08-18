<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Log;

class POCOR9754 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up(): void
    {
        // 1. backup report_queries tables
        $this->execute("CREATE TABLE zz_9754_report_queries LIKE report_queries");
        $this->execute("INSERT INTO zz_9754_report_queries SELECT * FROM report_queries");
        // 2. backup summary_institution_student_absences tables
        $this->execute("CREATE TABLE zz_9754_summary_institution_student_absences LIKE summary_institution_student_absences");
        $this->execute("INSERT INTO zz_9754_summary_institution_student_absences SELECT * FROM summary_institution_student_absences");
      
        // Check if matching report queries exist
        $result = $this->fetchRow("
            SELECT COUNT(*) AS total
            FROM report_queries
            WHERE name LIKE '%summary_institution_student_absences%'
        ");

        if (!empty($result) && $result['total'] > 0) {
            $this->execute("
                UPDATE report_queries
                SET status = 0
                WHERE name LIKE '%summary_institution_student_absences%'
            ");
        }

        // Drop table if exists
        if ($this->hasTable('summary_institution_student_absences')) {
            $this->table('summary_institution_student_absences')
                ->drop()
                ->save();
        }
    }

    /**
     * Reverse order of {{up()}}: revoke grants first, then drop nav rows,
     * then drop the runtime tables. Backup tables are intentionally NOT
     * restored — surgical deletes keep any concurrently-added rows safe.
     */
    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS zz_9754_report_queries");
        $this->execute("RENAME TABLE zz_9754_report_queries TO report_queries");
        $this->execute("DROP TABLE IF EXISTS zz_9754_summary_institution_student_absences");
        $this->execute("RENAME TABLE zz_9754_summary_institution_student_absences TO summary_institution_student_absences");
    }
}
