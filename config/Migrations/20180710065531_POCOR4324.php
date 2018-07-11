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
        for ($i = 1; $i <= 5; ++$i) {
            $tableName = 'institution_student_absences_period_' . $i;

            $InstitutionStudentAbsencesPeriod = $this->table($tableName, [
                'collaction' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains absence records of students for period ' . $i
            ]);

            $InstitutionStudentAbsencesPeriod
                ->addColumn('start_time', 'time', [
                    'default' => null,
                    'null' => true
                ])
                ->addColumn('end_time', 'time', [
                    'default' => null,
                    'null' => true
                ])
                ->addColumn('comment', 'text', [
                    'default' => null,
                    'null' => true
                ])
                ->addColumn('absence_type_id', 'integer', [
                    'default' => null,
                    'limit' => 11,
                    'null' => false,
                    'comment' => 'links to student_absence_reasons.id'
                ])
                ->addColumn('student_absence_reason_id', 'integer', [
                    'default' => null,
                    'limit' => 11,
                    'null' => false,
                    'comment' => 'links to absence_types.id'
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
                ->addIndex('absence_type_id')
                ->addIndex('student_absence_reason_id')
                ->addIndex('modified_user_id')
                ->addIndex('created_user_id')
                ->save();
        }

        // backup 
        $this->execute('CREATE TABLE `z_4234_institution_student_absences` LIKE `institution_student_absences`');
        $this->execute('INSERT INTO `z_4234_institution_student_absences` SELECT * FROM `institution_student_absences`');

        // $this->execute('CREATE TABLE `z_4234_institution_student_absence_days` LIKE `institution_student_absence_days`');
        // $this->execute('INSERT INTO `z_4234_institution_student_absence_days` SELECT * FROM `institution_student_absence_days`');

        // testing purpose
        $this->execute('DROP TABLE IF EXISTS `institution_student_absences`');
        // $this->execute('DROP TABLE IF EXISTS `institution_student_absence_days`');
        
        $InstitutionStudentAbsences = $this->table('institution_student_absences', [
            'collaction' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains absence records of students'
        ]);

        $InstitutionStudentAbsences
            ->addColumn('date', 'date', [])
            ->addColumn('full_day', 'integer', [])
            ->addColumn('student_id', 'integer', [])
            ->addColumn('institution_id', 'integer', [])
            ->addColumn('absences_period_1_id', 'integer', [])
            ->addColumn('absences_period_2_id', 'integer', [])
            ->addColumn('absences_period_3_id', 'integer', [])
            ->addColumn('absences_period_4_id', 'integer', [])
            ->addColumn('absences_period_5_id', 'integer', [])
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
            ->addIndex('institution_id')
            ->addIndex('absences_period_1_id')
            ->addIndex('absences_period_2_id')
            ->addIndex('absences_period_3_id')
            ->addIndex('absences_period_4_id')
            ->addIndex('absences_period_5_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
    }

    public function down()
    {
        $this->dropTable('student_attendance_types');
        $this->dropTable('student_attendance_mark_types');
        $this->dropTable('institution_student_absences_period_1');
        $this->dropTable('institution_student_absences_period_2');
        $this->dropTable('institution_student_absences_period_3');
        $this->dropTable('institution_student_absences_period_4');
        $this->dropTable('institution_student_absences_period_5');

        $this->execute('DROP TABLE IF EXISTS `institution_student_absences`');
        $this->execute('RENAME TABLE `z_4234_institution_student_absences` TO `institution_student_absences`');
    }
}
