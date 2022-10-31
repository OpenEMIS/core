<?php
use Phinx\Migration\AbstractMigration;

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
        // Backup locale_contents table
        $this->execute('CREATE TABLE `zz_5702_api_securities` LIKE `api_securities`');
        $this->execute('INSERT INTO `zz_5702_api_securities` SELECT * FROM `api_securities`');
        // End

        $stmt = $this->query('SELECT * FROM api_securities ORDER BY id DESC limit 1');
        $rows = $stmt->fetchAll();
        $uniqueId = $rows[0]['id'];
		
		$this->insert('api_securities', [
            'id' => $uniqueId +1,
            'name' => 'User Authentication',
            'model' => 'User.Users',
            'index' => 0,
            'view' => 0,
            'add' => 0,
            'edit' => 0,
            'delete' => 0,
            'execute' => 0
        ]);
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `api_securities`');
        $this->execute('RENAME TABLE `zz_5702_api_securities` TO `api_securities`');
    }
}
