<?php


use Phinx\Migration\AbstractMigration;

class POCOR9476 extends AbstractMigration
{

    public function up(): void
    {
        $this->updateCreateZTable();
        $this->updateMappingFields();

    }

    private function updateCreateZTable(): void
    {
        try {
            $this->execute('CREATE TABLE `z_9476_import_mapping` LIKE `import_mapping`');
        } catch (\Exception $e) {

        }
        try {
            $this->execute('INSERT IGNORE INTO `z_9476_import_mapping` SELECT * FROM `import_mapping`');
        } catch (\Exception $e) {

        }
    }

    private function updateMappingFields(): void
    {

        // Update descriptions for specific records
        $updates = [
            "UPDATE `import_mapping` SET `description` = '' WHERE `model` = 'User.Users' AND `column_name` = 'class_name'",
        ];

        foreach ($updates as $query) {
            $this->execute($query);
        }

    }

    public function down(): void
    {
        try {
//        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('DROP TABLE IF EXISTS `import_mapping`');
//        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
            $this->execute('RENAME TABLE `z_9476_import_mapping` TO `import_mapping`');
        } catch (\Exception $e) {

        }

    }


}
