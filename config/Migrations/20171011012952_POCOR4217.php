<?php

use Phinx\Migration\AbstractMigration;

class POCOR4217 extends AbstractMigration
{
    // commit
    public function up()
    {
        // utility_telephone_types
        $table = $this->table('utility_telephone_types', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains types of telephone utilities'
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
        // end utility_telephone_types

        // utility_telephone_conditions
        $table = $this->table('utility_telephone_conditions', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains conditions of telephone utilities'
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
        // end utility_telephone_conditions

        // infrastructure_utility_telephones
        $table = $this->table('infrastructure_utility_telephones', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains utilities for telephone'
            ]);
        $table
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true
            ])
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
            ->addColumn('utility_telephone_type_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to utility_telephone_types.id'
            ])
            ->addColumn('utility_telephone_condition_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to utility_telephone_conditions.id'
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
            ->addIndex('utility_telephone_type_id')
            ->addIndex('utility_telephone_condition_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // end infrastructure_utility_telephones

        // utility_internet_types
        $table = $this->table('utility_internet_types', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains types of internet utilities'
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
        // end utility_internet_types

        // utility_internet_conditions
        $table = $this->table('utility_internet_conditions', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains conditions of internet utilities'
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
        // end utility_internet_conditions

        // infrastructure_utility_internets
        $table = $this->table('infrastructure_utility_internets', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains utilities for internet'
            ]);
        $table
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('internet_purpose', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => '1 => Teaching, 2 => Non-Teaching'
            ])
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
            ->addColumn('utility_internet_type_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to utility_internet_types.id'
            ])
            ->addColumn('utility_internet_condition_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to utility_internet_conditions.id'
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
            ->addIndex('utility_internet_type_id')
            ->addIndex('utility_internet_condition_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // end infrastructure_utility_internets

        // utility_electricity_types
        $table = $this->table('utility_electricity_types', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains types of electricity utilities'
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
        // end utility_electricity_types

        // utility_electricity_conditions
        $table = $this->table('utility_electricity_conditions', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains conditions of electricity utilities'
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
        // end utility_electricity_conditions

        // infrastructure_utility_electricities
        $table = $this->table('infrastructure_utility_electricities', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains utilities for electricity'
            ]);
        $table
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true
            ])
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
            ->addColumn('utility_electricity_type_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to utility_electricity_types.id'
            ])
            ->addColumn('utility_electricity_condition_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to utility_electricity_conditions.id'
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
            ->addIndex('utility_electricity_type_id')
            ->addIndex('utility_electricity_condition_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // end infrastructure_utility_electricities

        // security_functions
        $this->execute('UPDATE security_functions SET `order` = `order` + 3 WHERE `order` > 14');

        $data = [
            [
                'id' => 1066,
                'name' => 'Infrastructure Utility Electricity',
                'controller' => 'InfrastructureUtilityElectricities',
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
            ],
            [
                'id' => 1067,
                'name' => 'Infrastructure Utility Internet',
                'controller' => 'InfrastructureUtilityInternets',
                'module' => 'Institutions',
                'category' => 'Details',
                'parent_id' => 8,
                '_view' => 'index|view|download',
                '_edit' => 'edit',
                '_add' => 'add',
                '_delete' => 'delete',
                'order' => 16,
                'visible' => 1,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 1068,
                'name' => 'Infrastructure Utility Telephone',
                'controller' => 'InfrastructureUtilityTelephones',
                'module' => 'Institutions',
                'category' => 'Details',
                'parent_id' => 8,
                '_view' => 'index|view|download',
                '_edit' => 'edit',
                '_add' => 'add',
                '_delete' => 'delete',
                'order' => 17,
                'visible' => 1,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];

        $this->insert('security_functions', $data);
        // end security_functions
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE infrastructure_utility_electricities');
        $this->execute('DROP TABLE utility_electricity_conditions');
        $this->execute('DROP TABLE utility_electricity_types');

        $this->execute('DROP TABLE infrastructure_utility_internets');
        $this->execute('DROP TABLE utility_internet_conditions');
        $this->execute('DROP TABLE utility_internet_types');

        $this->execute('DROP TABLE infrastructure_utility_telephones');
        $this->execute('DROP TABLE utility_telephone_conditions');
        $this->execute('DROP TABLE utility_telephone_types');

        $this->execute('UPDATE security_functions SET `order` = `order` - 3 WHERE `order` > 14');
        $this->execute('DELETE FROM security_functions WHERE id = 1066');
        $this->execute('DELETE FROM security_functions WHERE id = 1067');
        $this->execute('DELETE FROM security_functions WHERE id = 1068');
    }
}
