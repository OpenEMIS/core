<?php
use Migrations\AbstractMigration;

class POCOR7716 extends AbstractMigration
{
    // commit
    public function up()
    {
        // Backup table
        $this->execute('CREATE TABLE `zz_7716_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_7716_config_items` SELECT * FROM `config_items`');

        $configData = [
            'name' => 'Default Student Admission Status',
            'code' => 'student_admission_status',
            'type' => 'Add New Student',
            'label' => 'Default Student Admission Status',
            'value' => 0,
            'value_selection' => '',
            'default_value' => 0,
            'editable' => 1,
            'visible' => 1,
            'field_type' => "Dropdown",
            'option_type' => "admission_options",
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s'),
            'modified' => null,
            'modified_user_id' => null
        ];
        $this->insert('config_items', $configData);

        $this->execute('CREATE TABLE `zz_7716_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `zz_7716_import_mapping` SELECT * FROM `import_mapping`');
        $this->execute('
            DELETE FROM `import_mapping` WHERE `model` = "Institution.StudentAdmission" AND `column_name` = "status_id"
        ');
    }

    // rollback
    public function down()
    {
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_7716_config_items` TO `config_items`');

        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        $this->execute('RENAME TABLE `zz_7716_import_mapping` TO `import_mapping`');
    }
}
