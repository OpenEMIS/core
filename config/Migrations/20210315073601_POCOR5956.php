<?php
use Migrations\AbstractMigration;

class POCOR5956 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        // Backup student_attendance_per_day_periods table
        $this->execute('CREATE TABLE `z_5956_student_attendance_per_day_periods` LIKE `student_attendance_per_day_periods`');
        $this->execute('INSERT INTO `z_5956_student_attendance_per_day_periods` SELECT * FROM `student_attendance_per_day_periods`');
        // End

        $table = $this->table('student_attendance_per_day_periods');
        $table->removeColumn('education_grade_id')
              ->removeColumn('academic_period_id')
              ->save();
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `student_attendance_per_day_periods`');
        $this->execute('RENAME TABLE `z_5956_student_attendance_per_day_periods` TO `student_attendance_per_day_periods`');
    }
}
