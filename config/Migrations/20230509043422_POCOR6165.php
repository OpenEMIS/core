<?php
use Migrations\AbstractMigration;

/*
POCOR-6165 adding permission for exceute in Students(Finance)
*/

class POCOR6165 extends AbstractMigration
{
    public function up()
    {
        // Creating backup
        $this->execute('DROP TABLE IF EXISTS `zz_6165_security_functions`');
        $this->execute('CREATE TABLE `zz_6165_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6165_security_functions` SELECT * FROM `security_functions`');

        //setting permission for execute excel
        $this->execute("UPDATE `security_functions` SET
        `_execute` = 'StudentFees.excel'
        WHERE `name` = 'Students' AND `controller` = 'Institutions' AND `module` = 'Institutions' AND `category` = 'Finance'");

    }


    public function down()
    {
        //rollback
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6165_security_functions` TO `security_functions`');
    }

}
