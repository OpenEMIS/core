<?php
use Migrations\AbstractMigration;

class POCOR6151 extends AbstractMigration
{
    public function up()
    {
        /** START: security_functions table changes */
        $this->execute('DROP TABLE IF EXISTS `zz_6151_security_functions`');
        $this->execute('CREATE TABLE `zz_6151_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6151_security_functions` SELECT * FROM `security_functions`');

        $this->execute("UPDATE `security_functions` SET `controller` = 'Institutions', `_view` = 'InfrastructureProjects.index|InfrastructureProjects.view|InfrastructureProjects.download', `_edit` = 'InfrastructureProjects.edit', `_add` = 'InfrastructureProjects.add', `_delete` = 'InfrastructureProjects.remove', `_execute` = 'InfrastructureProjects.excel' WHERE `name` = 'Infrastructure Project' AND `controller` = 'InfrastructureProjects' AND `module` = 'Institutions' AND `category` = 'Details'");
        /** END: security_functions table changes */
    }

    //rollback
    public function down()
    {
        /** START: security_functions table changes */
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6151_security_functions` TO `security_functions`');
        /** END: security_functions table changes */
    }
}
