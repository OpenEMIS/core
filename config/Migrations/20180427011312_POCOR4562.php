<?php

use Phinx\Migration\AbstractMigration;

class POCOR4562 extends AbstractMigration
{
    public function up()
    {
        // report_card_processes
        $table = $this->table('report_card_processes', [
                'id' => false,
                'primary_key' => [
                    'report_card_id',
                    'institution_class_id',
                    'student_id'
                ],
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'Internal use - to track the report card processes currenly running'
            ]);
        $table
            ->addColumn('report_card_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to report_cards.id'
            ])
            ->addColumn('institution_class_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_classes.id'
            ])
            ->addColumn('student_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('status', 'integer', [
                'limit' => 2,
                'null' => false,
                'comment' => '1 => New 2 => Running 3 => Completed -1 => Error'
            ])
            ->addColumn('institution_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('education_grade_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to education_grades.id'
            ])
            ->addColumn('academic_period_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to academic_periods.id'
            ])
            ->addColumn('created', 'datetime', [
                'null' => false
            ])
            ->addIndex('report_card_id')
            ->addIndex('institution_class_id')
            ->addIndex('student_id')
            ->addIndex('institution_id')
            ->addIndex('education_grade_id')
            ->addIndex('academic_period_id')
            ->save();
    }

    public function down()
    {
        $this->dropTable('report_card_processes');
    }
}
