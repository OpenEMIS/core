<?php
use Migrations\AbstractMigration;

class POCOR5845 extends AbstractMigration
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
        // backup the table
        $this->execute('CREATE TABLE `z_5845_api_securities` LIKE `api_securities`');
        $this->execute('INSERT INTO `z_5845_api_securities` SELECT * FROM `api_securities`');

        //inserting new values 
        $stmt = $this->query('SELECT * FROM api_securities ORDER BY id DESC limit 1');
        $rows = $stmt->fetchAll();
        $uniqueId = $rows[0]['id'];

        $apiSecuritiesData = [
            [
                'id' => $uniqueId + 1,
                'name' => 'Institution Absence Types',
                'model' => 'Institution.AbsenceTypes',
                'index' => 1,
                'view' => 1,
                'add' => 1,
                'edit' => 1,
                'delete' => 0,
                'execute' => 0 
            ],
            [ 'id' => $uniqueId + 2, 
              'name' => 'Institution Student Absence Reasons',
                'model' => 'Institution.StudentAbsenceReasons',
                'index' => 1,
                'view' => 1,
                'add' => 1,
                'edit' => 1,
                'delete' => 0,
                'execute' => 0 
            ],
            [ 'id' => $uniqueId + 3, 'name' => 'Institution Staff Attendances',
                'model' => 'Staff.InstitutionStaffAttendances',
                'index' => 1,
                'view' => 1,
                'add' => 1,
                'edit' => 1,
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
        $this->execute('DROP TABLE IF EXISTS `api_securities`');
        $this->execute('RENAME TABLE `z_5845_api_securities` TO `api_securities`');
    }
}
