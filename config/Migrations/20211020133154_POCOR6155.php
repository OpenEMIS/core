<?php
use Migrations\AbstractMigration;

class POCOR6155 extends AbstractMigration
{
    public function up()
    {
        /** START: security_functions table changes */
        $this->execute('DROP TABLE IF EXISTS `zz_6155_security_functions`');
        $this->execute('CREATE TABLE `zz_6155_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6155_security_functions` SELECT * FROM `security_functions`');

        $this->execute("UPDATE `security_functions` SET `_execute` = 'StaffBehaviours.excel' WHERE `name` = 'Behaviour' AND `controller` = 'Institutions' AND `module` = 'Institutions' AND `category` = 'Staff'");
        /** END: security_functions table changes */
    }

    //rollback
    public function down()
    {
        /** START: security_functions table changes */
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6155_security_functions` TO `security_functions`');
        /** END: security_functions table changes */
    }
}
