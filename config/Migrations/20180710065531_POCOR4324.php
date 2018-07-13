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
        /*
            Admin setup for student_attendance_mark_type.
            For future, if institution level wants to override, create table institution_student_attendance_mark_type (primary key: institution_id, education_grade_id, academic_period_id)
            
            Attendance per day will read from institution_student_attendance_mark_type.
            If no record found, read from student_attendance_mark_types
            If no record found, default is Day type, per day 1
         */
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

        // backup 
        $this->execute('CREATE TABLE `z_4234_institution_student_absences` LIKE `institution_student_absences`');
        $this->execute('INSERT INTO `z_4234_institution_student_absences` SELECT * FROM `institution_student_absences`');

        // $this->execute('CREATE TABLE `z_4234_institution_student_absence_days` LIKE `institution_student_absence_days`');
        // $this->execute('INSERT INTO `z_4234_institution_student_absence_days` SELECT * FROM `institution_student_absence_days`');
        // $this->execute('DROP TABLE IF EXISTS `institution_student_absence_days`');

        // institution_student_absences - DAY TYPE STUDENT ATTENDANCE MARKING
        $this->execute('DROP TABLE IF EXISTS `institution_student_absences`');
        
        $InstitutionStudentAbsences = $this->table('institution_student_absences', [
            'id' => false,
            'primary_key' => ['student_id', 'institution_id', 'academic_period_id', 'institution_class_id', 'date'],
            'collaction' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains absence records of students for day type attendance marking'
        ]);

        $InstitutionStudentAbsences
            ->addColumn('student_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('institution_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to instututions.id'
            ])
            ->addColumn('academic_period_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to academic_periods.id'
            ])
            ->addColumn('institution_class_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_classes.id'
            ])
            ->addColumn('date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('period', 'integer', [
                'default' => null,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('start_time', 'time', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('end_time', 'time', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => false
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
            ->addIndex('student_id')
            ->addIndex('institution_id')
            ->addIndex('academic_period_id')
            ->addIndex('institution_class_id')
            ->addIndex('absence_type_id')
            ->addIndex('student_absence_reason_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
    }

    public function down()
    {
        $this->dropTable('student_attendance_types');
        $this->dropTable('student_attendance_mark_types');

        $this->execute('DROP TABLE IF EXISTS `institution_student_absences`');
        $this->execute('RENAME TABLE `z_4234_institution_student_absences` TO `institution_student_absences`');
    }
}
