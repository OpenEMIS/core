<?php
use Migrations\AbstractMigration;

class POCOR6131a extends AbstractMigration
{
    public function up()
    {
        /** START: security_functions table changes */
        $this->execute('DROP TABLE IF EXISTS `zz_6131a_security_functions`');
        $this->execute('CREATE TABLE `zz_6131a_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6131a_security_functions` SELECT * FROM `security_functions`');

        $this->execute("UPDATE `security_functions` SET `controller` = 'Students', `_view` = 'StudentBodyMasses.index|StudentBodyMasses.view', `_edit` = 'StudentBodyMasses.edit', `_add` = 'StudentBodyMasses.add', `_delete` = 'StudentBodyMasses.delete' WHERE `name` = 'Student Body Mass' AND `controller` = 'StudentBodyMasses' AND `module` = 'Institutions' AND `category` = 'Students - Health'");

        $this->execute("UPDATE `security_functions` SET `controller` = 'Students', `_view` = 'StudentInsurances.index|StudentInsurances.view', `_edit` = 'StudentInsurances.edit', `_add` = 'StudentInsurances.add', `_delete` = 'StudentInsurances.delete' WHERE `name` = 'Student Insurance' AND `controller` = 'StudentInsurances' AND `module` = 'Institutions' AND `category` = 'Students - Health'");
        /** END: security_functions table changes */
    }

    //rollback
    public function down()
    {
        /** START: security_functions table changes */
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6131a_security_functions` TO `security_functions`');
        /** END: security_functions table changes */
    }
}
