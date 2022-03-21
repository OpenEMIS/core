<?php
use Migrations\AbstractMigration;

class POCOR6625 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     * @ticket POCOR-6625
     */
    public function up()
    {
        // Backup config_items table
        $this->execute('DROP TABLE IF EXISTS `zz_6625_config_items`');
        $this->execute('CREATE TABLE `zz_6625_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_6625_config_items` SELECT * FROM `config_items`');

        /** Create OpenEMIS Core report_student_assessment_summary table */
        $this->execute('UPDATE `config_items` SET `value` = 1 WHERE `config_items`.`code` = "latitude_longitude"');
    }

    //rollback
    public function down()
    {
        // $this->execute('UPDATE `config_items` SET `value` = 0 WHERE `config_items`.`code` = "latitude_longitude"');
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_6625_config_items` TO `config_items`');
    }
}
