<?php
use Migrations\AbstractMigration;

class POCOR6150 extends AbstractMigration
{
    public function up()
    {
        /** START: security_functions table changes */
        $this->execute('DROP TABLE IF EXISTS `zz_6150_security_functions`');
        $this->execute('CREATE TABLE `zz_6150_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6150_security_functions` SELECT * FROM `security_functions`');

        $this->execute("UPDATE `security_functions` SET `controller` = 'Institutions', `_view` = 'InfrastructureNeeds.index|InfrastructureNeeds.view|InfrastructureNeeds.download', `_edit` = 'InfrastructureNeeds.edit', `_add` = 'InfrastructureNeeds.add', `_delete` = 'InfrastructureNeeds.remove', `_execute` = 'InfrastructureNeeds.excel' WHERE `name` = 'Infrastructure Need' AND `controller` = 'InfrastructureNeeds' AND `module` = 'Institutions' AND `category` = 'Details'");
        /** END: security_functions table changes */
    }

    //rollback
    public function down()
    {
        /** START: security_functions table changes */
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6150_security_functions` TO `security_functions`');
        /** END: security_functions table changes */
    }
}
