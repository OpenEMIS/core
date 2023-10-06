<?php
use Migrations\AbstractMigration;

class POCOR7086 extends AbstractMigration
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
        // Backup table not needed
        $this->execute('DROP VIEW IF EXISTS institution_class_attendance_records_archived');
        $this->execute('DROP VIEW IF EXISTS institution_student_absences_archived');
        $this->execute('DROP VIEW IF EXISTS institution_student_absence_details_archived');
        $this->execute('DROP VIEW IF EXISTS student_attendance_marked_records_archived');
        $this->execute('DROP VIEW IF EXISTS student_attendance_mark_types_archived');
        $this->execute('DROP VIEW IF EXISTS institution_staff_attendances_archived');
        $this->execute('DROP VIEW IF EXISTS institution_staff_attendances_archive');
        $this->execute('DROP VIEW IF EXISTS institution_staff_leave_archived');
        $this->execute('DROP VIEW IF EXISTS assessment_item_results_archived');
    
    }
}
