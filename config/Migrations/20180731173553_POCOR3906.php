<?php
use Phinx\Migration\AbstractMigration;

class POCOR3906 extends AbstractMigration
{
    public function up()
    {
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
                'start_time', 'time', [
                'default' => null,
                'limit' => null,
                'null' => true,
                ]
            )
            ->addColumn(
                'end_time', 'time', [
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
    }

    public function down()
    {   
        $this->dropTable('institution_staff_attendances');
    }
}
