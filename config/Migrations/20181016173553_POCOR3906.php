<?php
use Cake\I18n\Date;
use Phinx\Migration\AbstractMigration;
use Cake\ORM\TableRegistry;
use Cake\Utility\Security;

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

        $staffAbsences = $this->fetchAll('SELECT `institution_staff_absences`.`*`, `staff_absence_reasons`.`name`FROM `institution_staff_absences` LEFT JOIN `staff_absence_reasons`on `institution_staff_absences`.`staff_absence_reason_id` = `staff_absence_reasons`.`id`');
        $tmp = [];
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
                    $AcademicPeriods->aliasField('code') . ' <> ' => 'all'
                ])
                ->extract('id')
                ->first();
            for ($i = 0; $i <= $days; $i++) {
                $hashString = [$value['staff_id'], $value['institution_id'], $academicPeriodId, date('Y-m-d', strtotime($value['start_date']. ' + '.$i.' days'))];
                $id = Security::hash(implode(',', $hashString), 'sha256');
                $tmp[] = [
                    'id' => $id,
                    'staff_id' => $value['staff_id'],
                    'institution_id' => $value['institution_id'],
                    'academic_period_id' => $academicPeriodId,
                    'date' => date('Y-m-d', strtotime($value['start_date']. ' + '.$i.' days')),
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
        if (!empty($tmp)) {
            $InstitutionStaffAttendances->insert($tmp);
            $InstitutionStaffAttendances->saveData();
        }

        // institution_staff_leave
        $institutionStaffLeaveOrigin = $InstitutionStaffLeave
            ->find()
            ->toArray();

        $data = [];
        foreach ($institutionStaffLeaveOrigin as $key => $value) {
            $startDate = $value->date_from;
            $academicPeriodId = $AcademicPeriods->getAcademicPeriodIdByDate($startDate);
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
    }

    public function down()
    {
        $this->dropTable('institution_staff_attendances');
        $this->dropTable('institution_staff_attendance_activities');
        $this->execute('DROP TABLE IF EXISTS institution_staff_leave');
        $this->execute('RENAME TABLE `z_3906_institution_staff_leave` TO `institution_staff_leave`');
        $this->execute('RENAME TABLE `z_3906_institution_staff_absences` TO `institution_staff_absences`');
        $this->execute('RENAME TABLE `z_3906_staff_absence_reasons` TO `staff_absence_reasons`');
    }
}
