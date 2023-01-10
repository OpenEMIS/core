<?php
use Migrations\AbstractMigration;

class POCOR6156 extends AbstractMigration
{
    public function up()
    {
        /** START: security_functions table changes */
        $this->execute('DROP TABLE IF EXISTS `zz_6156_security_functions`');
        $this->execute('CREATE TABLE `zz_6156_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6156_security_functions` SELECT * FROM `security_functions`');

        $this->execute("UPDATE `security_functions` SET `_execute` = 'Risks.excel|InstitutionStudentRisks.excel|InstitutionStudentRisks.excel' WHERE `name` = 'Risks' AND `controller` = 'Institutions' AND `module` = 'Institutions' AND `category` = 'Students'");

        /** END: security_functions table changes */
    }

    
    //rollback
    public function down()
    {
        /** START: security_functions table changes */
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6156_security_functions` TO `security_functions`');
        /** END: security_functions table changes */
    }
}
