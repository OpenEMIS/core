<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8395 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_8395_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_8395_config_items` SELECT * FROM `config_items`');

        $this->execute('CREATE TABLE `zz_8395_config_item_options` LIKE `config_item_options`');
        $this->execute('INSERT INTO `zz_8395_config_item_options` SELECT * FROM `config_item_options`');

        $this->execute("DELETE FROM config_items WHERE `code` = 'lowest_year'");
        $this->execute('INSERT INTO `config_items`
            (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES 
            (NULL, "Distance Measurement", "distance_measurement", "System", "Distance Measurement", "Meter", "Meter", "Meter", "1", "1", "Dropdown", "distance_measurement_type", NULL, NULL, "1", CURRENT_TIMESTAMP)');

        $this->execute("INSERT INTO `config_item_options` (`option_type`, `option`, `value`, `order`, `visible`) values('distance_measurement_type','Meter','Meter','1','1')");
        $this->execute("INSERT INTO `config_item_options` (`option_type`, `option`, `value`, `order`, `visible`) values('distance_measurement_type','Feet','Feet','2','1')");

    }

    public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_8395_config_items` TO `config_items`');

        $this->execute('DROP TABLE IF EXISTS `config_item_options`');
        $this->execute('RENAME TABLE `zz_8395_config_item_options` TO `config_item_options`');
    }
}
