<?php
use Migrations\AbstractMigration;
use Cake\Utility\Text;

class POCOR8072 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */

    public function up()
    {

        //backup
        $this->execute('CREATE TABLE `z_8072_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_8072_security_functions` SELECT * FROM `security_functions`'); 

        // security_functions Set Permission
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 478');
        //insert data in security function
        $record = [
            [
                'name' => 'Institution Choices', 'controller' => 'Profiles', 'module' => 'Personal', 'category' => 'Scholarships', 'parent_id' => 9030,'_view' => 'InstitutionChoicesScholarship.index|InstitutionChoicesScholarship.view', '_edit' => 'InstitutionChoicesScholarship.edit', '_add' => 'InstitutionChoicesScholarship.add', '_delete' => 'InstitutionChoicesScholarship.remove', '_execute' => NULL, 'order' => 479, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];
        $this->insert('security_functions', $record);

        // security_functions for student curricular
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 480');
        //insert 
        $record = [
            [
                'name' => 'Scholarship Attachments', 'controller' => 'Profiles', 'module' => 'Personal', 'category' => 'Scholarships', 'parent_id' => 9030,'_view' => 'ScholarshipAttachments.index|ScholarshipAttachments.view', '_edit' => 'ScholarshipAttachments.edit', '_add' => 'ScholarshipAttachments.add', '_delete' => 'ScholarshipAttachments.remove', '_execute' => NULL, 'order' => 481, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];
        $this->insert('security_functions', $record);

    }

    // rollback
    public function down()
    {
        $this->execute('RENAME TABLE `z_8072_security_functions` TO `security_functions`');
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 478'); 
    }
}
