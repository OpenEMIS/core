<?php
use Migrations\AbstractMigration;

class POCOR4254 extends AbstractMigration
{
    // commit
    public function up()
    {
        // infrastructure_wash_sanitation_types
        $table = $this->table('infrastructure_wash_sanitation_types', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains types of infrastructure wash sanitation types'
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
        // end infrastructure_wash_sanitation_types

        // infrastructure_wash_sanitation_uses
        $table = $this->table('infrastructure_wash_sanitation_uses', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains types of infrastructure wash sanitation uses'
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
        // end infrastructure_wash_sanitation_uses

        // infrastructure_wash_sanitation_qualities
        $table = $this->table('infrastructure_wash_sanitation_qualities', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains types of infrastructure wash sanitation qualities'
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
        // end infrastructure_wash_sanitation_qualities

        // infrastructure_wash_sanitation_accessibilities
        $table = $this->table('infrastructure_wash_sanitation_accessibilities', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains types of infrastructure wash sanitation accessibilities'
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
        // end infrastructure_wash_sanitation_accessibilities

        // infrastructure_wash_sanitations
        $table = $this->table('infrastructure_wash_sanitations', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains infrastructure sanitations'
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
            ->addColumn('infrastructure_wash_sanitation_type_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to infrastructure_wash_sanitation_types.id'
            ])
            ->addColumn('infrastructure_wash_sanitation_use_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to infrastructure_wash_sanitation_uses.id'
            ])
            ->addColumn('infrastructure_wash_sanitation_total_male', 'integer', [
                'default' => 0,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('infrastructure_wash_sanitation_total_female', 'integer', [
                'default' => 0,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('infrastructure_wash_sanitation_total_mixed', 'integer', [
                'default' => 0,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('infrastructure_wash_sanitation_quality_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to infrastructure_wash_sanitation_qualities.id'
            ])
            ->addColumn('infrastructure_wash_sanitation_accessibility_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to infrastructure_wash_sanitation_accessibilities.id'
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
            ->addIndex('infrastructure_wash_sanitation_type_id')
            ->addIndex('infrastructure_wash_sanitation_use_id')
            ->addIndex('infrastructure_wash_sanitation_quality_id')
            ->addIndex('infrastructure_wash_sanitation_accessibility_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // end infrastructure_wash_sanitations

        // infrastructure_wash_sanitation_quantities
        $table = $this->table('infrastructure_wash_sanitation_quantities', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains details of infrastructure wash sanitation quantities'
            ]);
        $table
            ->addColumn('gender_id', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('functional', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('value', 'integer', [
                'default' => 0,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('infrastructure_wash_sanitation_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to infrastructure_wash_sanitations.id'
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
            ->addIndex('infrastructure_wash_sanitation_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // end infrastructure_wash_sanitation_quantities

        // security_functions
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 14');

        $this->insert('security_functions', [
            'id' => 1074,
            'name' => 'Infrastructure WASH Sanitation',
            'controller' => 'InfrastructureWashSanitations',
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
        $this->execute('DROP TABLE infrastructure_wash_sanitation_types');
        $this->execute('DROP TABLE infrastructure_wash_sanitation_uses');
        $this->execute('DROP TABLE infrastructure_wash_sanitation_qualities');
        $this->execute('DROP TABLE infrastructure_wash_sanitation_accessibilities');
        $this->execute('DROP TABLE infrastructure_wash_sanitations');
        $this->execute('DROP TABLE infrastructure_wash_sanitation_quantities');
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 14');
        $this->execute('DELETE FROM security_functions WHERE id = 1074');
    }
}
