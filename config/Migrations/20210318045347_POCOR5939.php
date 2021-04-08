<?php
use Migrations\AbstractMigration;

class POCOR5939 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_5939_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_5939_config_items` SELECT * FROM `config_items`');
        $this->execute('CREATE TABLE `zz_5939_config_item_options` LIKE `config_item_options`');
        $this->execute('INSERT INTO `zz_5939_config_item_options` SELECT * FROM `config_item_options`');
        $this->execute("INSERT INTO `config_item_options` (`id`, `option_type`, `option`, `value`, `order`, `visible`) VALUES (NULL, 'completeness', 'Enabled', '1', '0', '1'), (NULL, 'completeness', 'Disabled', '0', '0', '1')");
         $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (
NULL, 'Overview', 'overview', 'User Completeness', 'Overview', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'),
(NULL, 'Demographic', 'demographic', 'User Completeness', 'Demographic', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'),
(NULL, 'Identities', 'identities', 'User Completeness', 'Identities', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'),
(NULL, 'Nationalities', 'nationalities', 'User Completeness', 'Nationalities', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'),
(NULL, 'Contacts', 'contacts', 'User Completeness', 'Contacts', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'),
(NULL, 'Languages', 'languages', 'User Completeness', 'Languages', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'
)");

 $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (
NULL, 'Overview', 'institution_overview', 'Institution Completeness', 'Overview', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'),
(NULL, 'Calendar', 'calendar', 'Institution Completeness', 'Calendar', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'),
(NULL, 'Contacts', 'institution_contacts', 'Institution Completeness', 'Contacts', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'),
(NULL, 'Shifts', 'shifts', 'Institution Completeness', 'Shifts', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'),
(NULL, 'Programmes', 'programmes', 'Institution Completeness', 'Programmes', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'),
(NULL, 'Classes', 'classes', 'Institution Completeness', 'Classes', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'
),
(NULL, 'Subjects', 'subjects', 'Institution Completeness', 'Subjects', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'),
(NULL, 'Textbooks', 'textbooks', 'Institution Completeness', 'Textbooks', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'),
(NULL, 'Students', 'students', 'Institution Completeness', 'Students', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'),
(NULL, 'Staff', 'staff', 'Institution Completeness', 'Staff', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'),
(NULL, 'Attendance', 'attendance', 'Institution Completeness', 'Attendance', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'),
(NULL, 'Behaviour', 'behaviour', 'Institution Completeness', 'Behaviour', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'),
(NULL, 'Positions', 'positions', 'Institution Completeness', 'Positions', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'
),
(NULL, 'Bank Accounts', 'bank_accounts', 'Institution Completeness', 'Bank Accounts', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'
),
(NULL, 'Institution Fees', 'institution_fees', 'Institution Completeness', 'Institution Fees', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'
),
(NULL, 'Infrastructures Overview', 'infrastructures_overview', 'Institution Completeness', 'Infrastructures Overview', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'
),
(NULL, 'Infrastructures Needs', 'infrastructures_needs', 'Institution Completeness', 'Infrastructures Needs', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'
),
(NULL, 'Wash Water', 'wash_water', 'Institution Completeness', 'Wash Water', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'
),
(NULL, 'Wash Hygiene', 'wash_hygiene', 'Institution Completeness', 'Wash Hygiene', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'
),
(NULL, 'Wash Waste', 'wash_waste', 'Institution Completeness', 'Wash Waste', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'
),
(NULL, 'Wash Sewage', 'wash_sewage', 'Institution Completeness', 'Wash Sewage', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'
),
(NULL, 'Utilities Electricity', 'utilities_electricity', 'Institution Completeness', 'Utilities Electricity', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'
),
(NULL, 'Utilities Internet', 'utilities_nternet', 'Institution Completeness', 'Utilities Internet', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'
),
(NULL, 'Utilities Telephone', 'utilities_telephone', 'Institution Completeness', 'Utilities Telephone', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'
),
(NULL, 'Assets', 'assets', 'Institution Completeness', 'Assets', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'
),
(NULL, 'Transport', 'transport', 'Institution Completeness', 'Transport', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'
),
(NULL, 'Committees', 'committees', 'Institution Completeness', 'Committees', '1', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', '2020-12-29 14:02:21'
)");
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_5939_config_items` TO `config_items`');
        $this->execute('DROP TABLE IF EXISTS `config_item_options`');
        $this->execute('RENAME TABLE `zz_5939_config_item_options` TO `config_item_options`');
    }
}
