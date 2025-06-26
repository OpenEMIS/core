<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8751 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        $this->execute('CREATE TABLE `zz_8751_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_8751_config_items` SELECT * FROM `config_items`');
        $this->execute('INSERT INTO `config_items` 
                (`name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
                VALUES 
                ("Edition", "edition", "System", "Edition", "Core", "", "Core", 0, 1, "", "", NULL, NULL, 1, CURRENT_TIMESTAMP)');

    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_8751_config_items` TO `config_items`');
    }
}
