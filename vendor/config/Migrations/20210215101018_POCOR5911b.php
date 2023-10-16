<?php
use Migrations\AbstractMigration;

class POCOR5911b extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_5911b_api_securities` LIKE `api_securities`');
        $this->execute('INSERT INTO `zz_5911b_api_securities` SELECT * FROM `api_securities`');
        $this->execute('CREATE TABLE `zz_5911b_api_securities_scopes` LIKE `api_securities_scopes`');
        $this->execute('INSERT INTO `zz_5911b_api_securities_scopes` SELECT * FROM `api_securities_scopes`');
        // End
        $this->execute("UPDATE `api_securities` SET `add` = 1 WHERE `name` = 'Payslips' AND `model` = 'Staff.StaffPayslips'");

        $uerAuthenticationSecurityId = $this->query("SELECT * FROM api_securities WHERE `name` = 'Payslips' AND `model` = 'Staff.StaffPayslips'");
        $securityId = $uerAuthenticationSecurityId->fetchAll();
        $securityValue = $securityId[0]['id'];

        $getApiScope = $this->query("SELECT * FROM api_scopes WHERE `name` = 'API'");
        $apiScope = $getApiScope->fetchAll();
        $apiScopeId = $apiScope[0]['id'];
        

        $checkDataExist = $this->query("SELECT * FROM api_securities_scopes WHERE api_security_id = $securityValue");
       
        $data = $checkDataExist->fetchAll();
		if(empty($data)){
            $this->insert('api_securities_scopes', [
                'api_security_id' => $securityValue,
                'api_scope_id' => $apiScopeId,
                'index' => 0,
                'view' => 0,
                'add' => 1,
                'edit' => 0,
                'delete' => 0,
                'execute' => 0,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]);
        }
        else{
            $this->execute("UPDATE `api_securities_scopes` SET `add` = 1 WHERE `api_security_id` = $securityValue");
        }
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `api_securities_scopes`');
        $this->execute('RENAME TABLE `zz_5911b_api_securities_scopes` TO `api_securities_scopes`');
        $this->execute('DROP TABLE IF EXISTS `api_securities`');
        $this->execute('RENAME TABLE `zz_5911b_api_securities` TO `api_securities`');
    }
}
