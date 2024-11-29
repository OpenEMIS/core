<?php

use Phinx\Migration\AbstractMigration;

class POCOR8683 extends AbstractMigration
{

    public function up(): void
    {
        $this->updateCreateZTable();
        $this->updateAddMappingFields();

    }

    private function updateCreateZTable(): void
    {
        try {
            $this->execute('CREATE TABLE `z_8683_import_mapping` LIKE `import_mapping`');
        } catch (\Exception $e) {

        }
        try {
            $this->execute('INSERT IGNORE INTO `z_8683_import_mapping` SELECT * FROM `import_mapping`');
        } catch (\Exception $e) {

        }
    }

    private function updateAddMappingFields(): void
    {
        // Update descriptions for specific records
        $updates = [
            "UPDATE `import_mapping` SET `description` = '*' WHERE `model` = 'User.Users' AND `column_name` = 'first_name'",
            "UPDATE `import_mapping` SET `description` = '*' WHERE `model` = 'User.Users' AND `column_name` = 'last_name'",
            "UPDATE `import_mapping` SET `description` = '* Code (M/F)' WHERE `model` = 'User.Users' AND `column_name` = 'gender_id'",
            "UPDATE `import_mapping` SET `description` = '* ( DD/MM/YYYY )' WHERE `model` = 'User.Users' AND `column_name` = 'date_of_birth'",
            "UPDATE `import_mapping` SET `description` = '* Code' WHERE `model` = 'User.Users' AND `column_name` = 'account_type'"
        ];

        foreach ($updates as $query) {
            $this->execute($query);
        }

        // Insert records with IDs 248 to 272, setting id to null
        $data = [
            ['model' => 'User.Users', 'column_name' => 'institution_code', 'description' => '**', 'order' => 19, 'is_optional' => 0, 'foreign_key' => 0, 'lookup_plugin' => null, 'lookup_model' => null, 'lookup_column' => null],
            ['model' => 'User.Users', 'column_name' => 'academic_period_id', 'description' => '** Code', 'order' => 20, 'is_optional' => 0, 'foreign_key' => 0, 'lookup_plugin' => 'AcademicPeriod', 'lookup_model' => 'AcademicPeriods', 'lookup_column' => 'code'],
            ['model' => 'User.Users', 'column_name' => 'education_grade_id', 'description' => '**', 'order' => 21, 'is_optional' => 0, 'foreign_key' => 0, 'lookup_plugin' => null, 'lookup_model' => null, 'lookup_column' => null],
            ['model' => 'User.Users', 'column_name' => 'class_name', 'description' => '**', 'order' => 22, 'is_optional' => 0, 'foreign_key' => 0, 'lookup_plugin' => null, 'lookup_model' => null, 'lookup_column' => null],
            ['model' => 'User.Users', 'column_name' => 'start_date', 'description' => '** ( DD/MM/YYYY )', 'order' => 23, 'is_optional' => 0, 'foreign_key' => 0, 'lookup_plugin' => null, 'lookup_model' => null, 'lookup_column' => null],
            ['model' => 'User.Users', 'column_name' => 'guardian_relation_id', 'description' => '*** Relation Code (Guardian)', 'order' => 24, 'is_optional' => 0, 'foreign_key' => 2, 'lookup_plugin' => 'Student', 'lookup_model' => 'GuardianRelations', 'lookup_column' => 'id'],
            ['model' => 'User.Users', 'column_name' => 'guardian_openemis_no', 'description' => '(Leave as blank for new entries)', 'order' => 25, 'is_optional' => 0, 'foreign_key' => 0, 'lookup_plugin' => null, 'lookup_model' => null, 'lookup_column' => null],
            ['model' => 'User.Users', 'column_name' => 'guardian_first_name', 'description' => '***', 'order' => 26, 'is_optional' => 0, 'foreign_key' => 0, 'lookup_plugin' => null, 'lookup_model' => null, 'lookup_column' => null],
            ['model' => 'User.Users', 'column_name' => 'guardian_middle_name', 'description' => null, 'order' => 27, 'is_optional' => 0, 'foreign_key' => 0, 'lookup_plugin' => null, 'lookup_model' => null, 'lookup_column' => null],
            ['model' => 'User.Users', 'column_name' => 'guardian_third_name', 'description' => null, 'order' => 28, 'is_optional' => 0, 'foreign_key' => 0, 'lookup_plugin' => null, 'lookup_model' => null, 'lookup_column' => null],
            ['model' => 'User.Users', 'column_name' => 'guardian_last_name', 'description' => '***', 'order' => 29, 'is_optional' => 0, 'foreign_key' => 0, 'lookup_plugin' => null, 'lookup_model' => null, 'lookup_column' => null],
            ['model' => 'User.Users', 'column_name' => 'guardian_preferred_name', 'description' => null, 'order' => 30, 'is_optional' => 0, 'foreign_key' => 0, 'lookup_plugin' => null, 'lookup_model' => null, 'lookup_column' => null],
            ['model' => 'User.Users', 'column_name' => 'guardian_gender_id', 'description' => 'Code (M/F) ***', 'order' => 31, 'is_optional' => 0, 'foreign_key' => 0, 'lookup_plugin' => 'User', 'lookup_model' => 'Genders', 'lookup_column' => 'code'],
            ['model' => 'User.Users', 'column_name' => 'guardian_date of Birth', 'description' => '*** ( DD/MM/YYYY )', 'order' => 32, 'is_optional' => 0, 'foreign_key' => 0, 'lookup_plugin' => null, 'lookup_model' => null, 'lookup_column' => null],
            ['model' => 'User.Users', 'column_name' => 'guardian_address', 'description' => null, 'order' => 33, 'is_optional' => 0, 'foreign_key' => 0, 'lookup_plugin' => null, 'lookup_model' => null, 'lookup_column' => null],
            ['model' => 'User.Users', 'column_name' => 'guardian_postal', 'description' => null, 'order' => 34, 'is_optional' => 0, 'foreign_key' => 0, 'lookup_plugin' => null, 'lookup_model' => null, 'lookup_column' => null],
            ['model' => 'User.Users', 'column_name' => 'guardian_address_area_id', 'description' => 'Code', 'order' => 35, 'is_optional' => 0, 'foreign_key' => 0, 'lookup_plugin' => 'Area', 'lookup_model' => 'AreaAdministratives', 'lookup_column' => 'code'],
            ['model' => 'User.Users', 'column_name' => 'guardian_birthplace_area_id', 'description' => 'Code', 'order' => 36, 'is_optional' => 0, 'foreign_key' => 0, 'lookup_plugin' => 'Area', 'lookup_model' => 'AreaAdministratives', 'lookup_column' => 'code'],
            ['model' => 'User.Users', 'column_name' => 'guardian_nationality_id', 'description' => null, 'order' => 37, 'is_optional' => 0, 'foreign_key' => 0, 'lookup_plugin' => 'FieldOption', 'lookup_model' => 'Nationalities', 'lookup_column' => 'id'],
            ['model' => 'User.Users', 'column_name' => 'guardian_identity_type', 'description' => 'Code', 'order' => 38, 'is_optional' => 0, 'foreign_key' => 0, 'lookup_plugin' => 'FieldOption', 'lookup_model' => 'IdentityTypes', 'lookup_column' => 'national_code'],
            ['model' => 'User.Users', 'column_name' => 'guardian_identity_number', 'description' => null, 'order' => 39, 'is_optional' => 0, 'foreign_key' => 0, 'lookup_plugin' => null, 'lookup_model' => null, 'lookup_column' => null],
            ['model' => 'User.Users', 'column_name' => 'guardian_contact_email', 'description' => null, 'order' => 40, 'is_optional' => 0, 'foreign_key' => 0, 'lookup_plugin' => null, 'lookup_model' => null, 'lookup_column' => null],
            ['model' => 'User.Users', 'column_name' => 'guardian_contact_cell_phone', 'description' => null, 'order' => 41, 'is_optional' => 0, 'foreign_key' => 0, 'lookup_plugin' => null, 'lookup_model' => null, 'lookup_column' => null],
        ];

        $this->insert('import_mapping', $data);
    }

    public function down(): void
    {
        try {
//        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('DROP TABLE IF EXISTS `import_mapping`');
//        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
            $this->execute('RENAME TABLE `z_8683_import_mapping` TO `import_mapping`');
        } catch (\Exception $e) {

        }

    }


}
