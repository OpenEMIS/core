<?php

use Cake\I18n\Date;
use Cake\ORM\TableRegistry;
use Phinx\Migration\AbstractMigration;

class POCOR4324 extends AbstractMigration
{
    public function up()
    {
        // student_attendance_types - start
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
        // student_attendance_types - end


        // student_attendance_mark_types - start
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
        // student_attendance_mark_types - end


        // student_attendance_marked_records - start
        $StudentAttendanceMarkRecords = $this->table('student_attendance_marked_records', [
            'id' => false,
            'primary_key' => ['institution_id', 'academic_period_id', 'institution_class_id', 'date', 'period'],
            'collaction' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains attendance marking records'
        ]);

        $StudentAttendanceMarkRecords
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
            ->addIndex('institution_id')
            ->addIndex('academic_period_id')
            ->addIndex('institution_class_id')
            ->save();

        $institutionClassesList = []; // class_id - institution_id pair
        $dateData = [];
        $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $records = $this->fetchAll('SELECT * FROM `institution_class_attendance_records`');
        if (count($records) > 0) {
            foreach ($records as $value) {
                $institutionClassId = $value['institution_class_id'];
                $academicPeriodId = $value['academic_period_id'];

                if (isset($institutionClassesList[$institutionClassId])) {
                    $institutionId = $institutionClassesList[$institutionClassId];
                } else {
                    $classEntity = $InstitutionClasses
                        ->find()
                        ->select([$InstitutionClasses->aliasField('institution_id')])
                        ->where([$InstitutionClasses->aliasField('id') => $institutionClassId])
                        ->first();

                    if (!is_null($classEntity)) {
                        $institutionClassesList[$institutionClassId] = $classEntity->institution_id;           
                        $institutionId = $institutionClassesList[$institutionClassId];
                    }
                }

                if (!is_null($institutionId)) {
                    $year = $value['year'];
                    $month = $value['month'];
                    $dayPrefix = 'day_';

                    for ($i = 1; $i <= 31; ++$i) {
                        /*
                            0 = Not marked
                            1 = Marked
                            -1 = Not valid
                         */
                        $dayColumn = $dayPrefix . $i;
                        if ($value[$dayColumn] == 1) {
                            $day = $i;

                            $date = (new Date($year . '-' . $month . '-' . $day))->format('Y-m-d');
                            $dateData[] = [
                                'institution_class_id' => $institutionClassId,
                                'academic_period_id' => $academicPeriodId,
                                'institution_id' => $institutionId,
                                'date' => $date,
                                'period' => 1
                            ];
                        }
                    }
                }
            }
        }

        if (!empty($dateData)) {
            $StudentAttendanceMarkRecords
                ->insert($dateData)
                ->save();
        }
        // student_attendance_marked_records - end


        // institution_student_absences - start
        // backup 
        $this->execute('CREATE TABLE `z_4324_institution_student_absences` LIKE `institution_student_absences`');
        $this->execute('INSERT INTO `z_4324_institution_student_absences` SELECT * FROM `institution_student_absences`');
        $this->execute('DROP TABLE IF EXISTS `institution_student_absences`');

        $InstitutionStudentAbsences = $this->table('institution_student_absences', [
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
                'comment' => 'links to institutions.id'
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
            ->addColumn('absence_type_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to student_absence_reasons.id'
            ])
            ->addColumn('institution_student_absence_day_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'comment' => 'links to institution_student_absence_days.id'
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
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // institution_student_absences - end


        // institution_student_absence_details - start
        $InstitutionStudentAbsenceDetails = $this->table('institution_student_absence_details', [
            'id' => false,
            'primary_key' => ['student_id', 'institution_id', 'academic_period_id', 'institution_class_id', 'date', 'period'],
            'collaction' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains absence records of students for day type attendance marking'
        ]);

        $InstitutionStudentAbsenceDetails
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
                'comment' => 'links to institutions.id'
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
                'null' => true,
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
        // institution_student_absence_details - end
        
        // $weekdays = [
        //  0 => __('Sunday'),
        //  1 => __('Monday'),
        //  2 => __('Tuesday'),
        //  3 => __('Wednesday'),
        //  4 => __('Thursday'),
        //  5 => __('Friday'),
        //  6 => __('Saturday'),
        // ];

        // flatten to single day and insert 
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');

        $firstDayOfWeek = $ConfigItems->value('first_day_of_week');
        $daysPerWeek = $ConfigItems->value('days_per_week');
        $workingDays = [];
        for ($i = 0; $i < $daysPerWeek; ++$i) {
            $day = ($firstDayOfWeek + $i) % 7;
            $workingDays[] = $day;
        }

        $absenceMainData = [];
        $absenceDetailData = [];

        $rows = $this->fetchAll('SELECT * FROM `z_4324_institution_student_absences`');
        if (count($rows) > 0) {
            foreach ($rows as $value) {
                $data = [];
                $data['student_id'] = $value['student_id'];
                $data['institution_id'] = $value['institution_id'];
                $data['absence_type_id'] = $value['absence_type_id'];
                $data['modified_user_id'] = $value['modified_user_id'];
                $data['modified'] = $value['modified'];
                $data['created_user_id'] = $value['created_user_id'];
                $data['created'] = $value['created'];

                $startDate = new Date($value['start_date']);
                $endDate = new Date($value['end_date']);

                do {
                    $date = $startDate->copy();
                    $dateKey = ($startDate->format('N')) % 7;

                    if (in_array($dateKey, $workingDays)) {
                        $academicPeriodId = $AcademicPeriods->getAcademicPeriodIdByDate($date);
                        $data['date'] = $date->format('Y-m-d');
                        $data['academic_period_id'] = $academicPeriodId;

                        // get institution_class_id by academic_period_id, institution_id, student_id
                        $result = $InstitutionClassStudents
                            ->find()
                            ->where([
                                $InstitutionClassStudents->aliasField('academic_period_id') => $academicPeriodId,
                                $InstitutionClassStudents->aliasField('institution_id') => $value['institution_id'],
                                $InstitutionClassStudents->aliasField('student_id') => $value['student_id']
                            ]);

                        if (!$result->isEmpty()) {
                            $classId = $result->first()->institution_class_id;
                            $data['institution_class_id'] = $classId;

                            $absenceData = $data;
                            $absenceData['institution_student_absence_day_id'] = $value['institution_student_absence_day_id'];

                            $detailData = $data;
                            $detailData['student_absence_reason_id'] = $value['student_absence_reason_id'];
                            $detailData['comment'] = $value['comment'];
                            $detailData['period'] = 1;

                            $absenceMainData[] = $absenceData;
                            $absenceDetailData[] = $detailData;
                        } else {
                            // pr('this data does not have a class');
                        }
                     }
                    $startDate->addDay();
                } while ($startDate->lte($endDate));
            }

            if (!empty($absenceMainData)) {
                $InstitutionStudentAbsences
                    ->insert($absenceMainData)
                    ->save();
            }
            
            if (!empty($absenceDetailData)) {
                $InstitutionStudentAbsenceDetails
                    ->insert($absenceDetailData)
                    ->save();
            }
        }

        // locale_contents - start
        // backup
        $this->execute('CREATE TABLE `z_4324_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_4324_locale_contents` SELECT * FROM `locale_contents`');
        $today = date('Y-m-d H:i:s');
        $localeContent = [
            [
                'en' => 'Total Attendance',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'No. of Present',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'No. of Late',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'No. of Absence',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Week',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Day',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Attendance per day',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Reason / Comment',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Period 1',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Period 2',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Period 3',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Period 4',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Period 5',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Monday',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Tuesday',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Wednesday',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Thursday',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Friday',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Saturday',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Sunday',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Present',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Absence - Excused',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Absence - Unexcused',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Late',
                'created_user_id' => 1,
                'created' => $today
            ]
        ];

        $this->insert('locale_contents', $localeContent);
        // locale_contents - end


        // security_functions - start
        // backup
        $this->execute('CREATE TABLE `z_4324_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_4324_security_functions` SELECT * FROM `security_functions`');
        $this->execute('
            UPDATE `security_functions` SET
            `_view` = "StudentAttendances.index|StudentAbsences.view",
            `_edit` = "StudentAttendances.edit",
            `_add` = NULL,
            `_delete` = NULL,
            `_execute` = NULL
            WHERE `id` = 1014
        ');

        $this->execute('UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` >= 202');
        $securityData = [
            'id' => 5106,
            'name' => 'Attendances',
            'controller' => 'Attendances',
            'module' => 'Administration',
            'category' => 'Attendances',
            'parent_id' => 5000,
            '_view' => 'StudentMarkTypes.view',
            '_edit' => 'StudentMarkTypes.edit',
            '_add' => null,
            '_delete' => null,
            '_execute' => null,
            'order' => 202,
            'visible' => 1,
            'description' => null,
            'created_user_id' => 1,
            'created' => $today
        ];
        $this->insert('security_functions', $securityData);
        // security_functions - end

        // security_role_functions
        // backup
        $this->execute('CREATE TABLE `z_4324_security_role_functions` LIKE `security_role_functions`');
        $this->execute('INSERT INTO `z_4324_security_role_functions` SELECT * FROM `security_role_functions`');
        $this->execute('
            UPDATE `security_role_functions` SET
            `_add` = 0,
            `_delete` = 0,
            `_execute` = 0
            WHERE `security_role_id` = 1014
        ');
        // security_role_functions - end
    }

    public function down()
    {
        $this->dropTable('student_attendance_types');
        $this->dropTable('student_attendance_mark_types');
        $this->dropTable('student_attendance_marked_records');

        $this->dropTable('institution_student_absence_details');
        $this->dropTable('institution_student_absences');
        $this->execute('RENAME TABLE `z_4324_institution_student_absences` TO `institution_student_absences`');

        $this->dropTable('locale_contents');
        $this->execute('RENAME TABLE `z_4324_locale_contents` TO `locale_contents`');

        $this->dropTable('security_functions');
        $this->execute('RENAME TABLE `z_4324_security_functions` TO `security_functions`');

        $this->dropTable('security_role_functions');
        $this->execute('RENAME TABLE `z_4324_security_role_functions` TO `security_role_functions`');
    }
}
