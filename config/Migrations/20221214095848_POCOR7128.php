<?php
use Migrations\AbstractMigration;

class POCOR7128 extends AbstractMigration
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
        // Creating backup
        $this->execute('DROP TABLE IF EXISTS `zz_7128_config_item_options`');
        $this->execute('CREATE TABLE `zz_7128_config_item_options` LIKE `config_item_options`');
       

        // update order in import compatancy section
        $this->execute('UPDATE `config_item_options` SET `option` = "Mark absent if one or more records absent" WHERE `option_type` = "calculate_daily_attendance" And `option` = "Mark absent if one or absent records"'); 
        
        $this->execute('UPDATE `config_item_options` SET `option` = "Mark present if one or more records present" WHERE `option_type` = "calculate_daily_attendance" And `option` = "Mark present if one or present records"'); 
    }

    // Rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `config_item_options`');
        $this->execute('RENAME TABLE `zz_7128_config_item_options` TO `config_item_options`');
    }
}

?>