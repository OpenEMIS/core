<?php
use Migrations\AbstractMigration;

class POCOR5911a extends AbstractMigration
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
        // Backup api_securities table
        $this->execute('CREATE TABLE `zz_5911a_api_securities` LIKE `api_securities`');
        $this->execute('INSERT INTO `zz_5911a_api_securities` SELECT * FROM `api_securities`');
        // End

        $stmt = $this->query('SELECT * FROM api_securities ORDER BY id DESC limit 1');
        $rows = $stmt->fetchAll();
        $uniqueId = $rows[0]['id'];
		
		$this->insert('api_securities', [
            'id' => $uniqueId +1,
            'name' => 'Payslips',
            'model' => 'Staff.StaffPayslips',
            'index' => 0,
            'view' => 0,
            'add' => 1,
            'edit' => 0,
            'delete' => 0,
            'execute' => 0
        ]);
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `api_securities`');
        $this->execute('RENAME TABLE `zz_5911a_api_securities` TO `api_securities`');
    }
}
