<?php
use Migrations\AbstractMigration;

class POCOR6002 extends AbstractMigration
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
        $this->execute('DROP TABLE IF EXISTS `zz_6002_security_functions`');
        $this->execute('CREATE TABLE `zz_6002_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6002_security_functions` SELECT * FROM `security_functions`');

        $this->execute('DROP TABLE IF EXISTS `zz_6002_security_role_functions`');
        $this->execute('CREATE TABLE `zz_6002_security_role_functions` LIKE `security_role_functions`');
        $this->execute('INSERT INTO `zz_6002_security_role_functions` SELECT * FROM `security_role_functions`');

        $securityFunctionsQuery = $this->query("SELECT * FROM security_functions WHERE `name` = 'My Subjects' AND `controller` ='Institutions' AND `module`='Institutions' AND `category`='Academic'");
        $securityFunctionsData = $securityFunctionsQuery->fetchAll();
        $securityFunctionsId = $securityFunctionsData[0]['id'];
        if(!empty($securityFunctionsQuery)){
            $this->execute("UPDATE `security_role_functions` SET `_edit` = 1 WHERE `security_role_id`=2 AND `security_function_id` = $securityFunctionsId");
        }
    }

    //rollback
    public function down()
    {
            $this->execute('DROP TABLE IF EXISTS `security_functions`');
            $this->execute('RENAME TABLE `zz_6002_security_functions` TO `security_functions`');
            $this->execute('DROP TABLE IF EXISTS `security_role_functions`');
            $this->execute('RENAME TABLE `zz_6002_security_role_functions` TO `security_role_functions`');
    }
}
