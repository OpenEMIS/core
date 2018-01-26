<?php

use Cake\Utility\Text;
use Phinx\Migration\AbstractMigration;

class POCOR4377 extends AbstractMigration
{
    // commit
    public function up()
    {
        // labels
        $labels = [
            [
                'id' => Text::uuid(),
                'module' => 'Institutions',
                'field' => 'postal_code',
                'module_name' => 'Institutions',
                'field_name' => 'Postal Code',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'StudentUser',
                'field' => 'postal_code',
                'module_name' => 'Institutions -> Students -> General',
                'field_name' => 'Postal Code',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'StaffUser',
                'field' => 'postal_code',
                'module_name' => 'Institutions -> Staff -> General',
                'field_name' => 'Postal Code',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'Directories',
                'field' => 'postal_code',
                'module_name' => 'Directories -> General',
                'field_name' => 'Postal Code',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'Profiles',
                'field' => 'postal_code',
                'module_name' => 'Profiles -> General',
                'field_name' => 'Postal Code',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'ExaminationCentres',
                'field' => 'postal_code',
                'module_name' => 'Administration -> Examinations -> Centres',
                'field_name' => 'Postal Code',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('labels', $labels);
    }

    // rollback
    public function down()
    {
        // revert labels
        $this->execute("DELETE FROM `labels` WHERE `field` = 'postal_code'");
    }
}
