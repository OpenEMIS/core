<?php
use Migrations\AbstractMigration;

class POCOR5829 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_5829_api_securities_scopes` LIKE `api_securities_scopes`');
        $this->execute('INSERT INTO `zz_5829_api_securities_scopes` SELECT * FROM `api_securities_scopes`');
        // End

        $this->execute("UPDATE `api_securities` SET `execute` = '1' WHERE `name` = 'User Athentication' AND `model` = 'User.Users'");
        
		
		$this->insert('api_securities_scopes', [
            'api_security_id' => 1031,
            'api_scope_id' => 1,
            'index' => 0,
            'view' => 0,
            'add' => 0,
            'edit' => 0,
            'delete' => 0,
            'execute' => 1,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 2,
            'created' => '2020-12-06 12:53:31',
        ]);
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `api_securities_scopes`');
        $this->execute('RENAME TABLE `zz_5829_api_securities_scopes` TO `api_securities_scopes`');
    }
}
