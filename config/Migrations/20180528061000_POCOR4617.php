<?php

use Phinx\Migration\AbstractMigration;

class POCOR4617 extends AbstractMigration
{
    public function up()
    {
        // institution_committees
        $table = $this->table('institution_committees', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains list of institution committees'
        ]);

        $table
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('institution_committee_type_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_committee_types.id'
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
            ->addColumn('meeting_date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('start_time', 'time', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('end_time', 'time', [
                'default' => null,
                'limit' => null,
                'null' => true,
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
            ->addIndex('institution_committee_type_id')
            ->addIndex('academic_period_id')
            ->addIndex('institution_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // end institution_committees

        // institution_committee_types
        $table = $this->table('institution_committee_types', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains list of institution committee types'
            ]);

        $table
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
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


        $data = [
            ['name' => 'School Board Of Management (BOM)',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
            ],
            [
            'name' => "Parents and Citizens' Association (P&C)",
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
            ],
            [
            'name' => 'Staff Meeting',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
            ],
            [
            'name' => 'Parent Meeting',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
            ],
            [
            'name' => 'PTA committee',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
            ],
            [
            'name' => 'Hygiene committee',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
            ]
        ];
        $table->insert($data);
        $table->saveData();
        //end institution_committee_types

        // institution_committees
        // $table = $this->table('institution_committees', [
        //     'collation' => 'utf8mb4_unicode_ci',
        //     'comment' => 'This table contains list of committees for institutions'
        // ]);

        // $table
        //     ->addColumn('committee_id', 'integer', [
        //         'default' => null,
        //         'limit' => 11,
        //         'null' => false,
        //         'comment' => 'links to committees.id'
        //     ])
        //     ->addColumn('institution_id', 'integer', [
        //         // 'default' => null,
        //         'limit' => 11,
        //         'null' => false,
        //         'comment' => 'links to institutions.id'
        //     ])
        //     ->addColumn('modified_user_id', 'integer', [
        //         'default' => null,
        //         'limit' => 11,
        //         'null' => true,
        //     ])
        //     ->addColumn('modified', 'datetime', [
        //         'default' => null,
        //         'limit' => null,
        //         'null' => true,
        //     ])
        //     ->addColumn('created_user_id', 'integer', [
        //         'default' => null,
        //         'limit' => 11,
        //         'null' => false,
        //     ])
        //     ->addColumn('created', 'datetime', [
        //         'default' => null,
        //         'limit' => null,
        //         'null' => false,
        //     ])
        //     ->addIndex('committee_id')
        //     ->addIndex('institution_id')
        //     ->addIndex('modified_user_id')
        //     ->addIndex('created_user_id')
        //     ->save();
        // end institution_committees

       // committee_attachments
        $table = $this->table('institution_committee_attachments', [
            'comment' => 'This table contains the list of attachments linked to specific committee'
        ]);

        $table
            ->addColumn('file_name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false
            ])
            ->addColumn('file_content', 'blob', [
                'limit' => '4294967295',
                'default' => null,
                'null' => false
            ])
            ->addColumn('institution_committee_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_committees.id'
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
            ->addIndex('institution_committee_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // 
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE `institution_committees`');
        $this->execute('DROP TABLE `institution_committee_types`');
        $this->execute('DROP TABLE `institution_committee_attachments`');
        // $this->execute('DROP TABLE `institution_committees`');
    }
}
