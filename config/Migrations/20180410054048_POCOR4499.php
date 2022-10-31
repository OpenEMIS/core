<?php

use Phinx\Migration\AbstractMigration;

class POCOR4499 extends AbstractMigration
{
    public function up()
    {
        // insurance_providers
        $table = $this->table('insurance_providers', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains list of insurance providers'
        ]);

        $table
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // end insurance_providers

        // insurance_types
        $table = $this->table('insurance_types', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains list of insurance types'
            ]);

        $table
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('visible', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('editable', 'integer', [
                'default' => '1',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('default', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        //end insurance_types

        // user_insurances
        $table = $this->table('user_insurances', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains list of user insurance'
            ]);
        $table->addColumn('start_date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('end_date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('insurance_provider_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to insurance_providers.id'
            ])
            ->addColumn('insurance_type_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to insurance_types.id'
            ])
            ->addColumn('security_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex('insurance_provider_id')
            ->addIndex('insurance_type_id')
            ->addIndex('security_user_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')

            ->save();
        //end user_insurances

        // security_functions
        $row = $this->fetchRow('SELECT `order` FROM `security_functions` WHERE `id` = 3041');
        $order = $row['order'];

        $this->execute('UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` >= ' . $order);

        // insert permission for label
        $table = $this->table('security_functions');
        $data = [
            'id' => 3042,
            'name' => 'Staff Insurance',
            'controller' => 'StaffInsurances',
            'module' => 'Institutions',
            'category' => 'Staff - Health',
            'parent_id' => 3000,
            '_view' => 'index|view',
            '_edit' => 'edit',
            '_add' => 'add',
            '_delete' => 'delete',
            'order' => $order,
            'visible' => 1,
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
        ];
        $table->insert($data);

        $row = $this->fetchRow('SELECT `order` FROM `security_functions` WHERE `id` = 7055');
        $order = $row['order'];
        $this->execute('UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` >= '. $order);
        $data = [
            'id' => 7057,
            'name' => 'Insurances',
            'controller' => 'DirectoryInsurances',
            'module' => 'Directory',
            'category' => 'Health',
            'parent_id' => 7000,
            '_view' => 'index|view',
            '_edit' => 'edit',
            '_add' => 'add',
            '_delete' => 'delete',
            'order' => $order,
            'visible' => 1,
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
        ];
        $table->insert($data);

        $row = $this->fetchRow('SELECT `order` FROM `security_functions` WHERE `id` = 2035');
        $order = $row['order'];
        $this->execute('UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` >= '. $order);
        $data = [
            'id' => 2038,
            'name' => 'Student Insurance',
            'controller' => 'StudentInsurances',
            'module' => 'Institutions',
            'category' => 'Students - Health',
            'parent_id' => 2000,
            '_view' => 'index|view',
            '_edit' => 'edit',
            '_add' => 'add',
            '_delete' => 'delete',
            'order' => $order,
            'visible' => 1,
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
        ];
        $table->insert($data);
        $table->saveData();
        // end security_functions
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE `insurance_providers`');
        $this->execute('DROP TABLE `insurance_types`');
        $this->execute('DROP TABLE `user_insurances`');

        
        $row = $this->fetchRow('SELECT `order` FROM `security_functions` WHERE `id` = 3042');
        $order = $row['order'];
        $this->execute('UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` >= ' . $order);
        $this->execute('DELETE FROM security_functions WHERE id = 3042');

        
        $row = $this->fetchRow('SELECT `order` FROM `security_functions` WHERE `id` = 7057');
        $order = $row['order'];
        $this->execute('UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` >= ' . $order);
        $this->execute('DELETE FROM security_functions WHERE id = 7057');

        
        $row = $this->fetchRow('SELECT `order` FROM `security_functions` WHERE `id` = 2038');
        $order = $row['order'];
        $this->execute('UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` >= ' . $order);
        $this->execute('DELETE FROM security_functions WHERE id = 2038');
    }
}
