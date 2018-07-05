<?php

use Phinx\Migration\AbstractMigration;

class POCOR4324 extends AbstractMigration
{
    public function up()
    {
        // student_attendance_types
        $StudentAttendanceType = $this->table('student_attendance_types', [
            'id' => false,
            'primary_key' => 'id',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains different attendance marking types'
        ]);

        $StudentAttendanceType
            ->addColumn('id', 'integer', [
                'limit' => 11,
                'default' => null,
                'null' => false
            ])
            ->addColumn('code', 'string', [
                'limit' => 25,
                'default' => null,
                'null' => false
            ])
            ->addColumn('name', 'string', [
                'limit' => 25,
                'default' => null,
                'null' => false
            ])
            ->addIndex('id')
            ->save();

        $studentAttendanceTypeData = [
            [
                'id' => 1,
                'code' => 'DAY',
                'name' => 'Day'
            ],
            [
                'id' => 2,
                'code' => 'SUBJECT',
                'name' => 'Subject'
            ]
        ];

        $StudentAttendanceType
            ->insert($studentAttendanceTypeData)
            ->save();

        // student_attendance_mark_types
        $StudentAttendanceMarkTypes = $this->table('student_attendance_mark_types', [
            'id' => false,
            'primary_key' => ['education_grade_id', 'academic_period_id'],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains different attendance marking for different academic periods for different programme'
        ]);

        $StudentAttendanceMarkTypes
            ->addColumn('education_grade_id', 'integer', [
                'limit' => 11,
                'default' => null,
                'null' => false,
                'comment' => 'links to education_grades.id'
            ])
            ->addColumn('academic_period_id', 'integer', [
                'limit' => 11,
                'default' => null,
                'null' => false,
                'comment' => 'links to academic_periods.id'
            ])
            ->addColumn('student_attendance_type_id', 'integer', [
                'limit' => 11,
                'default' => null,
                'null' => false,
                'comment' => 'links to student_attendance_types.id'
            ])
            ->addColumn('attendance_per_day', 'integer', [
                'limit' => 1,
                'default' => null,
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
            ->addIndex('education_grade_id')
            ->addIndex('academic_period_id')
            ->addIndex('student_attendance_type_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // institution_student_absences_period_1
        // institution_student_absences_period_2
        // institution_student_absences_period_3
        // institution_student_absences_period_4
        // institution_student_absences_period_5
    }

    public function down()
    {
        $this->dropTable('student_attendance_types');
        $this->dropTable('student_attendance_mark_types');
    }
}
