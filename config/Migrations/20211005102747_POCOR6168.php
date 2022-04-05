<?php
use Migrations\AbstractMigration;

class POCOR6168 extends AbstractMigration
{
    public function up()
    {
        /** START: security_functions table changes */
        $this->execute('DROP TABLE IF EXISTS `zz_6168_security_functions`');
        $this->execute('CREATE TABLE `zz_6168_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6168_security_functions` SELECT * FROM `security_functions`');

        $this->execute("UPDATE `security_functions` SET `controller` = 'Institutions', `_view` = 'InstitutionBuses.index|InstitutionBuses.view', `_edit` = 'InstitutionBuses.edit', `_add` = 'InstitutionBuses.add', `_delete` = 'InstitutionBuses.delete', `_execute` = 'InstitutionBuses.excel' WHERE `name` = 'Buses' AND `controller` = 'InstitutionBuses' AND `module` = 'Institutions' AND `category` = 'Transport'");
        /** END: security_functions table changes */
    }

    //rollback
    public function down()
    {
        /** START: security_functions table changes */
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6168_security_functions` TO `security_functions`');
        /** END: security_functions table changes */
    }
}
