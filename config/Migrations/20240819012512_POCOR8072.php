<?php
use Cake\Utility\Text;
use Phinx\Migration\AbstractMigration;
use Cake\Datasource\ConnectionManager;

class POCOR8072 extends AbstractMigration
{
    public function up()
    {
        // Backup
        $this->execute('CREATE TABLE `z_8072_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_8072_security_functions` SELECT * FROM `security_functions`');

        // Update `order`
        $this->execute('UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` > 478');

        // Insert data using CakePHP ORM
        $connection = ConnectionManager::get('default');

        $connection->insert('security_functions', [
            'name' => 'Overview',
            'controller' => 'Profiles',
            'module' => 'Personal',
            'category' => 'Scholarships',
            'parent_id' => 9030,
            '_view' => 'Applications.index|Applications.view',
            '_edit' => NULL,
            '_add' => NULL,
            '_delete' => NULL,
            '_execute' => NULL,
            '`order`' => 479,  // Escape the column name using backticks
            'visible' => 1,
            'description' => NULL,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);

        $connection->insert('security_functions', [
            'name' => 'Institution Choices',
            'controller' => 'Profiles',
            'module' => 'Personal',
            'category' => 'Scholarships',
            'parent_id' => 9030,
            '_view' => 'ScholarshipApplicationInstitutionChoices.index|ScholarshipApplicationInstitutionChoices.view',
            '_edit' => 'ScholarshipApplicationInstitutionChoices.edit',
            '_add' => 'ScholarshipApplicationInstitutionChoices.add',
            '_delete' => 'ScholarshipApplicationInstitutionChoices.remove',
            '_execute' => NULL,
            '`order`' => 479,  // Escape the column name using backticks
            'visible' => 1,
            'description' => NULL,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);

        // Update `order` for student curricular
        $this->execute('UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` > 480');

        // Insert more data using CakePHP ORM
        $connection->insert('security_functions', [
            'name' => 'Attachments',
            'controller' => 'Profiles',
            'module' => 'Personal',
            'category' => 'Scholarships',
            'parent_id' => 9030,
            '_view' => 'ScholarshipApplicationAttachments.index|ScholarshipApplicationAttachments.view',
            '_edit' => 'ScholarshipApplicationAttachments.edit',
            '_add' => 'ScholarshipApplicationAttachments.add',
            '_delete' => 'ScholarshipApplicationAttachments.remove',
            '_execute' => NULL,
            '`order`' => 481,  // Escape the column name using backticks
            'visible' => 1,
            'description' => NULL,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);
    }

    // Rollback
    public function down()
    {
        $this->execute('RENAME TABLE `z_8072_security_functions` TO `security_functions`');
        $this->execute('UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` > 478');
    }
}
