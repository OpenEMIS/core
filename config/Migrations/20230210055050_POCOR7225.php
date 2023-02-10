<?php
use Migrations\AbstractMigration;

class POCOR7225 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_7225_survey_status_periods` LIKE `survey_status_periods`');
        $this->execute('INSERT INTO `z_7225_survey_status_periods` SELECT * FROM `survey_status_periods`');
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->execute("ALTER TABLE survey_status_periods DROP FOREIGN KEY `surve_statu_perio_fk_surve_statu_id`");      
        $this->execute("ALTER TABLE survey_status_periods ADD CONSTRAINT `surve_statu_perio_fk_surve_statu_id` FOREIGN KEY (`survey_status_id`) REFERENCES survey_statuses(`id`)");
        $this->execute('SET SESSION FOREIGN_KEY_CHECKS=1;');
    
    }
         
    // rollback
    public function down()
    {
        // Drop summary tables
        $this->execute('DROP TABLE IF EXISTS `survey_status_periods`');   
    }
}
