<?php
use Migrations\AbstractMigration;

class POCOR4256 extends AbstractMigration
{
   public function up()
    {
        // infrastructure_wash_waste_types
        $table = $this->table('infrastructure_wash_waste_types', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains types of infrastructure wash waste types'
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
        // end infrastructure_wash_waste_types

        // infrastructure_wash_waste_functionalities
        $table = $this->table('infrastructure_wash_waste_functionalities', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains types of infrastructure wash waste functionalities'
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
        // end infrastructure_wash_waste_functionalities

        // infrastructure_wash_wastes
        $table = $this->table('infrastructure_wash_wastes', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains infrastructure wastes'
            ]);
        $table
            ->addColumn('academic_period_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to academic_periods.id'
            ])
            ->addColumn('institution_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('infrastructure_wash_waste_type_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to infrastructure_wash_waste_types.id'
            ])
            ->addColumn('infrastructure_wash_waste_functionality_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to infrastructure_wash_waste_functionalities.id'
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
            ->addIndex('academic_period_id')
            ->addIndex('institution_id')
            ->addIndex('infrastructure_wash_waste_type_id')
            ->addIndex('infrastructure_wash_waste_functionality_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // end infrastructure_wash_wastes

        // security_functions
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 14');

        $this->insert('security_functions', [
            'id' => 1072,
            'name' => 'Infrastructure Wash Waste',
            'controller' => 'InfrastructureWashWastes',
            'module' => 'Institutions',
            'category' => 'Details',
            'parent_id' => 8,
            '_view' => 'index|view|download',
            '_edit' => 'edit',
            '_add' => 'add',
            '_delete' => 'delete',
            'order' => 15,
            'visible' => 1,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);
        // end security_functions
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE infrastructure_wash_waste_types');
        $this->execute('DROP TABLE infrastructure_wash_waste_functionalities');
        $this->execute('DROP TABLE infrastructure_wash_wastes');
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 14');
        $this->execute('DELETE FROM security_functions WHERE id = 1072');
    }
}
