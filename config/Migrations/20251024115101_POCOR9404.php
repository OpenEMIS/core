<?php
use Migrations\AbstractMigration;

class POCOR9404 extends AbstractMigration
{
    public function up()
    {
        //Backup the import_mapping table
        $this->execute('DROP TABLE IF EXISTS `z_9404_import_mapping`');
        $this->execute('CREATE TABLE `z_9404_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `z_9404_import_mapping` SELECT * FROM `import_mapping`');

        //Get current max order for Institution.StudentAdmission
        $result = $this->fetchAll("
            SELECT MAX(`order`) AS max_order
            FROM `import_mapping`
            WHERE `model` = 'Institution.StudentAdmission'
        ");
        $maxOrder = $result[0]['max_order'] ?? 0;

        //Insert identity_type_id
        $this->execute("
            INSERT INTO `import_mapping`
                (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`)
            VALUES
                ('Institution.StudentAdmission', 'identity_type_id', '', " . $maxOrder . ", 0, 2, 'FieldOption', 'IdentityTypes', 'id');
        ");

        //Insert identity_number
        $this->execute("
            INSERT INTO `import_mapping`
                (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`)
            VALUES
                ('Institution.StudentAdmission', 'identity_number', '', " . ($maxOrder + 1) . ", 0, 0, NULL, NULL, NULL);
        ");

        // Update institution_class_id order.Because import excel showing class_id in last column. 
        $this->execute("
            UPDATE `import_mapping`
            SET `order` = " . ($maxOrder + 2) . "
            WHERE `model` = 'Institution.StudentAdmission'
              AND `column_name` = 'institution_class_id';
        ");

    }

    public function down()
    {
        // Restore the original table from backup
        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        $this->execute('RENAME TABLE `z_9404_import_mapping` TO `import_mapping`');
    }
}
