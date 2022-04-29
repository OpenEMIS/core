<?php
use Migrations\AbstractMigration;

class POCOR6702 extends AbstractMigration
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

        
        $this->execute('CREATE TABLE `zz_report_student_assessment_summary` LIKE `report_student_assessment_summary`');
        $this->execute('INSERT INTO `zz_report_student_assessment_summary` SELECT * FROM `report_student_assessment_summary`');
        $this->execute('CREATE TABLE `zz_report_queries` LIKE `report_queries`');
        $this->execute('INSERT INTO `zz_report_queries` SELECT * FROM `report_queries`');

        /** Delete OpenEMIS Core report_assessment_missing_mark_entry table */
        $this->execute('DROP TABLE IF EXISTS `report_student_assessment_summary`');

        /** Create OpenEMIS Core report_student_assessment_summary table */
        $this->execute('
        CREATE TABLE `report_student_assessment_summary` (
            `academic_period_id` int(10) DEFAULT NULL,
            `academic_period_code` varchar(100) DEFAULT NULL,
            `academic_period_name` varchar(100) DEFAULT NULL,
            `area_id` int(10) DEFAULT NULL,
            `area_code` varchar(100) DEFAULT NULL,
            `area_name` varchar(100) DEFAULT NULL,
            `institution_id` int(10) DEFAULT NULL,
            `institution_code` varchar(100) DEFAULT NULL,
            `institution_name` varchar(100) DEFAULT NULL,
            `grade_id` int(10) DEFAULT NULL,
            `grade_code` varchar(100) DEFAULT NULL,
            `grade_name` varchar(100) DEFAULT NULL,
            `institution_classes_id` int(11) NOT NULL,
            `institution_classes_name` varchar(250) NOT NULL,
            `homeroom_teacher_id` int(11) NOT NULL,
            `homeroom_teacher_name` varchar(250) NOT NULL,
            `subject_id` int(10) DEFAULT NULL,
            `subject_code` varchar(100) DEFAULT NULL,
            `subject_name` varchar(100) DEFAULT NULL,
            `subject_weight` decimal(6,2) DEFAULT 0.00,
            `assessment_id` int(10) DEFAULT NULL,
            `assessment_code` varchar(100) DEFAULT NULL,
            `assessment_name` varchar(100) DEFAULT NULL,
            `period_id` int(10) DEFAULT NULL,
            `period_code` varchar(100) DEFAULT NULL,
            `period_name` varchar(100) DEFAULT NULL,
            `academic_term` varchar(100) DEFAULT NULL,
            `period_weight` decimal(6,2) DEFAULT 0.00,
            `student_id` int(11) NOT NULL,
            `student_name` varchar(250) NOT NULL,
            `latest_mark` float NOT NULL,
            `total_mark` float NULL,
            `average_mark` float NULL,
            `institution_average_mark` float NULL,
            `area_average_mark` float NULL,
            `created` datetime NOT NULL
        ENGINE=InnoDB DEFAULT CHARSET=utf8;
      ');
    }
    //rollback
    public function down()
    {
        /** Delete OpenEMIS Core report_assessment_missing_mark_entry table */
        $this->execute('DROP TABLE IF EXISTS `report_student_assessment_summary`');
        
        /** Delete OpenEMIS Core report_assessment_missing_mark_entry row in report_queries table */
        $this->execute('DELETE FROM report_queries WHERE report_queries.name = "report_student_assessment_summary_truncate"'); 
        $this->execute('DELETE FROM report_queries WHERE report_queries.name = "report_student_assessment_summary_insert"'); 
    }
}