<?php
use Migrations\AbstractMigration;

class POCOR5789 extends AbstractMigration
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
        // Backup locale_contents table
        $this->execute('CREATE TABLE `z_5789_institution_student_absences` LIKE `institution_student_absences`');
		$this->execute('INSERT INTO `z_5789_institution_student_absences` SELECT * FROM `institution_student_absences`');
		
		$this->execute('CREATE TABLE `z_5789_institution_student_absence_details` LIKE `institution_student_absence_details`');
		$this->execute('INSERT INTO `z_5789_institution_student_absence_details` SELECT * FROM `institution_student_absence_details`');
		
		$this->execute('CREATE TABLE `z_5789_student_attendance_marked_records` LIKE `student_attendance_marked_records`');
		$this->execute('INSERT INTO `z_5789_student_attendance_marked_records` SELECT * FROM `student_attendance_marked_records`');
		
		$this->execute('ALTER TABLE `institution_student_absences` ADD `education_grade_id` INT NOT NULL AFTER `institution_class_id`');
		
		$this->execute('ALTER TABLE `institution_student_absence_details` ADD `education_grade_id` INT NOT NULL AFTER `institution_class_id`');
		
		$this->execute('ALTER TABLE `student_attendance_marked_records` ADD `education_grade_id` INT NOT NULL AFTER `institution_class_id`');
		
		$this->execute("ALTER TABLE `institution_student_absences` CHANGE `education_grade_id` `education_grade_id` INT(11) NOT NULL DEFAULT '0'");
		$this->execute("ALTER TABLE `institution_student_absence_details` CHANGE `education_grade_id` `education_grade_id` INT(11) NOT NULL DEFAULT '0'");
		$this->execute("ALTER TABLE `student_attendance_marked_records` CHANGE `education_grade_id` `education_grade_id` INT(11) NOT NULL DEFAULT '0'");
		
		$this->execute('UPDATE `student_attendance_marked_records`
		LEFT JOIN `institution_class_grades` ON `institution_class_grades`.institution_class_id = `student_attendance_marked_records`.institution_class_id
		SET student_attendance_marked_records.education_grade_id = institution_class_grades.education_grade_id WHERE institution_class_grades.education_grade_id IS NOT NULL');
		
		$this->execute('UPDATE `institution_student_absences`
		LEFT JOIN `institution_class_grades` ON `institution_class_grades`.institution_class_id = `institution_student_absences`.institution_class_id
		SET institution_student_absences.education_grade_id = institution_class_grades.education_grade_id WHERE institution_class_grades.education_grade_id IS NOT NULL');
		
		$this->execute('UPDATE `institution_student_absence_details`
		LEFT JOIN `institution_class_grades` ON `institution_class_grades`.institution_class_id = `institution_student_absence_details`.institution_class_id
		SET institution_student_absence_details.education_grade_id = institution_class_grades.education_grade_id WHERE institution_class_grades.education_grade_id IS NOT NULL');
		
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_student_absences`');
        $this->execute('RENAME TABLE `z_5789_institution_student_absences` TO `institution_student_absences`');
		
		$this->execute('DROP TABLE IF EXISTS `institution_student_absence_details`');
        $this->execute('RENAME TABLE `z_5789_institution_student_absence_details` TO `institution_student_absence_details`');
		
		$this->execute('DROP TABLE IF EXISTS `student_attendance_marked_records`');
        $this->execute('RENAME TABLE `z_5789_student_attendance_marked_records` TO `student_attendance_marked_records`');
    }
}
