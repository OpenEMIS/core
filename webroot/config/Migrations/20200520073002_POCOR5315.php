<?php

use Phinx\Migration\AbstractMigration;

class POCOR5315 extends AbstractMigration
{
    // commit
    public function up()
    {
		$this->execute('CREATE TABLE `z_5315_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_5315_security_functions` SELECT * FROM `security_functions`');
		// backup 
        $this->execute('CREATE TABLE `z_5315_api_securities` LIKE `api_securities`');
		$this->execute('INSERT INTO `z_5315_api_securities` SELECT * FROM `api_securities`');
		
        // security_functions
        $this->execute('UPDATE `security_functions` SET `name` = "Mark Own Attendance", '
                . '`_view` = "InstitutionStaffAttendances.index|InstitutionStaffAttendances.ownview", '
                . '`_edit` = "InstitutionStaffAttendances.edit|InstitutionStaffAttendances.ownedit" '
                . 'WHERE `id` = 1018 AND `name` = "Attendance" AND `category` = "Staff" AND `_view` = "InstitutionStaffAttendances.index"');

        $securityFunctionsData = [
            [
                'name' => 'Mark Others Attendance',
                'controller' => 'Institutions',
                'module' => 'Institutions',
                'category' => 'Staff',
                'parent_id' => 8,
                '_view' => 'InstitutionStaffAttendances.index|InstitutionStaffAttendances.otherview',
                '_edit' => 'InstitutionStaffAttendances.edit|InstitutionStaffAttendances.otheredit',  
		'_add' => null,
                '_delete' => null,
		'_execute' => 'InstitutionStaffAttendances.excel',
                'order' => 67,
                'visible' => 1,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        
        $this->insert('security_functions', $securityFunctionsData);
		
		$stmt = $this->query('SELECT * FROM api_securities ORDER BY id DESC limit 1');
        $rows = $stmt->fetchAll();
		$uniqueId = $rows[0]['id'];
		
		$apiSecuritiesData = [
            [
                'id' => $uniqueId + 1,
				'name' => 'Security Role Functions',
				'model' => 'Security.SecurityRoleFunctions',
				'index' => 1,
				'view' => 0,
				'add' => 0,
				'edit' => 0,
				'delete' => 0,
				'execute' => 0 
            ],
			[
                'id' => $uniqueId + 2,
                'name' => 'Staff',
                'model' => 'Institution.Staff',
                'index' => 1,
                'view' => 0,
                'add' => 0,
                'edit' => 0,
                'delete' => 0,
                'execute' => 0
            ]
        ];

        $apiSecuritiesTable = $this->table('api_securities');
        $apiSecuritiesTable->insert($apiSecuritiesData);
        $apiSecuritiesTable->saveData();
    }

    // rollback
    public function down()
    {
        $this->dropTable('security_functions');
        $this->table('z_5315_security_functions')->rename('security_functions');
		$this->dropTable('api_securities');
        $this->table('z_5315_api_securities')->rename('api_securities');
    }
}
