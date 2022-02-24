<?php
use Migrations\AbstractMigration;

class POCOR6582 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_6582_api_securities` LIKE `api_securities`');
        $this->execute('INSERT INTO `zz_6582_api_securities` SELECT * FROM `api_securities`');
        $this->execute('CREATE TABLE `zz_6582_api_securities_scopes` LIKE `api_securities_scopes`');
        $this->execute('INSERT INTO `zz_6582_api_securities_scopes` SELECT * FROM `api_securities_scopes`');
        // End

        $stmt = $this->query('SELECT * FROM api_securities ORDER BY id DESC limit 1');
        $rows = $stmt->fetchAll();
        $uniqueId = $rows[0]['id'];

        $this->insert('api_securities', [
            'id' => $uniqueId +1,
            'name' => 'Security Group Users',
            'model' => 'Security.SecurityGroupUsers',
            'index' => 1,
            'view' => 1,
            'add' => 0,
            'edit' => 0,
            'delete' => 0,
            'execute' => 0
        ]);

        $apiSecurityGroupUsersID = $this->query("SELECT * FROM api_securities WHERE `name` = 'Security Group Users' AND `model` = 'Security.SecurityGroupUsers'");
        $securityGroupUsersID = $apiSecurityGroupUsersID->fetchAll();
        $securityGroupUsersValue = $securityGroupUsersID[0]['id'];

        $getApiScope = $this->query("SELECT * FROM api_scopes WHERE `name` = 'API'");
        $apiScope = $getApiScope->fetchAll();
        $apiScopeId = $apiScope[0]['id'];

        $checkDataExist = $this->query("SELECT * FROM api_securities_scopes WHERE api_security_id = $securityGroupUsersValue");

        $data = $checkDataExist->fetchAll();
		if(empty($data)){
            $this->insert('api_securities_scopes', [
                'api_security_id' => $securityGroupUsersValue,
                'api_scope_id' => $apiScopeId,
                'index' => 1,
                'view' => 1,
                'add' => 0,
                'edit' => 0,
                'delete' => 0,
                'execute' => 0,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]);
        }
        else{
            $this->execute("UPDATE `api_securities_scopes` SET `view` = 1 AND `index` = 1 WHERE `api_security_id` = $securityGroupUsersValue");
        }
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `api_securities_scopes`');
        $this->execute('RENAME TABLE `zz_6582_api_securities_scopes` TO `api_securities_scopes`');
        $this->execute('DROP TABLE IF EXISTS `api_securities`');
        $this->execute('RENAME TABLE `zz_6582_api_securities` TO `api_securities`');
    }
}
