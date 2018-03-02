<?php

use Phinx\Migration\AbstractMigration;

class POCOR4214 extends AbstractMigration
{
    // commit
    public function up()
    {
        // infrastructure_need_types
        $table = $this->table('infrastructure_need_types', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains types of infrastructure need types'
            ]);
        $table
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false
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
        // end infrastructure_need_types

        // infrastructure_needs
        $table = $this->table('infrastructure_needs', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains infrastructure needs'
            ]);
        $table
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('date_determined', 'date', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('date_started', 'date', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('date_completed', 'date', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('file_name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true
            ])
            ->addColumn('file_content', 'blob', [
                'limit' => '4294967295',
                'default' => null,
                'null' => true
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('infrastructure_need_type_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to infrastructure_need_types.id'
            ])
            ->addColumn('priority', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => '1 => High, 2 => Medium, 3 => Low'
            ])
            ->addColumn('institution_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
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
            ->addIndex('priority')
            ->addIndex('infrastructure_need_type_id')
            ->addIndex('institution_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // end infrastructure_needs

        // security_functions
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 11');

        $this->insert('security_functions', [
            'id' => 1063,
            'name' => 'Infrastructure Need',
            'controller' => 'InfrastructureNeeds',
            'module' => 'Institutions',
            'category' => 'Details',
            'parent_id' => 8,
            '_view' => 'index|view|download',
            '_edit' => 'edit',
            '_add' => 'add',
            '_delete' => 'delete',
            'order' => 12,
            'visible' => 1,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);
        // end security_functions
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE infrastructure_need_types');
        $this->execute('DROP TABLE infrastructure_needs');
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 11');
        $this->execute('DELETE FROM security_functions WHERE id = 1063');
    }
}
