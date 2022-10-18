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

        // Backup table
        $this->execute('CREATE TABLE `zz_6799_institution_staff_attendances` LIKE `institution_staff_attendances`');
        $this->execute('INSERT INTO `zz_6799_institution_staff_attendances` SELECT * FROM `institution_staff_attendances`');

        $this->execute('CREATE TABLE `zz_6799_institution_staff_leave` LIKE `institution_staff_leave`');
        $this->execute('INSERT INTO `zz_6799_institution_staff_leave` SELECT * FROM `institution_staff_leave`');

        $this->execute('CREATE TABLE `zz_6799_assessment_item_results` LIKE `assessment_item_results`');
        $this->execute('INSERT INTO `zz_6799_assessment_item_results` SELECT * FROM `assessment_item_results`');

        $this->execute('CREATE TABLE `zz_6799_institution_student_absences` LIKE `institution_student_absences`');
        $this->execute('INSERT INTO `zz_6799_institution_student_absences` SELECT * FROM `institution_student_absences`');

        $this->execute('CREATE TABLE `zz_6799_student_attendance_marked_records` LIKE `student_attendance_marked_records`');
        $this->execute('INSERT INTO `zz_6799_student_attendance_marked_records` SELECT * FROM `student_attendance_marked_records`');

        $this->execute('CREATE TABLE `zz_6799_student_attendance_mark_types` LIKE `student_attendance_mark_types`');
        $this->execute('INSERT INTO `zz_6799_student_attendance_mark_types` SELECT * FROM `student_attendance_mark_types`');

        $this->execute('CREATE TABLE `zz_6799_institution_student_absence_details` LIKE `institution_student_absence_details`');
        $this->execute('INSERT INTO `zz_6799_institution_student_absence_details` SELECT * FROM `institution_student_absence_details`');
        // End

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
        $this->execute('DROP TABLE IF EXISTS `institution_staff_attendances`');
        $this->execute('RENAME TABLE `zz_R6799_institution_staff_attendances` TO `institution_staff_attendances`');

        $this->execute('DROP TABLE IF EXISTS `institution_staff_leave`');
        $this->execute('RENAME TABLE `zz_R6799_institution_staff_leave` TO `institution_staff_leave`');

        $this->execute('DROP TABLE IF EXISTS `assessment_item_results`');
        $this->execute('RENAME TABLE `zz_R6799_assessment_item_results` TO `assessment_item_results`');

        $this->execute('DROP TABLE IF EXISTS `institution_student_absences`');
        $this->execute('RENAME TABLE `zz_R6799_institution_student_absences` TO `institution_student_absences`');

        $this->execute('DROP TABLE IF EXISTS `student_attendance_marked_records`');
        $this->execute('RENAME TABLE `zz_R6799_student_attendance_marked_records` TO `student_attendance_marked_records`');

        $this->execute('DROP TABLE IF EXISTS `student_attendance_mark_types`');
        $this->execute('RENAME TABLE `zz_R6799_student_attendance_mark_types` TO `student_attendance_mark_types`');

        $this->execute('DROP TABLE IF EXISTS `institution_student_absence_details`');
        $this->execute('RENAME TABLE `zz_R6799_institution_student_absence_details` TO `institution_student_absence_details`');
    }
}
