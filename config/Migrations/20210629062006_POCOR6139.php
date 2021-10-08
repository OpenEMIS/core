<?php

use Phinx\Migration\AbstractMigration;

class POCOR6139 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        //backup
        $this->execute('CREATE TABLE `z_6139_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_6139_security_functions` SELECT * FROM `security_functions`');


        //enable all Speacial needs tab (Referrals, Assessments, Services, Devices, Plans)
        $this->execute("UPDATE security_functions SET _execute = 'SpecialNeedsReferrals.excel' WHERE name = 'Referrals' AND controller = 'Staff' AND module = 'Institutions' AND category = 'Staff - Special Needs'");
        $this->execute("UPDATE security_functions SET _execute = 'SpecialNeedsAssessments.excel' WHERE name = 'Assessments' AND controller = 'Staff' AND module = 'Institutions' AND category = 'Staff - Special Needs'");
        $this->execute("UPDATE security_functions SET _execute = 'SpecialNeedsServices.excel' WHERE name = 'Services' AND controller = 'Staff' AND module = 'Institutions' AND category = 'Staff - Special Needs'");
        $this->execute("UPDATE security_functions SET _execute = 'SpecialNeedsDevices.excel' WHERE name = 'Devices' AND controller = 'Staff' AND module = 'Institutions' AND category = 'Staff - Special Needs'");
        $this->execute("UPDATE security_functions SET _execute = 'SpecialNeedsPlans.excel' WHERE name = 'Plans' AND controller = 'Staff' AND module = 'Institutions' AND category = 'Staff - Special Needs'");
    }

     //rollback
     public function down()
     {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_6139_security_functions` TO `security_functions`');
     }
}
