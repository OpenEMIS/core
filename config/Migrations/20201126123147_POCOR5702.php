<?php
use Migrations\AbstractMigration;

class POCOR5702 extends AbstractMigration
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
        $stmt = $this->query('SELECT * FROM api_securities ORDER BY id DESC limit 1');
        $rows = $stmt->fetchAll();
        $uniqueId = $rows[0]['id'];

        $apiSecuritiesData = [
            [
                'id' => $uniqueId + 2,
                'name' => 'User Authentication',
                'model' => 'User.Users',
                'index' => 0,
                'view' => 0,
                'add' => 0,
                'edit' => 0,
                'delete' => 0,
                'execute' => 1 
            ]
        ];

        $apiSecuritiesTable = $this->table('api_securities');
        $apiSecuritiesTable->insert($apiSecuritiesData);
        $apiSecuritiesTable->saveData();
    }
}
