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
        // $this->execute("UPDATE `api_securities` SET `execute` = '1' WHERE `name` = 'User Athentication' AND `model` = 'User.Users'");
        // Backup locale_contents table
        // $this->execute('CREATE TABLE `zz_5829b_api_securities_scopes` LIKE `api_securities_scopes`');
        // $this->execute('INSERT INTO `zz_5829b_api_securities_scopes` SELECT * FROM `api_securities_scopes`');
        // End
        $dataExist = 1;
        $uerAuthenticationSecurityId = $this->query("SELECT * FROM api_securities WHERE `model` = 'User.Users'");
        $securityId = $uerAuthenticationSecurityId->fetchAll();
        foreach($securityId AS $val){
            if($val['name']== 'User Authentication'){
                $securityValue = $val['id'];
            }
        }

        $getApiScope = $this->query("SELECT * FROM api_scopes WHERE `name` = 'API'");
        $apiScope = $getApiScope->fetchAll();
        $apiScopeId = $apiScope[0]['id'];
        
        $todayDate = Date::now();

        $checkDataExist = $this->query("SELECT * FROM api_securities_scopes");
       
        $data = $checkDataExist->fetchAll();
        foreach($data AS $val2){
            if($val2['api_security_id'] == $securityValue){
                $dataExist = 0;
            }
        }
        echo  $dataExist;exit;
		if($dataExist == 0){
            
            $this->insert('api_securities_scopes', [
                'api_security_id' => $securityValue,
                'api_scope_id' => $apiScopeId,
                'index' => 0,
                'view' => 0,
                'add' => 0,
                'edit' => 0,
                'delete' => 0,
                'execute' => 1,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => 0,
                'created' => $todayDate,
            ]);
        }
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `api_securities_scopes`');
        $this->execute('RENAME TABLE `zz_5829b_api_securities_scopes` TO `api_securities_scopes`');
    }
}
