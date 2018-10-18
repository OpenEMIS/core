<?php
use Phinx\Migration\AbstractMigration;

class POCOR4634 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `z_4634_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_4634_security_functions` SELECT * FROM `security_functions`');

        $this->execute('UPDATE security_functions SET `order` = `order` + 8 WHERE `order` > 152');

       $data = [
            [
                'id' => 2049,
                'name' => 'Guardian Accounts',
                'controller' => 'Guardians',
                'module' => 'Institutions',
                'category' => 'Students - Guardians',
                'parent_id' => 2000,
                '_view' => 'Accounts.view',
                '_edit' => 'Accounts.edit',
                '_add' => 'Accounts.add',
                '_delete' => null,
                'order' => 159,
                'visible' => 1,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2050,
                'name' => 'Guardian Identities',
                'controller' => 'Guardians',
                'module' => 'Institutions',
                'category' => 'Students - Guardians',
                'parent_id' => 2000,
                '_view' => 'Identities.index|Identities.view',
                '_edit' => 'Identities.edit',
                '_add' => 'Identities.add',
                '_delete' => 'Identities.remove',
                'order' => 154,
                'visible' => 1,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2051,
                'name' => 'Guardian Nationalities',
                'controller' => 'Guardians',
                'module' => 'Institutions',
                'category' => 'Students - Guardians',
                'parent_id' => 2000,
                '_view' => 'Nationalities.index|Nationalities.view',
                '_edit' => 'Nationalities.edit',
                '_add' => 'Nationalities.add',
                '_delete' => 'Nationalities.remove',
                'order' => 155,
                'visible' => 1,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2052,
                'name' => 'Guardian Contacts',
                'controller' => 'Guardians',
                'module' => 'Institutions',
                'category' => 'Students - Guardians',
                'parent_id' => 2000,
                '_view' => 'Contacts.index|Contacts.view',
                '_edit' => 'Contacts.edit',
                '_add' => 'Contacts.add',
                '_delete' => 'Contacts.remove',
                'order' => 153,
                'visible' => 1,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2053,
                'name' => 'Guardian Languages',
                'controller' => 'Guardians',
                'module' => 'Institutions',
                'category' => 'Students - Guardians',
                'parent_id' => 2000,
                '_view' => 'Languages.index|Languages.view',
                '_edit' => 'Languages.edit',
                '_add' => 'Languages.add',
                '_delete' => 'Languages.remove',
                'order' => 156,
                'visible' => 1,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2054,
                'name' => 'Guardian Attachments',
                'controller' => 'Guardians',
                'module' => 'Institutions',
                'category' => 'Students - Guardians',
                'parent_id' => 2000,
                '_view' => 'Attachments.index|Attachments.view',
                '_edit' => 'Attachments.edit',
                '_add' => 'Attachments.add',
                '_delete' => 'Attachments.remove',
                '_execute' => 'Attachments.download',
                'order' => 158,
                'visible' => 1,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2055,
                'name' => 'Guardian Comments',
                'controller' => 'GuardianComments',
                'module' => 'Institutions',
                'category' => 'Students - Guardians',
                'parent_id' => 2000,
                '_view' => 'index|view',
                '_edit' => 'edit',
                '_add' => 'add',
                '_delete' => 'delete',
                'order' => 157,
                'visible' => 1,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2056,
                'name' => 'Guardian Demographic',
                'controller' => 'Guardians',
                'module' => 'Institutions',
                'category' => 'Students - Guardians',
                'parent_id' => 2000,
                '_view' => 'Demographic.index|Demographic.view',
                '_edit' => 'Demographic.edit',
                '_add' => 'Demographic.add',
                '_delete' => null,
                'order' => 152,
                'visible' => 1,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],            
        ];

        $this->insert('security_functions', $data);
    }

    public function down()
    {
        $this->dropTable('security_functions');
        $this->table('z_4634_security_functions')->rename('security_functions');
    }    
}
