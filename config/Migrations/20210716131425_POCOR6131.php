<?php

use Phinx\Migration\AbstractMigration;

class POCOR6131 extends AbstractMigration
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
        $this->execute('DROP TABLE IF EXISTS `zz_6131_security_functions`');
        $this->execute('CREATE TABLE `zz_6131_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6131_security_functions` SELECT * FROM `security_functions`');

        $this->execute("UPDATE `security_functions` SET `controller` = 'Students', `_view` = 'StudentBodyMasses.index|StudentBodyMasses.view', `_edit` = 'StudentBodyMasses.edit', `_add` = 'StudentBodyMasses.add', `_delete` = 'StudentBodyMasses.delete' ,`_execute` = 'StudentBodyMasses.excel' WHERE `name` = 'Student Body Mass' AND `controller` = 'StudentBodyMasses' AND `module` = 'Institutions' AND `category` = 'Students - Health'");

        $this->execute("UPDATE `security_functions` SET `controller` = 'Students', `_view` = 'StudentInsurances.index|StudentInsurances.view', `_edit` = 'StudentInsurances.edit', `_add` = 'StudentInsurances.add', `_delete` = 'StudentInsurances.delete', `_execute` = 'StudentInsurances.excel' WHERE `name` = 'Student Insurance' AND `controller` = 'StudentInsurances' AND `module` = 'Institutions' AND `category` = 'Students - Health'");

        //enable Execute checkbox for export data
        $this->execute("UPDATE `security_functions` SET `_execute` = 'Healths.excel' WHERE `name` = 'Overview' AND `controller` = 'Students' AND `category` = 'Students - Health' ");
        $this->execute("UPDATE `security_functions` SET `_execute` = 'HealthAllergies.excel' WHERE `name` = 'Allergies' AND `controller` = 'Students' AND  `category` = 'Students - Health' ");
        $this->execute("UPDATE `security_functions` SET `_execute` = 'HealthConsultations.excel' WHERE `name` = 'Consultations' AND `controller` = 'Students' AND `category` = 'Students - Health' ");
        $this->execute("UPDATE `security_functions` SET `_execute` = 'HealthFamilies.excel' WHERE `name` = 'Families' AND `controller` = 'Students' AND `category` = 'Students - Health' ");
        $this->execute("UPDATE `security_functions` SET `_execute` = 'HealthHistories.excel' WHERE `name` = 'Histories' AND `controller` = 'Students' AND `category` = 'Students - Health' ");
        $this->execute("UPDATE `security_functions` SET `_execute` = 'HealthImmunizations.excel' WHERE `name` = 'Vaccinations' AND `controller` = 'Students' AND `category` = 'Students - Health' ");
        $this->execute("UPDATE `security_functions` SET `_execute` = 'HealthMedications.excel' WHERE `name` = 'Medications' AND `controller` = 'Students' AND `category` = 'Students - Health' ");
        $this->execute("UPDATE `security_functions` SET `_execute` = 'HealthTests.excel' WHERE `name` = 'Tests' AND `controller` = 'Students' AND `category` = 'Students - Health' ");
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_6131_security_functions` TO `security_functions`');
    }
}
