<?php

declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR7509 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * 
     * @return void
     */
    public function up()
    {
        // Create a backup table like `security_functions`
        $this->execute('CREATE TABLE IF NOT EXISTS `zz_7509_security_functions` LIKE `security_functions`');

        // Copy data from `security_functions` to `zz_7509_security_functions`
        $this->execute('INSERT IGNORE INTO `zz_7509_security_functions` SELECT * FROM `security_functions`');

        // Add Sync records for different modules
        $this->addSyncRecordIfNotExists('Examinations', 'Examinations', 'Administration', 'syncResultFromExam.execute');
        $this->addSyncRecordIfNotExists('Institutions', 'Examinations', 'Institutions', 'syncStudentsToExam.execute');

        $this->execute('CREATE TABLE IF NOT EXISTS `zz_7509_examination_centres_examinations_students` LIKE `examination_centres_examinations_students`');
        $this->execute('INSERT IGNORE INTO `zz_7509_examination_centres_examinations_students` SELECT * FROM `examination_centres_examinations_students`');
        $this->execute('ALTER TABLE `examination_centres_examinations_students` ADD `sync_status` INT NULL DEFAULT NULL AFTER `academic_period_id`');
        $this->execute('ALTER TABLE `examination_centres_examinations_students` ADD `last_synced` DATETIME NULL AFTER `sync_status`');
    }

    /**
     * Helper method to add 'Sync' record if it doesn't exist.
     * 
     * @param string $controller The controller to check for.
     * @param string $category The category to check for.
     * @param string $module The module to check for.
     * @param string $execute The 'execute' value for the 'Sync' record.
     * 
     * @return void
     */
    private function addSyncRecordIfNotExists(string $controller, string $category, string $module, string $execute)
    {
        // Query for the 'Results' row
        $resultRow = $this->fetchRow(
            "SELECT * FROM `security_functions`
             WHERE `name` = 'Results' AND 
                   `controller` = '{$controller}' AND 
                   `module` = '{$module}' AND 
                   `category` = '{$category}'"
        );

        // Query for the 'Sync' row
        $syncExists = $this->fetchRow(
            "SELECT * FROM `security_functions`
             WHERE `name` = 'Sync' AND 
                   `controller` = '{$controller}' AND 
                   `module` = '{$module}' AND 
                   `category` = '{$category}'"
        );

        // Insert Sync record if 'Sync' doesn't exist and 'Results' row is found
        if (!$syncExists && $resultRow) {
            $this->insertSyncRecord($resultRow, $module, $execute);
        }
    }

    /**
     * Inserts a 'Sync' record into the `security_functions` table.
     * 
     * @param array $resultRow The 'Results' row data to inherit values.
     * @param string $module The module for the 'Sync' record.
     * @param string $execute The 'execute' value for the 'Sync' record.
     * 
     * @return void
     */
    private function insertSyncRecord(array $resultRow, string $module, string $execute)
    {
        $record = [
            [
                'name' => 'Sync',
                'controller' => 'Examinations',
                'module' => $module,
                'category' => 'Examinations',
                'parent_id' => $resultRow['parent_id'],
                '_view' => null,
                '_edit' => null,
                '_add' => null,
                '_delete' => null,
                '_execute' => $execute,
                'order' => $resultRow['order'],
                'visible' => 1,
                'description' => null,
                'modified_user_id' => null,
                'modified' => null,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];

        $this->table('security_functions')->insert($record)->save();
    }

    /**
     * Revert the migration (down method).
     *
     * @return void
     */
    public function down()
    {
        // Revert the table to its previous state by renaming
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_7509_security_functions` TO `security_functions`');
        $this->execute('DROP TABLE IF EXISTS `examination_centres_examinations_students`');
        $this->execute('RENAME TABLE `zz_7509_examination_centres_examinations_students` TO `examination_centres_examinations_students`');
    }
}
