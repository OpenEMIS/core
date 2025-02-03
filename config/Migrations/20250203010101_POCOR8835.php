<?php


use Phinx\Migration\AbstractMigration;

class POCOR8835 extends AbstractMigration
{

    public function up(): void
    {
        $this->updateCreateZTable();
        $this->updateMappingFields();

    }

    private function updateCreateZTable(): void
    {
        try {
            $this->execute('CREATE TABLE `z_8835_import_mapping` LIKE `import_mapping`');
        } catch (\Exception $e) {

        }
        try {
            $this->execute('INSERT IGNORE INTO `z_8835_import_mapping` SELECT * FROM `import_mapping`');
        } catch (\Exception $e) {

        }
    }

    private function updateMappingFields(): void
    {

        // Step 1: Update the `order` field by reducing by 1 for all rows where model='User.Users'
//        $this->query("
//            UPDATE import_mapping
//            SET `order` = `order` - 1
//            WHERE model = 'User.Users' AND `order` > (
//                SELECT MIN(`order`)
//                FROM (SELECT * FROM import_mapping) as temp
//                WHERE model = 'User.Users' AND column_name = 'openemis_no'
//            )
//        ");

        // Step 2: Remove the `openemis_no` record
        $this->query("UPDATE `import_mapping` SET `column_name` = 'username', `description` = '' WHERE model = 'User.Users' AND column_name = 'openemis_no'");

    }

    public function down(): void
    {
        try {
//        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('DROP TABLE IF EXISTS `import_mapping`');
//        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
            $this->execute('RENAME TABLE `z_8835_import_mapping` TO `import_mapping`');
        } catch (\Exception $e) {

        }

    }


}
