<?php

use Phinx\Migration\AbstractMigration;

class POCOR4644 extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('inserted_records', [
            'id' => false,
            'primary_key' => [
                'id',
                'inserted_date',
            ],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains data of previously inserted records"'
        ]);
        $table
            ->addColumn('id', 'biginteger', [
                'identity' => true,
                'default' => null,
                'limit' => 20,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('inserted_date', 'integer', [
                'default' => null,
                'limit' => 8,
                'null' => false,
            ])
            ->addColumn('reference_table', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('reference_key', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('data', 'text', [
                'default' => null,
                'limit' => 16777215,
                'null' => false,
            ])
            ->addColumn('action_type', 'string', [
                'comment' => 'DEFAULT, IMPORTED, FIRST_PARTY, THIRD_PARTY',
                'default' => null,
                'limit' => 100,
                'null' => false,
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
            ->addIndex('reference_table')
            ->addIndex('inserted_date')
            ->addIndex('created_user_id')
            ->save();

        $this->insert('api_securities', [
            'id' => 1003,
            'name' => 'Student Admission',
            'model' => 'Institution.StudentAdmission',
            'index' => 1,
            'view' => 1,
            'add' => 1,
            'edit' => 0,
            'delete' => 0,
            'execute' => 0
        ]);    
    }

    public function down()
    {
        $this->dropTable('inserted_records');
        $this->execute('DELETE FROM api_securities WHERE id = 1003');
    }
}
