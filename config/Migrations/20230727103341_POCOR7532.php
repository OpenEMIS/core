<?php
use Migrations\AbstractMigration;

class POCOR7532 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_7532_config_item_options` LIKE `config_item_options`');
        $this->execute('INSERT INTO `z_7532_config_item_options` SELECT * FROM `config_item_options`');
        $this->execute('UPDATE `config_item_options` 
                         SET `option` = "PacSIMS" ,`value`="PacSIMS" 
                         WHERE `option_type`= "external_data_exam_source_type" 
                         AND `option`="Pacific Schools Information Management System (PacSIMS)"
                         AND  `value`="Pacific Schools Information Management System (PacSIMS)"');
    }
    // rollback
    public function down()
    {
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `config_item_options`');
        $this->execute('RENAME TABLE `zz_7532_config_item_options` TO `config_item_options`');
    }
}
