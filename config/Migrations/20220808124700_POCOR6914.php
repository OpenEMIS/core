<?php
use Migrations\AbstractMigration;

class POCOR6914 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_6914_report_queries` LIKE `report_queries`');
        $this->execute('INSERT INTO `zz_6914_report_queries` SELECT * FROM `report_queries`');

        // Insert new row into table
        $this->execute('INSERT INTO `zz_6848_report_queries` (`name`, `query_sql`, `frequency`, `status`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (`system_errors_truncate`, `TRUNCATE system_errors;`, `week`, 1, NULL, NULL, 1, date(`Y-m-d H:i:s`))');
    }
         
    // rollback
    public function down()
    {
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `report_queries`');
        $this->execute('RENAME TABLE `zz_6914_report_queries` TO `report_queries`');
    }
}