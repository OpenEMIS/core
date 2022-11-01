<?php
use Phinx\Migration\AbstractMigration;

class POCOR3594 extends AbstractMigration
{
    public function up()
    {
        // demographic_types
        $demographicTypesTable = $this->table('demographic_types', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains types of demographics'
            ]);

        $demographicTypesTable
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false
            ])
            ->addColumn('visible', 'integer', [
                'default' => 1,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('editable', 'integer', [
                'default' => 1,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('default', 'integer', [
                'default' => 0,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // end demographic_types

       $data = [
            [
                'id' => '1',
                'name' => '1',
                'description' => '1',
                'order' => '1',
                'visible' => '1',
                'default' => '0',
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '2',
                'name' => '2',
                'description' => '2',
                'order' => '2',
                'visible' => '1',
                'default' => '0',
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '3',
                'name' => '3',
                'description' => '3',
                'order' => '3',
                'visible' => '1',
                'default' => '0',
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '4',
                'name' => '4',
                'description' => '4',
                'order' => '4',
                'visible' => '1',
                'default' => '0',
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '5',
                'name' => '5',
                'description' => '5',
                'order' => '5',
                'visible' => '1',
                'default' => '0',
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
        ];

        // this is a handy shortcut (phinx documentation, http://docs.phinx.org/en/latest/migrations.html)
        $this->insert('demographic_types', $data);

        $table = $this->table('user_demographics');

        $table
            ->addColumn('demographic_types_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to demographic_types.id'
            ])
            ->addColumn('indigenous', 'integer', [
                'comment' => '0-No / 1-Yes / 2-Unknown',
                'default' => null,
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('security_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('demographic_types_id')
            ->addIndex('security_user_id')
            ->save();

        $this->execute('CREATE TABLE `z_3594_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_3594_security_functions` SELECT * FROM `security_functions`');

        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 114');
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 155');
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 291');

       $data = [
            [
                'id' => 2048,
                'name' => 'Demographic',
                'controller' => 'Students',
                'module' => 'Institutions',
                'category' => 'Students - General',
                'parent_id' => 2000,
                '_view' => 'Demographic.index|Demographic.view',
                '_edit' => 'Demographic.edit',
                '_add' => 'Demographic.add',
                '_delete' => null,
                'order' => 115,
                'visible' => 1,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 3055,
                'name' => 'Demographic',
                'controller' => 'Staff',
                'module' => 'Institutions',
                'category' => 'Staff - General',
                'parent_id' => 3000,
                '_view' => 'Demographic.index|Demographic.view',
                '_edit' => 'Demographic.edit',
                '_add' => 'Demographic.add',
                '_delete' => null,
                'order' => 156,
                'visible' => 1,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 7068,
                'name' => 'Demographic',
                'controller' => 'Directories',
                'module' => 'Directory',
                'category' => 'General',
                'parent_id' => 7000,
                '_view' => 'Demographic.index|Demographic.view',
                '_edit' => 'Demographic.edit',
                '_add' => 'Demographic.add',
                '_delete' => null,
                'order' => 292,
                'visible' => 1,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
        ];

        $this->insert('security_functions', $data);
    }

    public function down()
    {
        $this->execute('DROP TABLE `demographic_types`');
        $this->execute('DROP TABLE `user_demographics`');
        $this->dropTable('security_functions');
        $this->table('z_3594_security_functions')->rename('security_functions');
    }    
}
