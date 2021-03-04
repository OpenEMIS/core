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
         // Backup table
        $this->execute('CREATE TABLE `zz_5939_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_5939_config_items` SELECT * FROM `config_items`');
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (
NULL, 'Overview', 'overview', 'User Profile', 'Overview', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'),
(NULL, 'Demographic', 'demographic', 'User Profile', 'Demographic', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'),
(NULL, 'Identities', 'identities', 'User Profile', 'Identities', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'),
(NULL, 'Nationalities', 'nationalities', 'User Profile', 'Nationalities', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'),
(NULL, 'Contacts', 'contacts', 'User Profile', 'Contacts', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'),
(NULL, 'Languages', 'languages', 'User Profile', 'Languages', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'
)");

 $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (
NULL, 'Overview', 'institution_overview', 'Institution Profile', 'Overview', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'),
(NULL, 'Calendar', 'calendar', 'Institution Profile', 'Calendar', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'),
(NULL, 'Contacts', 'institution_contacts', 'Institution Profile', 'Contacts', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'),
(NULL, 'Shifts', 'shifts', 'Institution Profile', 'Shifts', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'),
(NULL, 'Programmes', 'programmes', 'Institution Profile', 'Programmes', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'),
(NULL, 'Classes', 'classes', 'Institution Profile', 'Classes', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'
),
(NULL, 'Subjects', 'subjects', 'Institution Profile', 'Subjects', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'),
(NULL, 'Textbooks', 'textbooks', 'Institution Profile', 'Textbooks', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'),
(NULL, 'Students', 'students', 'Institution Profile', 'Students', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'),
(NULL, 'Staff', 'staff', 'Institution Profile', 'Staff', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'),
(NULL, 'Attendance', 'attendance', 'Institution Profile', 'Attendance', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'),
(NULL, 'Behaviour', 'behaviour', 'Institution Profile', 'Behaviour', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'),
(NULL, 'Positions', 'positions', 'Institution Profile', 'Positions', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'
),
(NULL, 'Bank Accounts', 'bank_accounts', 'Institution Profile', 'Bank Accounts', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'
),
(NULL, 'Institution Fees', 'institution_fees', 'Institution Profile', 'Institution Fees', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'
),
(NULL, 'Infrastructures Overview', 'infrastructures_overview', 'Institution Profile', 'Infrastructures Overview', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'
),
(NULL, 'Infrastructures Needs', 'infrastructures_needs', 'Institution Profile', 'Infrastructures Needs', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'
),
(NULL, 'Wash Water', 'wash_water', 'Institution Profile', 'Wash Water', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'
),
(NULL, 'Wash Hygiene', 'wash_hygiene', 'Institution Profile', 'Wash Hygiene', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'
),
(NULL, 'Wash Waste', 'wash_waste', 'Institution Profile', 'Wash Waste', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'
),
(NULL, 'Wash Sewage', 'wash_sewage', 'Institution Profile', 'Wash Sewage', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'
),
(NULL, 'Utilities Electricity', 'utilities_electricity', 'Institution Profile', 'Utilities Electricity', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'
),
(NULL, 'Utilities Internet', 'utilities_nternet', 'Institution Profile', 'Utilities Internet', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'
),
(NULL, 'Utilities Telephone', 'utilities_telephone', 'Institution Profile', 'Utilities Telephone', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'
),
(NULL, 'Assets', 'assets', 'Institution Profile', 'Assets', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'
),
(NULL, 'Transport', 'transport', 'Institution Profile', 'Transport', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'
),
(NULL, 'Committees', 'committees', 'Institution Profile', 'Committees', '1', '0', '0', '1', 'Dropdown', 'configitems_type_value', NULL, NULL, '1', '2020-12-29 14:02:21'
)");
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_5939_config_items` TO `config_items`');
    }
}
