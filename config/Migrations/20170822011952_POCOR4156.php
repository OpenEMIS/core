<?php

use Phinx\Migration\AbstractMigration;

class POCOR4156 extends AbstractMigration
{
    // commit
    public function up()
    {
        // body_masses
        $table = $this->table('body_masses', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains the body mass of the user'
            ]);

        $table
            ->addColumn('date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('height', 'decimal', [
                'default' => null,
                'precision' => 5, // total digit
                'scale' => 2, // digit after decimal point
                'null' => false
            ])
            ->addColumn('weight', 'decimal', [
                'default' => null,
                'precision' => 5, // total digit
                'scale' => 2, // digit after decimal point
                'null' => false
            ])
            ->addColumn('body_mass_index', 'decimal', [
                'default' => null,
                'precision' => 5, // total digit
                'scale' => 2, // digit after decimal point
                'null' => true
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
            ->addColumn('user_id', 'integer', [
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
            ->addIndex('academic_period_id')
            ->addIndex('user_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // end of user_body_masses

        // security_functions
        $data = [
            'id' => '1061',
            'name' => 'Counselling',
            'controller' => 'Counsellings',
            'module' => 'Institutions',
            'category' => 'Students',
            'parent_id' => 8,
            '_view' => 'index|view|download',
            '_edit' => 'edit',
            '_add' => 'add',
            '_delete' => 'delete',
            'order' => '1061',
            'visible' => 1,
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
        ];

        $table = $this->table('security_functions');
        $table->insert($data);
        $table->saveData();
        // end security_functions
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE body_masses');
    }
}
