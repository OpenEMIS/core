<?php
use Migrations\AbstractMigration;

/**
 * POCOR-8697
 * create migration for excel file
 */
class POCOR8697 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `zz_8697_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_8697_config_items` SELECT * FROM `config_items`');

        $result = $this->query('SELECT COUNT(*) AS count FROM `config_items` WHERE `code` = "lowest_year"');

        // Fetch the result set as an associative array
        $row = $result->fetchAll(PDO::FETCH_ASSOC); // Use PDO::FETCH_ASSOC instead of 'assoc'

        // If record does not exist, insert it
        if ($row[0]['count'] == 0) {
            $this->execute('INSERT INTO `config_items` 
                (`name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
                VALUES 
                ("Lowest Year", "lowest_year", "System", "Lowest Year", "1950", "", "1950", 0, 1, "", "", NULL, NULL, 1, CURRENT_TIMESTAMP)');
        }
    }

    public function down()
    {
        // Drop and rename tables during rollback
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_8697_config_items` TO `config_items`');
    }
}
