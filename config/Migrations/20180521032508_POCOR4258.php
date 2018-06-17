<?php

use Phinx\Migration\AbstractMigration;

class POCOR4258 extends AbstractMigration
{
    public function up()
    {
        // import mapping
        $data = [
            [
                'model' => 'Institution.InstitutionPositions',
                'column_name' => 'position_no',
                'description' => '(Leave as blank to auto generate)',
                'order' => 1,
                'is_optional' => 1,
                'foreign_key' => 0,
                'lookup_plugin' => null,
                'lookup_model' => null,
                'lookup_column' => null
            ],
            [
                'model' => 'Institution.InstitutionPositions',
                'column_name' => 'staff_position_title_id',
                'description' => '',
                'order' => 2,
                'is_optional' => 0,
                'foreign_key' => 2,
                'lookup_plugin' => 'Institution',
                'lookup_model' => 'StaffPositionTitles',
                'lookup_column' => 'id'
            ],
            [
                'model' => 'Institution.InstitutionPositions',
                'column_name' => 'staff_position_grade_id',
                'description' => '',
                'order' => 3,
                'is_optional' => 0,
                'foreign_key' => 1,
                'lookup_plugin' => 'Institution',
                'lookup_model' => 'StaffPositionGrades',
                'lookup_column' => 'id'
            ],
            [
                'model' => 'Institution.InstitutionPositions',
                'column_name' => 'is_homeroom',
                'description' => '(Leave as blank for Non-Teaching type)',
                'order' => 4,
                'is_optional' => 1,
                'foreign_key' => 3,
                'lookup_plugin' => null,
                'lookup_model' => 'HomeroomTeacher',
                'lookup_column' => 'id'
            ],
            [
                'model' => 'Institution.InstitutionPositions',
                'column_name' => 'status_id',
                'description' => '',
                'order' => 5,
                'is_optional' => 0,
                'foreign_key' => 2,
                'lookup_plugin' => 'Workflow',
                'lookup_model' => 'WorkflowSteps',
                'lookup_column' => 'id'
            ]
        ];

        $this->insert('import_mapping', $data);

        // locale_contents
        $localeData = [
            [
                'en' => '(Leave as blank to auto generate)',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => '(Leave as blank for Non-Teaching type)',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Selected value does not match with Staff Position Title Type',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Invalid status id',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('locale_contents', $localeData);

        // security_functions
        $row = $this->fetchRow('SELECT `order` FROM `security_functions` WHERE `id` = 1017'); // Order after Positions
        $order = $row['order'];

        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` >= ' . $order);

        $securityData = [
            [
                'id' => 1084,
                'name' => 'Import Institution Positions',
                'controller' => 'Institutions',
                'module' => 'Institutions',
                'category' => 'Staff',
                'parent_id' => 8,
                '_view' => null,
                '_edit' => null,
                '_add' => null,
                '_delete' => null,
                '_execute' => 'ImportInstitutionPositions.add|ImportInstitutionPositions.template|ImportInstitutionPositions.results|ImportInstitutionPositions.downloadFailed|ImportInstitutionPositions.downloadPassed',
                'order' => $order,
                'visible' => 1,
                'description' => null,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];

        $this->insert('security_functions', $securityData);
    }

    public function down()
    {
        // import mapping
        $this->execute("DELETE FROM `import_mapping` WHERE `model` = 'Institution.InstitutionPositions'");

        // locale_contents
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = '(Leave as blank to auto generate)'");
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = '(Leave as blank for Non-Teaching type)'");
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'Selected value does not match with Staff Position Title Type'");
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'Invalid status id'");

        // security_functions
        $row = $this->fetchRow('SELECT `order` FROM `security_functions` WHERE `id` = 1017'); // Leave id
        $order = $row['order'];

        $this->execute("DELETE FROM `security_functions` WHERE `id` = 1084");
        $this->execute("UPDATE security_functions SET `order` = `order` - 1 WHERE `order` >= " . $order);
    }
}
