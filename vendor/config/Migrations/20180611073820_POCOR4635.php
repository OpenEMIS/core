<?php

use Phinx\Migration\AbstractMigration;

class POCOR4635 extends AbstractMigration
{
    public function up()
    {
        // asset_types
        $table = $this->table('asset_types', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This is a field option table containing the list of user-defined asset types used by institution assets'
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

        // asset_purposes
        $table = $this->table('asset_purposes', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This is a field option table containing the list of user-defined asset purposes used by institution assets'
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

        // asset_conditions
        $table = $this->table('asset_conditions', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This is a field option table containing the list of user-defined asset conditions used by institution assets'
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

        // asset_statuses
        $table = $this->table('asset_statuses', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains a fixed list of statuses for assets'
        ]);
        $table
            ->addColumn('code', 'string', [
                'null' => false,
                'limit' => 100
            ])
            ->addColumn('name', 'string', [
                'null' => false,
                'limit' => 250
            ])
            ->save();

        $statuses = [
            [
                'code' => 'IN_USE',
                'name' => 'In Use'
            ],
            [
                'code' => 'END_OF_USAGE',
                'name' => 'End of Usage'
            ]
        ];
        $this->insert('asset_statuses', $statuses);

        // institution_assets
        $table = $this->table('institution_assets', [
            'id' => false,
            'primary_key' => ['id', 'academic_period_id'],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all assets used by institutions'
        ]);
        $table
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => 11,
                'identity' => true
            ])
            ->addColumn('academic_period_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to academic_periods.id'
            ])
            ->addColumn('code', 'string', [
                'null' => false,
                'limit' => 50
            ])
            ->addColumn('name', 'string', [
                'null' => false,
                'limit' => 250
            ])
            ->addColumn('accessibility', 'integer', [
                'null' => false,
                'limit' => 1,
                'comment' => '0 -> Not Accessible, 1 -> Accessible'
            ])
            ->addColumn('institution_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('asset_status_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to asset_statuses.id'
            ])
            ->addColumn('asset_type_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to asset_types.id'
            ])
            ->addColumn('asset_purpose_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to asset_purposes.id'
            ])
            ->addColumn('asset_condition_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to asset_conditions.id'
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
            ->addIndex('asset_status_id')
            ->addIndex('asset_type_id')
            ->addIndex('asset_purpose_id')
            ->addIndex('asset_condition_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // security_functions
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 102');

        $securityFunctions = [
            [
                'id' => 3044,
                'name' => 'Assets',
                'controller' => 'InstitutionAssets',
                'module' => 'Institutions',
                'category' => 'Assets',
                'parent_id' => 1000,
                '_view' => 'index|view',
                '_edit' => 'edit',
                '_add' => 'add',
                '_delete' => 'delete',
                'order' => 103,
                'visible' => 1,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('security_functions', $securityFunctions);
    }

    public function down()
    {
        $this->dropTable('asset_types');
        $this->dropTable('asset_purposes');
        $this->dropTable('asset_conditions');
        $this->dropTable('asset_statuses');
        $this->dropTable('institution_assets');

        $this->execute('DELETE FROM security_functions WHERE `id` = 3044');
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 102');
    }
}
