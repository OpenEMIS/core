<?php

use Phinx\Migration\AbstractMigration;

class POCOR2377 extends AbstractMigration
{
    // commit
    public function up()
    {
        // calendar_types
        $table = $this->table('calendar_types', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains types of calendar holiday'
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
            ->addColumn('is_institution', 'integer', [
                'limit' => 1,
                'default' => 0,
                'null' => false
            ])
            ->addColumn('is_attendance_required', 'integer', [
                'limit' => 1,
                'default' => 0,
                'null' => false
            ])
            ->save()
        ;

        // inserting multiple rows of data
        $data = [
            [
                'id' => 1,
                'code' => 'SCHOOLOPEN',
                'name' => 'School Open',
                'is_institution' => 1,
                'is_attendance_required' => 1
            ],
            [
                'id' => 2,
                'code' => 'SCHOOLCLOSED',
                'name' => 'School Closed',
                'is_institution' => 1,
                'is_attendance_required' => 0
            ],
            [
                'id' => 3,
                'code' => 'SCHOOLEVENT',
                'name' => 'School Event',
                'is_institution' => 1,
                'is_attendance_required' => 1
            ],
            [
                'id' => 4,
                'code' => 'PUBLICHOLIDAY',
                'name' => 'Public Holiday',
                'is_institution' => 0,
                'is_attendance_required' => 0
            ],
            [
                'id' => 5,
                'code' => 'SCHOOLHOLIDAY',
                'name' => 'School Holiday',
                'is_institution' => 0,
                'is_attendance_required' => 0
            ]
        ];

        $this->insert('calendar_types', $data);
        // end calendar_types

        // calendar_events
        $table = $this->table('calendar_events', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains event for the calendar'
        ]);
        $table
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
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
            ->addColumn('institution_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('calendar_type_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to calendar_types.id'
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
            ->addIndex('calendar_type_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save()
        ;
        // end calendar_events

        // calendar_event_dates
        $table = $this->table('calendar_event_dates', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains date of the event on the calendar'
        ]);
        $table
            ->addColumn('calendar_event_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to calendar_events.id'
            ])
            ->addColumn('date', 'date', [
                'null' => false
            ])
            ->save()
        ;
        // end calendar_event_dates

        // security_functions
        $data = [
            [
                'id' => '1080',
                'name' => 'Calendar',
                'controller' => 'InstitutionCalendars',
                'module' => 'Institutions',
                'category' => 'General',
                'parent_id' => 8,
                '_view' => 'index|view',
                '_edit' => 'edit',
                '_add' => 'add',
                '_delete' => 'delete',
                'order' => '1080',
                'visible' => 1,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '5081',
                'name' => 'Calendar',
                'controller' => 'Calendars',
                'module' => 'Administration',
                'category' => 'Calendar',
                'parent_id' => 8,
                '_view' => 'index|view',
                '_edit' => 'edit',
                '_add' => 'add',
                '_delete' => 'delete',
                'order' => '5081',
                'visible' => 1,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];

        $this->insert('security_functions', $data);
        // end security_functions
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE calendar_types');
        $this->execute('DROP TABLE calendar_events');
        $this->execute('DROP TABLE calendar_event_dates');

        $this->execute('DELETE FROM security_functions WHERE id = 1080');
        $this->execute('DELETE FROM security_functions WHERE id = 5081');
    }
}
