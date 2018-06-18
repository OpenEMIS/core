<?php

use Cake\Utility\Text;
use Phinx\Migration\AbstractMigration;

class POCOR4664 extends AbstractMigration
{
    public function up()
    {
        $data = [
            [
                'id' => Text::uuid(),
                'module' => 'Institutions',
                'field' => 'code',
                'module_name' => 'Institutions',
                'field_name' => 'Code',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'Institutions',
                'field' => 'name',
                'module_name' => 'Institutions',
                'field_name' => 'Name',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];

        $this->insert('labels', $data);
    }

    public function down()
    { 

        $this->execute("DELETE FROM `labels` WHERE `module` = 'Institutions' and `field` = 'code' ");
        $this->execute("DELETE FROM `labels` WHERE `module` = 'Institutions' and `field` = 'name' ");
       
    }
}
