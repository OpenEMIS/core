<?php

use Phinx\Migration\AbstractMigration;

class POCOR4724 extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('feeders_institutions', [
                'id' => false,
                'primary_key' => ['institution_id', 'feeder_institution_id', 'academic_period_id', 'education_grade_id'],
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains the relation between institutions and feeder institution'
        ]);

        $table
            ->addColumn('institution_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('feeder_institution_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('academic_period_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to academic_periods.id'
            ])
            ->addColumn('education_grade_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to education_grades.id'
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
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'null' => false
            ])
            ->addIndex('institution_id')
            ->addIndex('feeder_institution_id')
            ->addIndex('academic_period_id')
            ->addIndex('education_grade_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
    }

    public function down()
    {
        $this->dropTable('feeders_institutions');
    }
}
