<?php
use Migrations\AbstractMigration;

class POCOR9711 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_9711_report_queries` LIKE `report_queries`');
        $this->execute('INSERT INTO `z_9711_report_queries` SELECT * FROM `report_queries`');
        
        //UPDATE data in report_queries table for cron 
        $this->execute("UPDATE report_queries SET query_sql = 'DELETE FROM security_user_codes WHERE created < DATE_SUB(NOW(), INTERVAL 1 HOUR);' WHERE name = 'clear_otp';"); 
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `report_queries`');
        $this->execute('RENAME TABLE `z_9711_report_queries` TO `report_queries`');
    }
}
