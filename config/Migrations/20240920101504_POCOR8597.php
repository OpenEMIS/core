<?php
use Migrations\AbstractMigration;

/**
 * POCOR-8597
 * create mgration for excel file
 */
class POCOR8597 extends AbstractMigration
{
    public function up() {

        $this->execute('CREATE TABLE `zz_8597_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_8597_config_items` SELECT * FROM `config_items`');

         $this->execute('INSERT INTO `config_items` 
            (`name`, `code`, `type`, `label`, `value`, `value_selection`,`default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES 
            ("Validate Address", "institution_validate_address", "Institution", "Validate Address", "0", "0", "0", 1, 1, "Dropdown", "yes_no", 1, CURRENT_DATE())');

    }

    public function down() {
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_8597_config_items` TO `config_items`');
    }

}
