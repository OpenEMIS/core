<?php
use Migrations\AbstractMigration;

class POCOR6171 extends AbstractMigration
{
    public function up()
    {
        /** START: security_functions table changes */
        $this->execute('DROP TABLE IF EXISTS `zz_6171_security_functions`');
        $this->execute('CREATE TABLE `zz_6171_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6171_security_functions` SELECT * FROM `security_functions`');

        $this->execute("UPDATE `security_functions` SET `controller` = 'Institutions', `_view` = 'Committees.index|Committees.view', `_edit` = 'Committees.edit', `_add` = 'Committees.add', `_delete` = 'Committees.remove', `_execute` = 'Committees.excel' WHERE `name` = 'Institution Committees' AND `module` = 'Institutions' AND `category` = 'Committees'");

        /** END: security_functions table changes */
    }

    //rollback
    public function down()
    {
        /** START: security_functions table changes */
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6171_security_functions` TO `security_functions`');
        /** END: security_functions table changes */
    }
}
