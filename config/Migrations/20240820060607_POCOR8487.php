<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8487 extends AbstractMigration
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
        // Backup table
        $this->execute('CREATE TABLE `zz_8487_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_8487_config_items` SELECT * FROM `config_items`');

		$this->execute('INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, "Validate Contact Person Telephone", "validate_contact_person_telephone", "Institution", "Validate Contact Person Telephone", "/^[0-9]+$/gm", "", "0", "1", "1", "", "", NULL, NULL, "1", CURRENT_TIMESTAMP)');
        $this->execute('INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, "Validate Contact Person Mobile Number", "validate_contact_person_mobile_number", "Institution", "Validate Contact Person Mobile Number", "/^[0-9]+$/gm", "", "0", "1", "1", "", "", NULL, NULL, "1", CURRENT_TIMESTAMP)');
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_8487_config_items` TO `config_items`');

    }
}
