<?php

use Phinx\Migration\AbstractMigration;

class POCOR4356c extends AbstractMigration
{
	public function up()
    {
		$stmt = $this->query('SELECT * FROM security_functions ORDER BY id DESC limit 1');
        $rows = $stmt->fetchAll();
        $uniqueId = $rows[0]['id'];
        $securityFunctionData = [
            [
                'id' => $uniqueId + 1,
                'name' => 'Timetable',
                'controller' => 'Institutions',
                'module' => 'Institutions',
                'category' => 'Schedules',
                'parent_id' => 1000,
                '_view' => 'Institutions.ScheduleTimetableOverview',
				'_edit' => 'Institutions.ScheduleTimetableOverview.edit',
                'order' => $uniqueId + 1,
                'visible' => 1,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => $uniqueId + 2,
                'name' => 'Intervals',
                'controller' => 'Institutions',
                'module' => 'Institutions',
                'category' => 'Schedules',
                'parent_id' => 1000,
                '_view' => 'Institutions.ScheduleIntervals',
				'_edit' => 'Institutions.ScheduleIntervals.edit',
                'order' => $uniqueId + 2,
                'visible' => 1,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => $uniqueId + 3,
                'name' => 'Terms',
                'controller' => 'Institutions',
                'module' => 'Institutions',
                'category' => 'Schedules',
                'parent_id' => 1000,
                '_view' => 'Institutions.ScheduleTerms',
				'_edit' => 'Institutions.ScheduleTerms.edit',
                'order' => $uniqueId + 3,
                'visible' => 1,
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