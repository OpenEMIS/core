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
        $this->execute('
            DELETE t1
            FROM import_mapping t1
            JOIN import_mapping t2
                ON t1.model = t2.model
                AND t1.column_name = t2.column_name
                AND IFNULL(t1.description, "") = IFNULL(t2.description, "")
                AND IFNULL(t1.`order`, 0) = IFNULL(t2.`order`, 0)
                AND t1.is_optional = t2.is_optional
                AND IFNULL(t1.foreign_key, 0) = IFNULL(t2.foreign_key, 0)
                AND IFNULL(t1.lookup_plugin, "") = IFNULL(t2.lookup_plugin, "")
                AND IFNULL(t1.lookup_model, "") = IFNULL(t2.lookup_model, "")
                AND IFNULL(t1.lookup_column, "") = IFNULL(t2.lookup_column, "")
                AND t1.id > t2.id;
        ');

        // 2. Check if the Unique Key Already Exists
        $result = $this->fetchRow("
            SELECT COUNT(*) as count
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = 'import_mapping'
              AND index_name = 'unique_import_mapping';
        ");

        // 3. Add Unique Key if Not Exists
        if ($result['count'] == 0) {
            $this->execute('
                ALTER TABLE import_mapping
                ADD UNIQUE KEY unique_import_mapping (
                    model,
                    `column_name`,
                    description,
                    `order`,
                    is_optional,
                    foreign_key,
                    lookup_plugin,
                    lookup_model,
                    lookup_column
                );
            ');
        }
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

        // Define data as an array for step-by-step insertion
        $data = [
            ['User.Users', 'institution_code', '**', 19, 0, 0, null, null, null],
            ['User.Users', 'academic_period_id', '** Code', 20, 0, 2, 'AcademicPeriod', 'AcademicPeriods', 'code'],
            ['User.Users', 'education_grade_id', '** Code', 21, 0, 2, 'Education', 'EducationGrades', 'code'],
            ['User.Users', 'class_name', '**', 22, 0, 0, null, null, null],
            ['User.Users', 'start_date', '** ( DD/MM/YYYY )', 23, 0, 0, null, null, null],
            ['User.Users', 'guardian_relation_id', '*** Code', 24, 0, 2, 'Student', 'GuardianRelations', 'id'],
            ['User.Users', 'guardian_openemis_no', '(Leave as blank for new entries)', 25, 0, 0, null, null, null],
            ['User.Users', 'guardian_first_name', '***', 26, 0, 0, null, null, null],
            ['User.Users', 'guardian_middle_name', null, 27, 0, 0, null, null, null],
            ['User.Users', 'guardian_third_name', null, 28, 0, 0, null, null, null],
            ['User.Users', 'guardian_last_name', '***', 29, 0, 0, null, null, null],
            ['User.Users', 'guardian_preferred_name', null, 30, 0, 0, null, null, null],
            ['User.Users', 'guardian_gender_id', '*** Code (M/F)', 31, 0, 2, 'User', 'Genders', 'code'],
            ['User.Users', 'guardian_date_of_birth', '*** ( DD/MM/YYYY )', 32, 0, 0, null, null, null],
            ['User.Users', 'guardian_address', null, 33, 0, 0, null, null, null],
            ['User.Users', 'guardian_postal', null, 34, 0, 0, null, null, null],
            ['User.Users', 'guardian_address_area_id', 'Code', 35, 0, 2, 'Area', 'AreaAdministratives', 'code'],
            ['User.Users', 'guardian_birthplace_area_id', 'Code', 36, 0, 2, 'Area', 'AreaAdministratives', 'code'],
            ['User.Users', 'guardian_nationality_id', null, 37, 1, 1, 'FieldOption', 'Nationalities', 'id'],
            ['User.Users', 'guardian_identity_type', 'Code', 38, 1, 1, 'FieldOption', 'IdentityTypes', 'national_code'],
            ['User.Users', 'guardian_identity_number', null, 39, 0, 0, null, null, null],
            ['User.Users', 'guardian_contact_email', null, 40, 0, 0, null, null, null],
            ['User.Users', 'guardian_contact_cell_phone', null, 41, 0, 0, null, null, null],
        ];

        // Iterate through the array for step-by-step insertion
        foreach ($data as $row) {
            $this->execute(sprintf(
                "INSERT IGNORE INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`)
            VALUES ('%s', '%s', %s, %d, %d, %d, %s, %s, %s)",
                $row[0],
                $row[1],
                $row[2] !== null ? "'" . $row[2] . "'" : 'NULL',
                $row[3],
                $row[4],
                $row[5],
                $row[6] !== null ? "'" . $row[6] . "'" : 'NULL',
                $row[7] !== null ? "'" . $row[7] . "'" : 'NULL',
                $row[8] !== null ? "'" . $row[8] . "'" : 'NULL'
            ));
        }
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
