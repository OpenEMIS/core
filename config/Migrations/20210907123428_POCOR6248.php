<?php
use Migrations\AbstractMigration;

class POCOR6248 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_6248_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `z_6248_config_items` SELECT * FROM `config_items`');

        //add value_selection column in `config_items` TABLE
        $this->execute("ALTER TABLE `config_items` ADD `value_selection` VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL AFTER `value`");

        //for student 
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Photo', 'student_photo', 'Columns for Student List Page', 'Photo', '1', '', NULL, '0', '1', 'Dropdown', 'completeness', '2', '2021-09-03 08:26:59', '2', '2021-08-27 00:00:00')");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'OpenEMIS ID', 'student_openEMIS_ID', 'Columns for Student List Page', 'OpenEMIS ID', '1', '', NULL, '0', '1', 'Dropdown', 'completeness', '2', '2021-09-03 08:27:10', '2', '2021-08-27 00:00:00')");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Identity Number', 'student_identity_number', 'Columns for Student List Page', 'Identity Number', '1', '163', '0', '1', '1', 'Dropdown', 'completeness', '2', '2021-09-02 08:31:30', '2', '2021-08-27 00:00:00')");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Name', 'student_name', 'Columns for Student List Page', 'Name', '1', '', '0', '0', '1', 'Dropdown', 'completeness', '2', '2021-09-03 08:27:21', '2', '2021-08-27 00:00:00')");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Education Grade', 'student_education_code', 'Columns for Student List Page', 'Education Grade', '1', '', '0', '0', '1', 'Dropdown', 'completeness', '2', '2021-09-03 08:27:31', '2', '2021-08-27 00:00:00')");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Class', 'student_class', 'Columns for Student List Page', 'Class', '1', '', '0', '0', '1', 'Dropdown', 'completeness', '2', '2021-09-03 08:27:41', '2', '2021-08-27 00:00:00')");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Status', 'student_status', 'Columns for Student List Page', 'Status', '1', '', '0', '0', '1', 'Dropdown', 'completeness', '2', '2021-09-03 08:27:50', '2', '2021-08-27 00:00:00')");

        //for staff
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Photo', 'staff_photo', 'Columns for Staff List Page', 'Photo', '1', '', NULL, '0', '1', 'Dropdown', 'completeness', '2', '2021-09-03 08:26:59', '2', '2021-08-27 00:00:00')");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'OpenEMIS ID', 'staff_openEMIS_ID', 'Columns for Staff List Page', 'OpenEMIS ID', '1', '', NULL, '0', '1', 'Dropdown', 'completeness', '2', '2021-09-03 08:27:10', '2', '2021-08-27 00:00:00')");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Identity Number', 'staff_identity_number', 'Columns for Staff List Page', 'Identity Number', '1', '163', '0', '1', '1', 'Dropdown', 'completeness', '2', '2021-09-02 08:31:30', '2', '2021-08-27 00:00:00')");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Name', 'staff_name', 'Columns for Staff List Page', 'Name', '1', '', '0', '0', '1', 'Dropdown', 'completeness', '2', '2021-09-03 08:27:21', '2', '2021-08-27 00:00:00')");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Position', 'staff_position', 'Columns for Staff List Page', 'Position', '1', '', '0', '0', '1', 'Dropdown', 'completeness', '2', '2021-09-03 08:27:31', '2', '2021-08-27 00:00:00')");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Start Date', 'staff_start_date', 'Columns for Staff List Page', 'Start Date', '1', '', '0', '0', '1', 'Dropdown', 'completeness', '2', '2021-09-03 08:27:41', '2', '2021-08-27 00:00:00')");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'End Date', 'staff_end_date', 'Columns for Staff List Page', 'End Date', '1', '', '0', '0', '1', 'Dropdown', 'completeness', '2', '2021-09-03 08:27:41', '2', '2021-08-27 00:00:00')");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Status', 'staff_status', 'Columns for Staff List Page', 'Status', '1', '', '0', '0', '1', 'Dropdown', 'completeness', '2', '2021-09-03 08:27:50', '2', '2021-08-27 00:00:00')");

        //for directory
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Photo', 'directory_photo', 'Columns for Directory List Page', 'Photo', '1', '', '0', '0', '1', 'Dropdown', 'completeness', '2', '2021-09-07 07:06:09', '2', '2021-08-27 00:00:00')");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Name', 'directory_name', 'Columns for Directory List Page', 'Name', '1', '', '0', '0', '1', 'Dropdown', 'completeness', '2', '2021-09-07 07:06:27', '2', '2021-08-27 00:00:00')");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'OpenEMIS ID', 'directory_openEMIS_ID', 'Columns for Directory List Page', 'OpenEMIS ID', '1', '', '0', '0', '1', 'Dropdown', 'completeness', '2', '2021-09-07 07:06:18', '2', '2021-08-27 00:00:00')");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Institution', 'directory_institution', 'Columns for Directory List Page', 'Institution', '1', '', '0', '0', '1', 'Dropdown', 'completeness', '2', '2021-09-07 07:06:18', '2', '2021-08-27 00:00:00')");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Date Of Birth', 'directory_date_of_birth', 'Columns for Directory List Page', 'Date Of Birth', '1', '', '0', '0', '1', 'Dropdown', 'completeness', '2', '2021-09-07 07:06:18', '2', '2021-08-27 00:00:00')");
        //$this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Student Status', 'directory_student_status', 'Columns for Directory List Page', 'Student Status', '1', '', '0', '0', '1', 'Dropdown', 'completeness', '2', '2021-09-07 07:06:18', '2', '2021-08-27 00:00:00')");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Identity Number', 'directory_identity_number', 'Columns for Directory List Page', 'Identity Number', '1', '160', '0', '1', '1', 'Dropdown', 'completeness', '2', '2021-09-07 07:05:43', '2', '2021-08-27 00:00:00')");
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `z_6248_config_items` TO `config_items`');
    }
}
