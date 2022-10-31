<?php
use Migrations\AbstractMigration;

class POCOR6152 extends AbstractMigration
{
    public function up()
    {
        /** START: security_functions table changes */
        $this->execute('DROP TABLE IF EXISTS `zz_6152_security_functions`');
        $this->execute('CREATE TABLE `zz_6152_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6152_security_functions` SELECT * FROM `security_functions`');

        $this->execute("UPDATE `security_functions` SET `controller` = 'Institutions', `_view` = 'InstitutionAssets.index|InstitutionAssets.view', `_edit` = 'InstitutionAssets.edit', `_add` = 'InstitutionAssets.add', `_delete` = 'InstitutionAssets.remove', `_execute` = 'InstitutionAssets.excel' WHERE `name` = 'Assets' AND `controller` = 'InstitutionAssets'");
        /** END: security_functions table changes */
    }

    //rollback
    public function down()
    {
        /** START: security_functions table changes */
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6152_security_functions` TO `security_functions`');
        /** END: security_functions table changes */
    }
}
