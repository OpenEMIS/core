<?php
use Migrations\AbstractMigration;

class POCOR6930 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_6930_config_item_options` LIKE `config_item_options`');
        $this->execute('INSERT INTO `z_6930_config_item_options` SELECT * FROM `config_item_options`');

        $this->execute("INSERT INTO `config_item_options` (`id`, `option_type`, `option`, `value`, `order`, `visible`) VALUES (NULL, 'external_data_source_type', 'Jordan CSPD', 'Jordan CSPD', '4', '1')");
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `config_item_options`');
        $this->execute('RENAME TABLE `z_6930_config_item_options` TO `config_item_options`');
    }
}
