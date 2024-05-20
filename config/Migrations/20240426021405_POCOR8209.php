<?php
use Migrations\AbstractMigration;
class POCOR8209 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_8209_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_8209_config_items` SELECT * FROM `config_items`');
        $this->execute("UPDATE `config_items` SET `default_value` = '15' WHERE `config_items`.`type` = 'Add New Staff' AND `config_items`.`code` = 'StaffMinimumAge'");
        $this->execute("UPDATE `config_items` SET `default_value` = '99' WHERE `config_items`.`type` = 'Add New Staff' AND `config_items`.`code` = 'StaffMaximumAge'");
    }
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_8209_config_items` TO `config_items`');
    }
}