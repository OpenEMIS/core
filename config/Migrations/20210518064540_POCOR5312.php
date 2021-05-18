<?php
use Migrations\AbstractMigration;

class POCOR5312 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_5312_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_5312_security_functions` SELECT * FROM `security_functions`');  
            
        // security_functions
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 419');

        //insert 
        $record = [
            [
                'name' => 'Overview',
                'controller' => 'Profiles',
                'module' => 'Profile',
                'category' => 'General',
                'parent_id' => -1,
                '_view' => 'Profiles.index|Profiles.view',
                '_edit' => 'Profiles.edit',
                '_add' => 'Profiles.add',
                '_delete' => 'Profiles.remove',
                '_execute' => NULL,
                'order' => 420,
                'visible' => 1,
                'description' => NULL,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s'),
            ]
        ];

        $this->insert('security_functions', $record);

        $row = $this->fetchRow("SELECT `id` FROM `security_functions` WHERE `controller` = 'Profiles' AND
                `module` = 'Profile'");
        $parentId = $row['id'];

        $data = [
            [
                'name' => 'Accounts',
                'controller' => 'Profiles',
                'module' => 'Profile',
                'category' => 'General',
                'parent_id' => $parentId,
                '_view' => 'Accounts.view',
                '_edit' => 'Accounts.edit',
                '_add' => 'Accounts.add',
                '_delete' => 'Accounts.remove',
                '_execute' => NULL,
                'order' => 421,
                'visible' => 1,
                'description' => NULL,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Demographic',
                'controller' => 'Profiles',
                'module' => 'Profile',
                'category' => 'General',
                'parent_id' => $parentId,
                '_view' => 'Demographic.index|Demographic.view',
                '_edit' => 'Demographic.edit',
                '_add' => 'Demographic.add',
                '_delete' => 'Demographic.remove',
                '_execute' => NULL,
                'order' => 422,
                'visible' => 1,
                'description' => NULL,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'Identities',
                'controller' => 'Profiles',
                'module' => 'Profile',
                'category' => 'General',
                'parent_id' => $parentId,
                '_view' => 'Identities.index|Identities.view',
                '_edit' => 'Identities.edit',
                '_add' => 'Identities.add',
                '_delete' => 'Identities.remove',
                '_execute' => NULL,
                'order' => 423,
                'visible' => 1,
                'description' => NULL,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'Nationalities',
                'controller' => 'Profiles',
                'module' => 'Profile',
                'category' => 'General',
                'parent_id' => $parentId,
                '_view' => 'Nationalities.index|Nationalities.view',
                '_edit' => 'Nationalities.edit',
                '_add' => 'Nationalities.add',
                '_delete' => 'Nationalities.remove',
                '_execute' => NULL,
                'order' => 424,
                'visible' => 1,
                'description' => NULL,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'Contacts',
                'controller' => 'Profiles',
                'module' => 'Profile',
                'category' => 'General',
                'parent_id' => $parentId,
                '_view' => 'Contacts.index|Contacts.view',
                '_edit' => 'Contacts.edit',
                '_add' => 'Contacts.add',
                '_delete' => 'Contacts.remove',
                '_execute' => NULL,
                'order' => 425,
                'visible' => 1,
                'description' => NULL,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'Languages',
                'controller' => 'Profiles',
                'module' => 'Profile',
                'category' => 'General',
                'parent_id' => $parentId,
                '_view' => 'Languages.index|Languages.view',
                '_edit' => 'Languages.edit',
                '_add' => 'Languages.add',
                '_delete' => 'Languages.remove',
                '_execute' => NULL,
                'order' => 426,
                'visible' => 1,
                'description' => NULL,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'Attachments',
                'controller' => 'Profiles',
                'module' => 'Profile',
                'category' => 'General',
                'parent_id' => $parentId,
                '_view' => 'Attachments.index|Attachments.view',
                '_edit' => 'Attachments.edit',
                '_add' => 'Attachments.add',
                '_delete' => 'Attachments.remove',
                '_execute' => NULL,
                'order' => 427,
                'visible' => 1,
                'description' => NULL,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'Comments',
                'controller' => 'Profiles',
                'module' => 'Profile',
                'category' => 'General',
                'parent_id' => $parentId,
                '_view' => 'ProfileComments.index|ProfileComments.view',
                '_edit' => 'ProfileComments.edit',
                '_add' => 'ProfileComments.add',
                '_delete' => 'ProfileComments.remove',
                '_execute' => NULL,
                'order' => 428,
                'visible' => 1,
                'description' => NULL,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'History',
                'controller' => 'Profiles',
                'module' => 'Profile',
                'category' => 'General',
                'parent_id' => $parentId,
                '_view' => 'History.index|History.view',
                '_edit' => 'History.edit',
                '_add' => 'History.add',
                '_delete' => 'History.remove',
                '_execute' => NULL,
                'order' => 429,
                'visible' => 1,
                'description' => NULL,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s'),
            ]
        ];

        $this->insert('security_functions', $data);
    }

    // rollback
    public function down()
    {
        $this->execute('RENAME TABLE `z_5312_security_functions` TO `security_functions`');
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 419');  
    }
}
