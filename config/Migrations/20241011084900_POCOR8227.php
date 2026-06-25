<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8227 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up(): void
    {
        $this->execute('CREATE TABLE `z_8227_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `z_8227_config_items` SELECT * FROM `config_items`');
        $this->execute("UPDATE `config_items` SET `default_value` = '55' WHERE `config_items`.`type` = 'Health' AND `config_items`.`code` = 'StudentMinimumHeight'");
        $this->execute("UPDATE `config_items` SET `default_value` = '270' WHERE `config_items`.`type` = 'Health' AND `config_items`.`code` = 'StudentMaximumHeight'");
        $this->execute("UPDATE `config_items` SET `default_value` = '10' WHERE `config_items`.`type` = 'Health' AND `config_items`.`code` = 'StudentMinimumWeight'");
        $this->execute("UPDATE `config_items` SET `default_value` = '625' WHERE `config_items`.`type` = 'Health' AND `config_items`.`code` = 'StudentMaximumWeight'");
    }

    //Rollback
    public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `z_8227_config_items` TO `config_items`');
    }
}
