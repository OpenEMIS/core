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
        $row = $this->fetchRow("SELECT MAX(`order`) FROM `security_functions` WHERE `module` = 'Personal' AND `category` = 'Scholarships'");
        $parent_id = $this->fetchRow("SELECT MAX(`parent_id`) FROM `security_functions` WHERE `module` = 'Personal' AND `category` = 'Scholarships'");
        $parentId = $parent_id[0];
        $order = $row[0] + 1;

        $record = [
            [
                'name' => 'Overview',
                'controller' => 'Profiles',
                'module' => 'Personal',
                'category' => 'Scholarships',
                'parent_id' => $parentId,
                '_view' => 'Applications.index|Applications.view',
                '_edit' => NULL,
                '_add' => NULL,
                '_delete' => NULL,
                '_execute' => NULL,
                'order' => $order,  // No backticks here
                'visible' => 1,
                'description' => NULL,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->table('security_functions')->insert($record)->save();
        //update order
        $row = $this->fetchRow("SELECT MAX(`order`) FROM `security_functions` WHERE `module` = 'Personal' AND `category` = 'Scholarships'");
        $parent_id = $this->fetchRow("SELECT MAX(`parent_id`) FROM `security_functions` WHERE `module` = 'Personal' AND `category` = 'Scholarships'");
        $parentId = $parent_id[0];
        $order = $row[0] + 1;
        $record = [
            [
                'name' => 'Institution Choices',
                'controller' => 'Profiles',
                'module' => 'Personal',
                'category' => 'Scholarships',
                'parent_id' => $parentId,
                '_view' => 'ScholarshipApplicationInstitutionChoices.index|ScholarshipApplicationInstitutionChoices.view',
                '_edit' => 'ScholarshipApplicationInstitutionChoices.edit',
                '_add' => 'ScholarshipApplicationInstitutionChoices.add',
                '_delete' => 'ScholarshipApplicationInstitutionChoices.remove',
                '_execute' => NULL,
                'order' => $order,  // No backticks here
                'visible' => 1,
                'description' => NULL,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->table('security_functions')->insert($record)->save();

        // Update `order` for student curricular
        //update order
        $row = $this->fetchRow("SELECT MAX(`order`) FROM `security_functions` WHERE `module` = 'Personal' AND `category` = 'Scholarships'");
        $parent_id = $this->fetchRow("SELECT MAX(`parent_id`) FROM `security_functions` WHERE `module` = 'Personal' AND `category` = 'Scholarships'");
        $parentId = $parent_id[0];
        $order = $row[0] + 1;

        // Insert more data using CakePHP ORM
        $record = [
            [
                'name' => 'Attachments',
                'controller' => 'Profiles',
                'module' => 'Personal',
                'category' => 'Scholarships',
                'parent_id' => $parentId,
                '_view' => 'ScholarshipApplicationAttachments.index|ScholarshipApplicationAttachments.view',
                '_edit' => 'ScholarshipApplicationAttachments.edit',
                '_add' => 'ScholarshipApplicationAttachments.add',
                '_delete' => 'ScholarshipApplicationAttachments.remove',
                '_execute' => NULL,
                'order' => $order,  // No backticks here
                'visible' => 1,
                'description' => NULL,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->table('security_functions')->insert($record)->save();
    }

    // Rollback table
    public function down()
    {
        $this->execute('RENAME TABLE `z_8072_security_functions` TO `security_functions`');
        $this->execute('UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` > 478');
    }
}
