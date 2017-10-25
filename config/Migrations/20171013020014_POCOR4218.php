<?php
use Phinx\Migration\AbstractMigration;

class POCOR4218 extends AbstractMigration
{
    // commit
    public function up()
    {
        // institution_transport_providers
        $table = $this->table('institution_transport_providers', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains list of transport providers manage by school'
        ]);

        $table
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false
            ])
            ->addColumn('address', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('email', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true
            ])
            ->addColumn('contact_number', 'string', [
                'default' => null,
                'limit' => 15,
                'null' => false
            ])
            ->addColumn('registration_number', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true
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
            ->addIndex('institution_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // end institution_transport_providers

        // institution_buses
        $table = $this->table('institution_buses', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains list of buses operate by transport providers from school'
        ]);

        $table
            ->addColumn('plate_number', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false
            ])
            ->addColumn('capacity', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => true
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('institution_transport_provider_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_transport_providers.id'
            ])
            ->addColumn('bus_type_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to bus_types.id'
            ])
            ->addColumn('transport_status_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to transport_statuses.id'
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
            ->addIndex('institution_transport_provider_id')
            ->addIndex('bus_type_id')
            ->addIndex('transport_status_id')
            ->addIndex('institution_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // end institution_buses

        // bus_types
        $table = $this->table('bus_types', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains types of buses'
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
        // end bus_types

        // transport_statuses
        $table = $this->table('transport_statuses', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains the fixed list of statuses for transport'
            ]);

        $table
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false
            ])
            ->save();

        $data = [
            [
                'id' => 1,
                'code' => 'OPERATING',
                'name' => 'Operating'
            ],
            [
                'id' => 2,
                'code' => 'NOT_OPERATING',
                'name' => 'Not Operating'
            ]
        ];

        $this->insert('transport_statuses', $data);
        // end transport_statuses

        // transport_features
        $table = $this->table('transport_features', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains features of transport'
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
        // end transport_features

        // institution_buses_transport_features
        $table = $this->table('institution_buses_transport_features', [
            'id' => false,
            'primary_key' => [
                'institution_bus_id',
                'transport_feature_id',
            ],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the transport_features for the buses'
        ]);

        $table
            ->addColumn('id', 'char', [
                'default' => null,
                'limit' => 64,
                'null' => false
            ])
            ->addColumn('institution_bus_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_buses.id'
            ])
            ->addColumn('transport_feature_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to transport_features.id'
            ])
            ->addIndex('institution_bus_id')
            ->addIndex('transport_feature_id')
            ->save();
        // end institution_buses_transport_features

        // institution_trips
        $table = $this->table('institution_trips', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains list of trips for school'
        ]);

        $table
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false
            ])
            ->addColumn('repeat', 'integer', [
                'default' => 0,
                'limit' => 1,
                'null' => false
            ])
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
            ->addColumn('trip_type_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to trip_types.id'
            ])
            ->addColumn('institution_transport_provider_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_transport_providers.id'
            ])
            ->addColumn('institution_bus_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_buses.id'
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
            ->addIndex('academic_period_id')
            ->addIndex('trip_type_id')
            ->addIndex('institution_transport_provider_id')
            ->addIndex('institution_bus_id')
            ->addIndex('institution_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // end institution_trips

        // trip_types
        $table = $this->table('trip_types', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains types of trips'
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
        // end trip_types

        // institution_trip_days
        $table = $this->table('institution_trip_days', [
            'id' => false,
            'primary_key' => [
                'institution_trip_id',
                'day',
            ],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the days for trips'
        ]);

        $table
            ->addColumn('id', 'char', [
                'default' => null,
                'limit' => 36,
                'null' => false
            ])
            ->addColumn('institution_trip_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_trips.id'
            ])
            ->addColumn('day', 'integer', [
                'default' => null,
                'limit' => 1,
                'null' => false
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
            ->addIndex('institution_trip_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // end institution_trip_days

        // institution_trip_passengers
        $table = $this->table('institution_trip_passengers', [
            'id' => false,
            'primary_key' => [
                'student_id',
                'education_grade_id',
                'academic_period_id',
                'institution_id',
                'institution_trip_id',
            ],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the passengers for trips'
        ]);

        $table
            ->addColumn('id', 'char', [
                'default' => null,
                'limit' => 36,
                'null' => false
            ])
            ->addColumn('student_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('education_grade_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to education_grades.id'
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
            ->addColumn('institution_trip_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_trips.id'
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
            ->addIndex('student_id')
            ->addIndex('education_grade_id')
            ->addIndex('academic_period_id')
            ->addIndex('institution_id')
            ->addIndex('institution_trip_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // end institution_trip_passengers

        // security_functions
        $data = [
            [
                'id' => 1069,
                'name' => 'Providers',
                'controller' => 'InstitutionTransportProviders',
                'module' => 'Institutions',
                'category' => 'Transport',
                'parent_id' => 1000,
                '_view' => 'index|view',
                '_edit' => 'edit',
                '_add' => 'add',
                '_delete' => 'delete',
                'order' => 70,
                'visible' => 1,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 1070,
                'name' => 'Buses',
                'controller' => 'InstitutionBuses',
                'module' => 'Institutions',
                'category' => 'Transport',
                'parent_id' => 1000,
                '_view' => 'index|view',
                '_edit' => 'edit',
                '_add' => 'add',
                '_delete' => 'delete',
                'order' => 71,
                'visible' => 1,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 1071,
                'name' => 'Trips',
                'controller' => 'InstitutionTrips',
                'module' => 'Institutions',
                'category' => 'Transport',
                'parent_id' => 1000,
                '_view' => 'index|view',
                '_edit' => 'edit',
                '_add' => 'add',
                '_delete' => 'delete',
                'order' => 72,
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
        $this->execute('DROP TABLE `institution_transport_providers`');
        $this->execute('DROP TABLE `institution_buses`');
        $this->execute('DROP TABLE `bus_types`');
        $this->execute('DROP TABLE `transport_statuses`');
        $this->execute('DROP TABLE `transport_features`');
        $this->execute('DROP TABLE `institution_buses_transport_features`');

        $this->execute('DROP TABLE `institution_trips`');
        $this->execute('DROP TABLE `trip_types`');
        $this->execute('DROP TABLE `institution_trip_days`');
        $this->execute('DROP TABLE `institution_trip_passengers`');

        $this->execute('DELETE FROM `security_functions` WHERE `id` = 1069');
        $this->execute('DELETE FROM `security_functions` WHERE `id` = 1070');
        $this->execute('DELETE FROM `security_functions` WHERE `id` = 1071');
    }
}
