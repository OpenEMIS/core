<?php
use Migrations\AbstractMigration;
use Cake\ORM\TableRegistry;

class POCOR6799 extends AbstractMigration
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

        // Create Views
        $this->execute("CREATE OR REPLACE VIEW institution_staff_attendances_archive AS SELECT *    
        FROM institution_staff_attendances LIMIT 0");

        $this->execute("CREATE OR REPLACE VIEW institution_staff_leave_archived AS SELECT *    
        FROM institution_staff_leave LIMIT 0");

        $this->execute("CREATE OR REPLACE VIEW assessment_item_results_archived AS SELECT *    
        FROM assessment_item_results LIMIT 0");

        $this->execute("CREATE OR REPLACE VIEW institution_student_absences_archived AS SELECT *    
        FROM institution_student_absences LIMIT 0");

        $this->execute("CREATE OR REPLACE VIEW student_attendance_marked_records_archived AS SELECT *    
        FROM student_attendance_marked_records LIMIT 0");

        $this->execute("CREATE OR REPLACE VIEW student_attendance_mark_types_archived AS SELECT *    
        FROM student_attendance_mark_types LIMIT 0");

        $this->execute("CREATE OR REPLACE VIEW institution_student_absence_details_archived AS SELECT *    
        FROM institution_student_absence_details LIMIT 0");
    }

    // rollback
    public function down()
    {
        $this->execute("DROP VIEW IF EXISTS institution_staff_attendances_archive");
        $this->execute("DROP VIEW IF EXISTS institution_staff_leave_archived");
        $this->execute("DROP VIEW IF EXISTS assessment_item_results_archived");
        $this->execute("DROP VIEW IF EXISTS institution_student_absences_archived");
        $this->execute("DROP VIEW IF EXISTS student_attendance_marked_records_archived");
        $this->execute("DROP VIEW IF EXISTS student_attendance_mark_types_archived");
        $this->execute("DROP VIEW IF EXISTS institution_student_absence_details_archived");
    }
}
