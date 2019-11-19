<?php

use Phinx\Migration\AbstractMigration;

class POCOR4356a extends AbstractMigration
{
	public function up()
    {
        $stmt = $this->query('SELECT * FROM security_functions ORDER BY id DESC limit 1');
        $rows = $stmt->fetchAll();
        $uniqueId = $rows[0]['id'];
        $securityFunctionData = [
            [
                'id' => $uniqueId + 1,
                'name' => 'Students',
                'controller' => 'Institutions',
                'module' => 'Institutions',
                'category' => 'Timetable',
                'parent_id' => 1000,
                '_view' => 'Students.StudentScheduleTimetable',
                'order' => $uniqueId + 1,
                'visible' => 0,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => $uniqueId + 2,
                'name' => 'Staff',
                'controller' => 'Institutions',
                'module' => 'Institutions',
                'category' => 'Timetable',
                'parent_id' => 1000,
                '_view' => 'Staff.ScheduleTimetable',
                'order' => $uniqueId + 2,
                'visible' => 0,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];

        $securityFunctionsTable = $this->table('security_functions');
        $securityFunctionsTable->insert($securityFunctionData);
        $securityFunctionsTable->saveData();
    }
	
    // rollback
    public function down()
    {
		$this->execute('DELETE FROM security_functions WHERE category = "Timetable"');
    }
}