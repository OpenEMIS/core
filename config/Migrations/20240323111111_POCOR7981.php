<?php
use Migrations\AbstractMigration;

class POCOR7981 extends AbstractMigration
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
        if($this->hasTable('z_7981_config_items')){
        $this->execute('CREATE TABLE `z_7981_config_items` LIKE `config_items`');
        }
        $this->execute('INSERT IGNORE INTO `z_7981_config_items` SELECT * FROM `config_items`');
//External Data Source - Identity
        $this->execute("INSERT INTO `config_items` (
                                   `id`,
                                   `option_type`,
                                   `option`,
                                   `value`,
                                   `order`,
                                   `visible`) VALUES (
                                                      NULL,
                                                      'external_data_source_type',
                                                      'UNHCR',
                                                      'UNHCR',
                                                      '6',
                                                      '1')");
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `config_item_options`');
        $this->execute('RENAME TABLE `z_7981_config_item_options` TO `config_item_options`');
    }
}
