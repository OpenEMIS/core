<?php

use Phinx\Migration\AbstractMigration;

class POCOR4577 extends AbstractMigration
{
    public function up()
    {
        // import_mapping
        $data = [
            [
                'model' => 'Institution.StaffLeave',
                'column_name' => 'staff_leave_type_id',
                'description' => '',
                'order' => 1,
                'is_optional' => 0,
                'foreign_key' => 2,
                'lookup_plugin' => 'Staff',
                'lookup_model' => 'StaffLeaveTypes',
                'lookup_column' => 'id'
            ],
            [
                'model' => 'Institution.StaffLeave',
                'column_name' => 'date_from',
                'description' => '( DD/MM/YYYY )',
                'order' => 2,
                'is_optional' => 0,
                'foreign_key' => 0,
                'lookup_plugin' => null,
                'lookup_model' => null,
                'lookup_column' => null
            ],
            [
                'model' => 'Institution.StaffLeave',
                'column_name' => 'date_to',
                'description' => '( DD/MM/YYYY )',
                'order' => 3,
                'is_optional' => 0,
                'foreign_key' => 0,
                'lookup_plugin' => null,
                'lookup_model' => null,
                'lookup_column' => null
            ],
            [
                'model' => 'Institution.StaffLeave',
                'column_name' => 'comments',
                'description' => '(Optional)',
                'order' => 4,
                'is_optional' => 1,
                'foreign_key' => 0,
                'lookup_plugin' => null,
                'lookup_model' => null,
                'lookup_column' => null
            ],
            [
                'model' => 'Institution.StaffLeave',
                'column_name' => 'status_id',
                'description' => '',
                'order' => 5,
                'is_optional' => 0,
                'foreign_key' => 2,
                'lookup_plugin' => 'Workflow',
                'lookup_model' => 'WorkflowSteps',
                'lookup_column' => 'id'
            ],
        ];

        $this->insert('import_mapping', $data);

        // locale_contents
        $localeData = [
            [
                'en' => 'Administration - Record Imported',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Selected value does not match with Staff Leave Type',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Staff Leave Type Id',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'No active institution',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'No staff id found',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];

        $this->insert('locale_contents', $localeData);

        // security_functions
    }

    public function down()
    {
        // import_mapping
        $this->execute("DELETE FROM import_mapping WHERE model = 'Institution.StaffLeave'");
        
        // locale_contents
        $this->execute("DELETE FROM locale_contents WHERE en = 'Administration - Record Imported'");

        // security_functions
    }
}
