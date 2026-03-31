<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8898 extends AbstractMigration
{
    /**
     * Up Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        // Create archive table for institution_students_report_cards
        if (!$this->hasTable('institution_students_report_cards_archived')) {
            $this->execute('CREATE TABLE `institution_students_report_cards_archived` LIKE `institution_students_report_cards`');
        }
        
        // Create backup table for security_functions
        if (!$this->hasTable('z_8898_security_functions')) {
            $this->execute('CREATE TABLE `z_8898_security_functions` LIKE `security_functions`');
            $this->execute('INSERT INTO `z_8898_security_functions` SELECT * FROM `security_functions`');
        }

        $funName = 'Student Assessment Archive';

        $fetchedRow = $this->fetchRow("SELECT `order`, `parent_id` FROM security_functions WHERE name = '{$funName}' AND controller = 'Institutions' AND module = 'Institutions' AND category = 'Students' LIMIT 1");

        if ($fetchedRow) {
            $order = $fetchedRow['order'] + 1;
            $parentId = $fetchedRow['parent_id'];

            $funName = 'Student Report Card Archive';
            $modelName = 'InstitutionStudentsReportCardsArchived';
            $modelName1 = 'ReportCardArchives';
            $sql = "INSERT IGNORE INTO security_functions
                (id, name, controller, module, category, parent_id, _view, _edit, _add, _delete, _execute, `order`, visible, description, modified_user_id, modified, created_user_id, created)
                VALUES
                (
                    NULL,
                    '{$funName}',
                    'Institutions',
                    'Institutions',
                    'Students',
                    '{$parentId}',
                    '{$modelName}.index | {$modelName1}.index',
                    NULL,
                    NULL,
                    NULL,
                    NULL,
                    {$order},
                    1,
                    NULL,
                    2,
                    NOW(),
                    1,
                    NOW()
                );";
            $this->execute($sql);
        }
    }

    public function down()
    {
        // Remove the new security function
        $this->execute("DELETE FROM security_functions WHERE name = 'Student Report Card Archive' AND controller = 'Institutions' AND module = 'Institutions' AND category = 'Students'");
        
        // Drop archive table for institution_students_report_cards
        $this->execute('DROP TABLE IF EXISTS `institution_students_report_cards_archived`');

        // Restore security_functions table from backup
        if ($this->hasTable('z_8898_security_functions')) {
            $this->execute('DROP TABLE IF EXISTS `security_functions`');
            $this->execute('RENAME TABLE `z_8898_security_functions` TO `security_functions`');
        }
    }

   

}