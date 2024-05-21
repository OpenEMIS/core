<?php
use Migrations\AbstractMigration;

class POCOR7508 extends AbstractMigration
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

        $this->execute('CREATE TABLE `zz_7508_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_7508_config_items` SELECT * FROM `config_items`');

        $this->execute('CREATE TABLE `zz_7508_config_item_options` LIKE `config_item_options`');
        $this->execute('INSERT INTO `zz_7508_config_item_options` SELECT * FROM `config_item_options`');

        $this->execute('INSERT INTO `config_items` 
            (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`,`default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES 
            (1300, "Type", "external_data_exam_source_type", "External Data Source - Exams", "Type", "OpenEMIS Exams", "Test", "None", 1, 1, "Dropdown", "external_data_exam_source_type", 1, CURRENT_DATE())');


        $this->execute("INSERT INTO `config_item_options` (`option_type`, `option`, `value`, `order`, `visible`) values('external_data_exam_source_type','OpenEMIS Exams','OpenEMIS Exams','1','1')");

        $this->execute("INSERT INTO `config_item_options` (`option_type`, `option`, `value`, `order`, `visible`) values('external_data_exam_source_type','Caribbean Examinations Council (CXC)','Caribbean Examinations Council (CXC)','2','1')");

        $this->execute("INSERT INTO `config_item_options` (`option_type`, `option`, `value`, `order`, `visible`) values('external_data_exam_source_type','Pacific Schools Information Management System (PacSIMS)','Pacific Schools Information Management System (PacSIMS)','3','1')");
    }

    public function down()
    {

        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_7508_config_items` TO `config_items`');

        $this->execute('DROP TABLE IF EXISTS `config_item_options`');
        $this->execute('RENAME TABLE `zz_7508_config_item_options` TO `config_item_options`');


        // $this->execute('DELETE FROM `config_items` WHERE `code` = "external_data_exam_source_type"');
        // $this->execute('DELETE FROM `config_item_options` WHERE `option_type` = "external_data_exam_source_type"');
    }
}
