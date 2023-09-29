<?php

use Migrations\AbstractMigration;

class POCOR5320 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {        
		// backup 
        $this->execute('CREATE TABLE `z_5320_api_securities` LIKE `api_securities`');
		$this->execute('INSERT INTO `z_5320_api_securities` SELECT * FROM `api_securities`');
		
		$stmt = $this->query('SELECT * FROM api_securities ORDER BY id DESC limit 1');
        $rows = $stmt->fetchAll();
		$uniqueId = $rows[0]['id'];
		
		$apiSecuritiesData = [
            [
                'id' => $uniqueId + 1,
				'name' => 'UserGender',
				'model' => 'User.Genders',
				'index' => 1,
				'view' => 1,
				'add' => 0,
				'edit' => 0,
				'delete' => 0,
				'execute' => 0 
            ]
        ];

        $apiSecuritiesTable = $this->table('api_securities');
        $apiSecuritiesTable->insert($apiSecuritiesData);
        $apiSecuritiesTable->saveData();
		
        // end 
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `api_securities`');
        $this->execute('RENAME TABLE `z_5320_api_securities` TO `api_securities`');
    }
}
