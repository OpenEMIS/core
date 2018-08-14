<?php
use Phinx\Migration\AbstractMigration;

class POCOR3906 extends AbstractMigration
{
    public function up()
    {
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
            ->addColumn('old_value', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('new_value', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
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

        // institution_staff_leave
        $this->execute('RENAME TABLE `institution_staff_leave` TO `z_3906_institution_staff_leave`');
        $this->execute('DROP TABLE IF EXISTS institution_staff_leave');

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
            ->addColumn('full_day', 'integer', [
                'default' => null,
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
            ->addColumn('number_of_days', 'integer', [
                'default' => null,
                'limit' => 3,
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

        $this->execute('
            INSERT INTO `institution_staff_leave` 
                (`id`, `date_from`, `date_to`, `comments`, `staff_id`, `staff_leave_type_id`, `institution_id`, `assignee_id`, `status_id`, `number_of_days`, `file_name`, `file_content`, `modified_user_id`, `modified`, `created_user_id`, `created`)
            SELECT `id`, `date_from`, `date_to`, `comments`, `staff_id`, `staff_leave_type_id`, `institution_id`, `assignee_id`, `status_id`, `number_of_days`, `file_name`, `file_content`, `modified_user_id`, `modified`, `created_user_id`, `created`
            FROM `z_3906_institution_staff_leave`
        ');
    }

    public function down()
    {   
        $this->dropTable('institution_staff_attendances');
        $this->dropTable('institution_staff_attendance_activities');

        $this->execute('DROP TABLE IF EXISTS institution_staff_leave');
        $this->execute('RENAME TABLE `z_3906_institution_staff_leave` TO `institution_staff_leave`');
    }
}
