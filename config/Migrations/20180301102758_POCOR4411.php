<?php

use Phinx\Migration\AbstractMigration;

class POCOR4411 extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('institution_class_attendance_records', [
            'id' => false,
            'primary_key' => ['institution_class_id', 'academic_period_id', 'year', 'month'],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the whether the class attendance is marked for a month'
        ]);

        $table
            ->addColumn('institution_class_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'link to institution_classes.id'
            ])
            ->addColumn('academic_period_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'link to academic_period.id'
            ])
            ->addColumn('year', 'integer', [
                'limit' => 4,
                'null' => false,
            ])
            ->addColumn('month', 'integer', [
                'limit' => 2,
                'null' => false,
                'comment' => 'Jan = 1, Dec = 12'
            ])
            ->addIndex('institution_class_id')
            ->addIndex('academic_period_id')
            ->addIndex('year')
            ->addIndex('month');

        for ($day = 1; $day <= 31; ++$day) {
            $dayColumn = 'day_' . $day;

            $table
                ->addColumn($dayColumn, 'integer', [
                    'limit' => 1,
                    'null' => false,
                    'default' => 0
                ]);
        }

        $table->save();
    }

    public function down()
    {
        $this->dropTable('institution_class_attendance_records');
    }
}
