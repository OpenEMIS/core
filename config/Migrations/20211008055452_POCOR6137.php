<?php
use Migrations\AbstractMigration;

class POCOR6137 extends AbstractMigration
{
    public function up()
    {
        /** START: security_functions table changes */
        $this->execute('DROP TABLE IF EXISTS `zz_6137_security_functions`');
        $this->execute('CREATE TABLE `zz_6137_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6137_security_functions` SELECT * FROM `security_functions`');

        $this->execute("UPDATE `security_functions` SET `_execute` = 'Courses.excel' WHERE `name` = 'Courses' AND `controller` = 'Staff' AND `module` = 'Institutions' AND `category` = 'Staff - Training'");
        $this->execute("UPDATE `security_functions` SET `_execute` = 'StaffTrainingNeeds.excel' WHERE `name` = 'Needs' AND `controller` = 'Institutions' AND `module` = 'Institutions' AND `category` = 'Staff - Training'");
        $this->execute("UPDATE `security_functions` SET `_execute` = 'StaffTrainingResults.excel' WHERE `name` = 'Results' AND `controller` = 'Institutions' AND `module` = 'Institutions' AND `category` = 'Staff - Training'");
        $this->execute("UPDATE `security_functions` SET `_execute` = 'StaffTrainingApplications.excel' WHERE `name` = 'Applications' AND `controller` = 'Institutions' AND `module` = 'Institutions' AND `category` = 'Staff - Training'");
        /** END: security_functions table changes */
    }

    //rollback
    public function down()
    {
        /** START: security_functions table changes */
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6137_security_functions` TO `security_functions`');
        /** END: security_functions table changes */
    }
}
