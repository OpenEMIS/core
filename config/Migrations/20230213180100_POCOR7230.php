<?php
use Migrations\AbstractMigration;

class POCOR7230 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_7230_report_queries` LIKE `report_queries`');
        $this->execute('INSERT INTO `zz_7230_report_queries` SELECT * FROM `report_queries`');

        // DROP existing summary tables
        $this->execute('DROP summary_institution_nationalities;');
        $this->execute('DROP summary_institution_grade_nationalities;');

        // ADD new CREATE statements 
        $this->execute('INSERT INTO `report_queries` (`name`, `query_sql`, `frequency`, `status`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ("summary_institution_nationalities_create", "CREATE TABLE IF NOT EXISTS `summary_institution_nationalities`( `academic_period_id` int(11) NOT NULL, `academic_period_name` varchar(50) NOT NULL, `institution_id` int(11) NOT NULL, `institution_code` varchar(50) NOT NULL, `nationality_id` int(11) NULL, `nationality_name` varchar(50) NULL, `total_students` int(9) NOT NULL, `total_students_female` int(9) NOT NULL, `total_students_male` int(9) NOT NULL) ENGINE = InnoDB DEFAULT CHARSET = utf8;", "week", 1, NULL, NULL, 1, CURRENT_TIMESTAMP)');
        $this->execute('INSERT INTO `report_queries` (`name`, `query_sql`, `frequency`, `status`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ("summary_institution_grade_nationalities_create", "CREATE TABLE IF NOT EXISTS `summary_institution_grade_nationalities`( `academic_period_id` int(11) NOT NULL, `academic_period_name` varchar(50) NOT NULL, `institution_id` int(11) NOT NULL, `institution_code` varchar(50) NOT NULL, `grade_id` int(11) NOT NULL, `grade_name` varchar(50) NOT NULL, `nationality_id` int(11) NULL, `nationality_name` varchar(50) NULL, `total_students` int(9) NOT NULL, `total_students_female` int(9) NOT NULL, `total_students_male` int(9) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8;", "week", 1, NULL, NULL, 1, CURRENT_TIMESTAMP)');

    }
         
    // rollback
    public function down()
    {
        
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `report_queries`');
        $this->execute('RENAME TABLE `zz_7230_report_queries` TO `report_queries`');

        // Drop summary tables
        $this->execute('DROP TABLE IF EXISTS `summary_institution_nationalities`');
        $this->execute('DROP TABLE IF EXISTS `summary_institution_grade_nationalities`');

    }
}
?>