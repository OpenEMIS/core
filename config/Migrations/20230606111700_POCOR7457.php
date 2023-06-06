<?php
use Migrations\AbstractMigration;

class POCOR7457 extends AbstractMigration
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

        // DROP existing summary tables
        $this->execute('DROP TABLE summary_programme_sector_qualification_genders;');    

        // CREATE new table 
        $this->execute('CREATE TABLE `summary_programme_sector_qualification_genders`( `academic_period_id` int NOT NULL, `academic_period_name` varchar(200) NOT NULL, `institution_sector_id` int NOT NULL, `institution_sector_name` varchar(200) NOT NULL, `education_system_id` int NOT NULL, `education_system_name` varchar(200) NOT NULL, `education_level_isced_id` int NOT NULL, `education_level_isced_name` varchar(200) NOT NULL, `education_level_isced_level` int NOT NULL, `education_level_id` int NOT NULL, `education_level_name` varchar(200) NOT NULL, `education_cycle_id` int NOT NULL, `education_cycle_name` varchar(200) NOT NULL, `education_programme_id` int NOT NULL, `education_programme_code` varchar(200) NOT NULL, `education_programme_name` varchar(200) NOT NULL, `staff_gender_id` int NOT NULL, `staff_gender_name` varchar(200) NOT NULL, `staff_qualification_title_id` int NOT NULL, `staff_qualification_title_name` varchar(200) NOT NULL, `total_staff_teaching` int NOT NULL, `total_staff_teaching_newly_recruited` int NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;');

    }

    //rollback
    public function down()
    {

    }
}
