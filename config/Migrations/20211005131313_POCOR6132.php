<?php
use Migrations\AbstractMigration;

class POCOR6132 extends AbstractMigration
{
    public function up()
    {
        /** START: security_functions table changes */
        $this->execute('DROP TABLE IF EXISTS `zz_6132_security_functions`');
        $this->execute('CREATE TABLE `zz_6132_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6132_security_functions` SELECT * FROM `security_functions`');

        $this->execute("UPDATE `security_functions` SET `_execute` = 'SpecialNeedsReferrals.excel' WHERE `name` = 'Referrals' AND `controller` = 'Students' AND `module` = 'Institutions' AND `category` = 'Students - Special Needs'");
        $this->execute("UPDATE `security_functions` SET `_execute` = 'SpecialNeedsAssessments.excel' WHERE `name` = 'Assessments' AND `controller` = 'Students' AND `module` = 'Institutions' AND `category` = 'Students - Special Needs'");
        $this->execute("UPDATE `security_functions` SET `_execute` = 'SpecialNeedsServices.excel' WHERE `name` = 'Services' AND `controller` = 'Students' AND `module` = 'Institutions' AND `category` = 'Students - Special Needs'");
        $this->execute("UPDATE `security_functions` SET `_execute` = 'SpecialNeedsPlans.excel' WHERE `name` = 'Plans' AND `controller` = 'Students' AND `module` = 'Institutions' AND `category` = 'Students - Special Needs'");
        $this->execute("UPDATE `security_functions` SET `_execute` = 'SpecialNeedsDevices.excel' WHERE `name` = 'Devices' AND `controller` = 'Students' AND `module` = 'Institutions' AND `category` = 'Students - Special Needs'");
        /** END: security_functions table changes */
    }

    //rollback
    public function down()
    {
        /** START: security_functions table changes */
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6132_security_functions` TO `security_functions`');
        /** END: security_functions table changes */
    }
}
