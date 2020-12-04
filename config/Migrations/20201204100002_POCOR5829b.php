<?php
use Migrations\AbstractMigration;
use Cake\I18n\Date;

class POCOR5829b extends AbstractMigration
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
        // Backup table
        $this->execute('CREATE TABLE `zz_5829b_api_securities` LIKE `api_securities`');
        $this->execute('INSERT INTO `zz_5829b_api_securities` SELECT * FROM `api_securities`');
        $this->execute('CREATE TABLE `zz_5829b_api_securities_scopes` LIKE `api_securities_scopes`');
        $this->execute('INSERT INTO `zz_5829b_api_securities_scopes` SELECT * FROM `api_securities_scopes`');
        // End
        $this->execute("UPDATE `api_securities` SET `execute` = 1 WHERE `name` = 'User Authentication' AND `model` = 'User.Users'");

        $uerAuthenticationSecurityId = $this->query("SELECT * FROM api_securities WHERE `name` = 'User Authentication' AND `model` = 'User.Users'");
        $securityId = $uerAuthenticationSecurityId->fetchAll();
        $securityValue = $securityId[0]['id'];

        $getApiScope = $this->query("SELECT * FROM api_scopes WHERE `name` = 'API'");
        $apiScope = $getApiScope->fetchAll();
        $apiScopeId = $apiScope[0]['id'];
        
        $todayDate = Date::now();

        $checkDataExist = $this->query("SELECT * FROM api_securities_scopes WHERE api_security_id = $securityValue");
       
        $data = $checkDataExist->fetchAll();
		if(empty($data)){
            $this->insert('api_securities_scopes', [
                'api_security_id' => $securityValue,
                'api_scope_id' => $apiScopeId,
                'index' => 0,
                'view' => 0,
                'add' => 0,
                'edit' => 0,
                'delete' => 0,
                'execute' => 1,
                'created_user_id' => 1,
                'created' => $todayDate,
            ]);
        }
        else{
            $this->execute("UPDATE `api_securities_scopes` SET `execute` = 1 WHERE `api_security_id` = $securityValue");
        }
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `api_securities_scopes`');
        $this->execute('RENAME TABLE `zz_5829b_api_securities_scopes` TO `api_securities_scopes`');
        $this->execute('DROP TABLE IF EXISTS `api_securities`');
        $this->execute('RENAME TABLE `zz_5829b_api_securities` TO `api_securities`');
    }
}
