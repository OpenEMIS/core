<?php
use Cake\I18n\Date;
use Phinx\Migration\AbstractMigration;
use Cake\ORM\TableRegistry;
use Cake\Utility\Security;
use Cake\Utility\Hash;

class POCOR3906 extends AbstractMigration
{
    public function up()
    {
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $InstitutionStaffLeave = TableRegistry::get('Institution.StaffLeave');

        // institution_staff_attendance_activities
        $StaffAttendanceActivities = $this->table(
            'institution_staff_attendance_activities', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the log for staff attendances'
            ]
        );

        $StaffAttendanceActivities
            ->addColumn('model', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => false,
            ])
            ->addColumn('model_reference', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('field', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => false,
            ])
            ->addColumn('field_type', 'string', [
                'default' => null,
                'limit' => 128,
                'null' => false,
            ])
            ->addColumn('old_value', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('new_value', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('operation', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => false,
            ])
            ->addColumn('security_user_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
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
            ->addIndex('model_reference')
            ->addIndex('security_user_id')
            ->addIndex('created_user_id')
            ->save();

        // institution_staff_attendances
        $InstitutionStaffAttendances = $this->table(
            'institution_staff_attendances', [
            'id' => false,
            'primary_key' => ['staff_id', 'institution_id', 'academic_period_id', 'date'],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the attendance records for staff'
            ]
        );

        $InstitutionStaffAttendances
            ->addColumn(
                'id', 'string', [
                'limit' => 64,
                'null' => false
                ]
            )
            ->addColumn(
                'staff_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to security_users.id'
                ]
            )
            ->addColumn(
                'institution_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to instututions.id'
                ]
            )
            ->addColumn(
                'academic_period_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to academic_periods.id'
                ]
            )
            ->addColumn(
                'date', 'date', [
                'default' => null,
                'null' => false
                ]
            )
            ->addColumn(
                'time_in', 'time', [
                'default' => null,
                'limit' => null,
                'null' => true,
                ]
            )
            ->addColumn(
                'time_out', 'time', [
                'default' => null,
                'limit' => null,
                'null' => true,
                ]
            )
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn(
                'modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
                ]
            )
            ->addColumn(
                'modified', 'datetime', [
                'default' => null,
                'null' => true
                ]
            )
            ->addColumn(
                'created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
                ]
            )
            ->addColumn(
                'created', 'datetime', [
                'default' => null,
                'null' => false
                ]
            )
            ->addIndex('staff_id')
            ->addIndex('institution_id')
            ->addIndex('academic_period_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // start of overlappingRecords
        $overlapSql = 'SELECT
            `ISA1`.`id` AS `ISA1__ID`,
            `ISA2`.`id` AS `ISA2__ID`,
            `ISA1`.`full_day` AS `ISA1__FULLDAY`,
            `ISA2`.`full_day` AS `ISA2__FULLDAY`,
            `ISA1`.`start_date` AS `ISA1__STARTDATE`,
            `ISA1`.`end_date` AS `ISA1__ENDDATE`,
            `ISA2`.`start_date` AS `ISA2__STARTDATE`,
            `ISA2`.`end_date` AS `ISA2__ENDDATE`,
            `ISA1`.`absence_type_id` AS `ISA1__ABSENCETYPEID`,
            `ISA2`.`absence_type_id` AS `ISA2__ABSENCETYPEID`,
            `ISA1`.`staff_id` AS `ISA1__STAFFID`,
            `ISA1`.`institution_id` AS `ISA1__INSTITUTIONID`,
            `ISA2`.`staff_id` AS `ISA2__STAFFID`,
            `ISA2`.`institution_id` AS `ISA2__INSTITUTIONID`,
            `ISA1`.`modified` AS `ISA1__MODIFIED`,
            `ISA1`.`modified_user_id` AS `ISA1__MODIFIEDUSERID`,
            `ISA1`.`created` AS `ISA1__CREATED`,
            `ISA1`.`created_user_id` AS `ISA1__CREATEDUSERID`,
            `ISA2`.`modified` AS `ISA2__MODIFIED`,
            `ISA2`.`modified_user_id` AS `ISA2__MODIFIEDUSERID`,
            `ISA2`.`created` AS `ISA2__CREATED`,
            `ISA2`.`created_user_id` AS `ISA2__CREATEDUSERID`,
            `ISA1`.`comment` AS `ISA1__COMMENT`,
            `ISA2`.`comment` AS `ISA2__COMMENT`,
            `SAR1`.`name` AS `SAR1__NAME`,
            `SAR2`.`name` AS `SAR2__NAME`
            FROM `institution_staff_absences` `ISA1`
            INNER JOIN `institution_staff_absences` `ISA2`
            ON (
               `ISA1`.`id` < `ISA2`.`id` AND
               `ISA1`.`staff_id` = `ISA2`.`staff_id` AND
               `ISA1`.`institution_id` = `ISA2`.`institution_id`
            )
            LEFT JOIN `staff_absence_reasons` `SAR1`
            ON (
               `ISA1`.`staff_absence_reason_id` = `SAR1`.`id`
            )
            LEFT JOIN `staff_absence_reasons` `SAR2`
            ON (
               `ISA2`.`staff_absence_reason_id` = `SAR2`.`id`
            )
            WHERE
            (
               `ISA1`.`end_date` >= `ISA2`.`start_date` AND
               `ISA1`.`end_date` <= `ISA2`.`end_date`
            )
            OR
            (
               `ISA1`.`start_date` >= `ISA2`.`start_date` AND
               `ISA1`.`start_date` <= `ISA2`.`end_date`
            )
            OR
            (
               `ISA1`.`start_date` <= `ISA2`.`start_date` AND
               `ISA1`.`end_date` >= `ISA2`.`end_date`
            )
            OR
            (
               `ISA1`.`start_date` >= `ISA2`.`start_date` AND
               `ISA1`.`end_date` <= `ISA2`.`end_date`
            )
            ORDER BY `ISA1__ID`;';
        $overlapData = $this->fetchAll($overlapSql);
        $mergedData = [];
        $mergedDataSql = null;
        $tmp = [];
        if (!empty($overlapData)) {
            $mergedData = array_merge(Hash::extract($overlapData, '{n}.ISA1__ID'), Hash::extract($overlapData, '{n}.ISA2__ID'));
            $mergedDataSql = '(' . implode(',', $mergedData) . ')';
            $tmpData = [];
            foreach ($overlapData as $key => $value) {
                $startDate = strtotime($value['ISA2__STARTDATE']);
                $endDate = strtotime($value['ISA2__ENDDATE']);
                $datediff = $endDate - $startDate;
                $days = round($datediff / (60 * 60 * 24));
                $comment = '';
                if ($value['ISA2__FULLDAY']) {
                    $comment = 'Absent (Full Day)';
                } else {
                    $comment = 'Absent:'. date('H:i:s', strtotime($value['ISA2__STARTDATE'])).'-'.date('H:i:s', strtotime($value['ISA2__ENDDATE']));
                }
                if (isset($value['SAR2__NAME'])) {
                    $comment = $comment.'. '.$value['SAR2__NAME'];
                }
                if ($value['ISA2__COMMENT']) {
                    $comment = $comment.'. '.$value['ISA2__COMMENT'];
                }
                if ($value['ISA2__MODIFIED']) {
                    $modified = date('Y-m-d H:i:s', strtotime($value['ISA2__MODIFIED']));
                } else {
                    $modified = null;
                }

                $academicPeriodId = $AcademicPeriods
                    ->find()
                    ->where([
                        $AcademicPeriods->aliasField('start_date') . ' <= ' => $value['ISA2__STARTDATE'],
                        $AcademicPeriods->aliasField('end_date') . ' >= ' => $value['ISA2__ENDDATE'],
                        $AcademicPeriods->aliasField('code') . ' <> ' => 'all',
                        $AcademicPeriods->aliasField('academic_period_level_id') => 1
                    ])
                    ->extract('id')
                    ->first();

                if (!is_null($academicPeriodId)) {
                    for ($i = 0; $i <= $days; $i++) {
                        $hashString = [$value['ISA2__STAFFID'], $value['ISA2__INSTITUTIONID'], $academicPeriodId, date('Y-m-d', strtotime($value['ISA2__STARTDATE']. ' + '.$i.' days'))];
                        $id = Security::hash(implode(',', $hashString), 'sha256');
                        if (!in_array($id, $tmpData)) {
                            $tmpData[] = $id;
                            $tmp[] = [
                                'id' => $id,
                                'staff_id' => $value['ISA2__STAFFID'],
                                'institution_id' => $value['ISA2__INSTITUTIONID'],
                                'academic_period_id' => $academicPeriodId,
                                'date' => date('Y-m-d', strtotime($value['ISA2__STARTDATE']. ' + '.$i.' days')),
                                'time_in' => null,
                                'time_out' => null,
                                'comment' => $comment,
                                'modified_user_id' => $value['ISA2__MODIFIEDUSERID'],
                                'modified' => $modified,
                                'created_user_id' => $value['ISA2__CREATEDUSERID'],
                                'created' => date('Y-m-d H:i:s', strtotime($value['ISA2__CREATED']))
                            ];
                        }
                    }
                }
                $startDate = strtotime($value['ISA1__STARTDATE']);
                $endDate = strtotime($value['ISA1__ENDDATE']);
                $datediff = $endDate - $startDate;
                $days = round($datediff / (60 * 60 * 24));
                $academicPeriodId = $AcademicPeriods
                    ->find()
                    ->where([
                        $AcademicPeriods->aliasField('start_date') . ' <= ' => $value['ISA1__STARTDATE'],
                        $AcademicPeriods->aliasField('end_date') . ' >= ' => $value['ISA1__ENDDATE'],
                        $AcademicPeriods->aliasField('code') . ' <> ' => 'all',
                        $AcademicPeriods->aliasField('academic_period_level_id') => 1
                    ])
                    ->extract('id')
                    ->first();
                if (!is_null($academicPeriodId)) {
                    $comment = '';
                    if ($value['ISA1__FULLDAY']) {
                        $comment = 'Absent (Full Day)';
                    } else {
                        $comment = 'Absent:'. date('H:i:s', strtotime($value['ISA1__STARTDATE'])).'-'.date('H:i:s', strtotime($value['ISA1__ENDDATE']));
                    }
                    if (isset($value['SAR1__NAME'])) {
                        $comment = $comment.'. '.$value['SAR1__NAME'];
                    }
                    if ($value['ISA1__COMMENT']) {
                        $comment = $comment.'. '.$value['ISA1__COMMENT'];
                    }
                    if ($value['ISA1__MODIFIED']) {
                        $modified = date('Y-m-d H:i:s', strtotime($value['ISA1__MODIFIED']));
                    } else {
                        $modified = null;
                    }
                    for ($i = 0; $i <= $days; $i++) {
                        $hashString = [$value['ISA1__STAFFID'], $value['ISA1__INSTITUTIONID'], $academicPeriodId, date('Y-m-d', strtotime($value['ISA1__STARTDATE']. ' + '.$i.' days'))];
                        $id = Security::hash(implode(',', $hashString), 'sha256');
                        if (!in_array($id, $tmpData)) {
                            $tmpData[] = $id;
                            $tmp[] = [
                                'id' => $id,
                                'staff_id' => $value['ISA1__STAFFID'],
                                'institution_id' => $value['ISA1__INSTITUTIONID'],
                                'academic_period_id' => $academicPeriodId,
                                'date' => date('Y-m-d', strtotime($value['ISA1__STARTDATE']. ' + '.$i.' days')),
                                'time_in' => null,
                                'time_out' => null,
                                'comment' => $comment,
                                'modified_user_id' => $value['ISA1__MODIFIEDUSERID'],
                                'modified' => $modified,
                                'created_user_id' => $value['ISA1__CREATEDUSERID'],
                                'created' => date('Y-m-d H:i:s', strtotime($value['ISA1__CREATED']))
                            ];
                        }
                    }
                }
            }

            if (!empty($tmp)) {
                $InstitutionStaffAttendances
                    ->insert($tmp)
                    ->save();
            }
        }
        // end of overlappingRecords

        // start of nonoverlappingRecords
        if ($mergedDataSql) {
            $countData = $this->fetchAll('SELECT count(*) AS `COUNT` FROM `institution_staff_absences` LEFT JOIN `staff_absence_reasons`on `institution_staff_absences`.`staff_absence_reason_id` = `staff_absence_reasons`.`id` where `institution_staff_absences`.`id` NOT IN '.$mergedDataSql);
        } else {
            $countData = $this->fetchAll('SELECT count(*) AS `COUNT` FROM `institution_staff_absences` LEFT JOIN `staff_absence_reasons`on `institution_staff_absences`.`staff_absence_reason_id` = `staff_absence_reasons`.`id`');
        }
        $count = $countData[0]['COUNT'];
        $MAX_PER_LOOP = 10000;
        $iteration = ceil($count / $MAX_PER_LOOP);

        for ($i = 1; $i <= $iteration; $i++) {
            $nonoverlappingRecords = [];
            if ($mergedDataSql) {
                $staffAbsences = $this->fetchAll('SELECT `institution_staff_absences`.`*`, `staff_absence_reasons`.`name`FROM `institution_staff_absences` LEFT JOIN `staff_absence_reasons`on `institution_staff_absences`.`staff_absence_reason_id` = `staff_absence_reasons`.`id` where `institution_staff_absences`.`id` NOT IN '.$mergedDataSql . 'LIMIT '.$MAX_PER_LOOP.' OFFSET ' . ($i * $MAX_PER_LOOP));
            } else {
                $staffAbsences = $this->fetchAll('SELECT `institution_staff_absences`.`*`, `staff_absence_reasons`.`name`FROM `institution_staff_absences` LEFT JOIN `staff_absence_reasons`on `institution_staff_absences`.`staff_absence_reason_id` = `staff_absence_reasons`.`id` LIMIT '.$MAX_PER_LOOP.' OFFSET ' . ($i * $MAX_PER_LOOP));
            }
            foreach ($staffAbsences as $key => $value) {
                $startDate = strtotime($value['start_date']);
                $endDate = strtotime($value['end_date']);
                $datediff = $endDate - $startDate;
                $days = round($datediff / (60 * 60 * 24));
                $comment = '';
                if ($value['full_day']) {
                    $comment = 'Absent (Full Day)';
                } else {
                    $comment = 'Absent:'. date('H:i:s', strtotime($value['start_time'])).'-'.date('H:i:s', strtotime($value['end_time']));
                }
                if (isset($value['name'])) {
                    $comment = $comment.'. '.$value['name'];
                }
                if ($value['comment']) {
                    $comment = $comment.'. '.$value['comment'];
                }
                if ($value['modified']) {
                    $modified = date('Y-m-d H:i:s', strtotime($value['modified']));
                } else {
                    $modified = null;
                }

                $academicPeriodId = $AcademicPeriods
                    ->find()
                    ->where([
                        $AcademicPeriods->aliasField('start_date') . ' <= ' => $value['start_date'],
                        $AcademicPeriods->aliasField('end_date') . ' >= ' => $value['end_date'],
                        $AcademicPeriods->aliasField('code') . ' <> ' => 'all',
                        $AcademicPeriods->aliasField('academic_period_level_id') => 1
                    ])
                    ->extract('id')
                    ->first();
                if (!is_null($academicPeriodId)) {
                    for ($j = 0; $j <= $days; $j++) {
                        $hashString = [$value['staff_id'], $value['institution_id'], $academicPeriodId, date('Y-m-d', strtotime($value['start_date']. ' + '.$j.' days'))];
                        $id = Security::hash(implode(',', $hashString), 'sha256');
                        $nonoverlappingRecords[] = [
                            'id' => $id,
                            'staff_id' => $value['staff_id'],
                            'institution_id' => $value['institution_id'],
                            'academic_period_id' => $academicPeriodId,
                            'date' => date('Y-m-d', strtotime($value['start_date']. ' + '.$j.' days')),
                            'time_in' => null,
                            'time_out' => null,
                            'comment' => $comment,
                            'modified_user_id' => $value['modified_user_id'],
                            'modified' => $modified,
                            'created_user_id' => '1',
                            'created' => date('Y-m-d H:i:s', strtotime($value['created']))
                        ];
                    }
                } 
            }
            if (!empty($nonoverlappingRecords)) {
                $InstitutionStaffAttendances
                    ->insert($nonoverlappingRecords)
                    ->save();
            }
        }
        // end of nonoverlappingRecords

        // institution_staff_leave
        $institutionStaffLeaveOrigin = $InstitutionStaffLeave
            ->find()
            ->toArray();

        $data = [];
        foreach ($institutionStaffLeaveOrigin as $key => $value) {
            $startDate = $value->date_from;
            $academicPeriodId = $AcademicPeriods
                ->find()
                ->where([
                    $AcademicPeriods->aliasField('start_date') . ' <= ' => $startDate->format('Y-m-d'),
                    $AcademicPeriods->aliasField('end_date') . ' >= ' => $startDate->format('Y-m-d'),
                    $AcademicPeriods->aliasField('code') . ' <> ' => 'all',
                    $AcademicPeriods->aliasField('academic_period_level_id') => 1
                ])
                ->extract('id')
                ->first();
            if ($academicPeriodId) {
                if ($value->modified) {
                    $modified = date('Y-m-d H:i:s', strtotime($value->modified));
                } else {
                    $modified = null;
                }
                $data[] = [
                        'id' => $value->id,
                        'date_from' => date('Y-m-d', strtotime($value->date_from)),
                        'date_to' => date('Y-m-d', strtotime($value->date_to)),
                        'comments' => $value->comments,
                        'staff_id' => $value->staff_id,
                        'staff_leave_type_id' => $value->staff_leave_type_id,
                        'institution_id' => $value->institution_id,
                        'assignee_id' => $value->assignee_id,
                        'academic_period_id' => $academicPeriodId,
                        'status_id' => $value->status_id,
                        'number_of_days' => $value->number_of_days,
                        'file_name' => $value->file_name,
                        'file_content' => $value->file_content,
                        'modified_user_id' => $value->modified_user_id,
                        'modified' => $modified,
                        'created_user_id' => '1',
                        'created' => date('Y-m-d H:i:s', strtotime($value->created))
                    ];
            }
        }

        $this->execute('RENAME TABLE `institution_staff_leave` TO `z_3906_institution_staff_leave`');

        $StaffLeave = $this->table('institution_staff_leave', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of leave for a specific staff'
        ]);

        $StaffLeave
            ->addColumn('date_from', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('date_to', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
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
            // set full_day to true because staff leave did not cater for non full day previously
            ->addColumn('full_day', 'integer', [
                'default' => 1,
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('comments', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('staff_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('staff_leave_type_id', 'integer', [
                'comment' => 'links to staff_leave_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('assignee_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('academic_period_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to academic_periods.id'
            ])
            ->addColumn('status_id', 'integer', [
                'comment' => 'links to workflow_steps.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('number_of_days', 'decimal', [
                'default' => null,
                'precision' => 5,
                'scale' => 1,
                'null' => false,
            ])
            ->addColumn('file_name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('file_content', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
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
            ->addIndex('staff_id')
            ->addIndex('staff_leave_type_id')
            ->addIndex('institution_id')
            ->addIndex('assignee_id')
            ->addIndex('status_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        if (!empty($data)) {
            $StaffLeave->insert($data);
            $StaffLeave->saveData();
        }
        // tables relating to the staff absences will no longer be used.
        $this->execute('RENAME TABLE `institution_staff_absences` TO `z_3906_institution_staff_absences`');
        $this->execute('RENAME TABLE `staff_absence_reasons` TO `z_3906_staff_absence_reasons`');

        // Create backup for security_functions and security_role_functions
        $this->execute('CREATE TABLE `z_3906_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_3906_security_functions` SELECT * FROM `security_functions`');
        $this->execute('CREATE TABLE `z_3906_security_role_functions` LIKE `security_role_functions`');
        $this->execute('INSERT INTO `z_3906_security_role_functions` SELECT * FROM `security_role_functions`');

        // modify security_functions to remove all the absence permission
        $sql = 'UPDATE `security_functions` SET
            `_view` = "InstitutionStaffAttendances.index",
            `_edit` = "InstitutionStaffAttendances.edit",
            `_add` = null,
            `_delete` = null,
            `_execute` = null
            WHERE `id` = 1018';
        $this->execute($sql);

        $sql = 'UPDATE `security_role_functions` SET
            `_add` = 0,
            `_delete` = 0,
            `_execute` = 0
            WHERE `security_function_id` = 1018';
        $this->execute($sql);
        // remove the staff absence in institution
        $this->execute('DELETE FROM security_functions WHERE id = 3015');
        // remove the staff absence in directory
        $this->execute('DELETE FROM security_functions WHERE id = 7024');
        // remove the staff absence permission in the security_role_functions for both institution and directory
        $this->execute('DELETE FROM security_role_functions WHERE security_function_id in (3015, 7024)');
        // insert new row to security_fuctions for Staff > Attendance Career Tab for Institutions
        $row = $this->fetchRow('SELECT `order` FROM `security_functions` WHERE `id` = 3016');
        $order = $row['order'];
        $this->execute('UPDATE `security_functions` SET `order` = `order` + 2 WHERE `order` >= ' . $order);

        // insert new row to security_fuctions for Staff > Attendance Career Tab for Directories
        $directoryRow = $this->fetchRow('SELECT `order` FROM `security_functions` WHERE `id` = 7025');
        $directoryorder = $directoryRow['order'];
        $this->execute('UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` >= ' . $directoryorder);

        $table = $this->table('security_functions');
        $data = [
            [
                'id' => 3056,
                'name' => 'Attendances',
                'controller' => 'Staff',
                'module' => 'Institutions',
                'category' => 'Staff - Career',
                'parent_id' => 3000,
                '_view' => 'StaffAttendances.index',
                '_edit' => 'StaffAttendances.edit',
                'order' => $order + 1,
                'visible' => 1,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 3057,
                'name' => 'Attendances Activities',
                'controller' => 'Staff',
                'module' => 'Institutions',
                'category' => 'Staff - Career',
                'parent_id' => 3000,
                '_view' => 'InstitutionStaffAttendanceActivities.index',
                'order' => $order + 2,
                'visible' => 1,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 7071,
                'name' => 'Attendances',
                'controller' => 'Directories',
                'module' => 'Directory',
                'category' => 'Staff - Career',
                'parent_id' => 7000,
                '_view' => 'StaffAttendances.index',
                'order' => $directoryorder + 1,
                'visible' => 1,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
        ];
        $table->insert($data);
        $table->saveData();
    }

    public function down()
    {
        $this->dropTable('institution_staff_attendances');
        $this->dropTable('institution_staff_attendance_activities');
        $this->execute('DROP TABLE IF EXISTS institution_staff_leave');
        $this->execute('RENAME TABLE `z_3906_institution_staff_leave` TO `institution_staff_leave`');
        $this->execute('RENAME TABLE `z_3906_institution_staff_absences` TO `institution_staff_absences`');
        $this->execute('RENAME TABLE `z_3906_staff_absence_reasons` TO `staff_absence_reasons`');
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_3906_security_functions` TO `security_functions`');
        $this->execute('DROP TABLE IF EXISTS `security_role_functions`');
        $this->execute('RENAME TABLE `z_3906_security_role_functions` TO `security_role_functions`');
    }
}
