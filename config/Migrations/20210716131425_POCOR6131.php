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
        //backup
        $this->execute('CREATE TABLE `z_6131_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_6131_security_functions` SELECT * FROM `security_functions`');


        //Change Contact people Controller
        $this->execute("UPDATE security_functions SET controller = 'Students' WHERE name = 'Student Body Mass'");
        $this->execute("UPDATE security_functions SET controller = 'Students' WHERE name = 'Student Insurance'");

        //chnage view,add,edit and delete to controller of Student Body Mass
        $this->execute("UPDATE security_functions SET _view = 'StudentBodyMasses.view|StudentBodyMasses.index' WHERE name = 'Student Body Mass' ");
        $this->execute("UPDATE security_functions SET  _edit = 'StudentBodyMasses.edit' WHERE name = 'Student Body Mass' ");
        $this->execute("UPDATE security_functions SET  _add = 'StudentBodyMasses.add' WHERE name = 'Student Body Mass' ");
        $this->execute("UPDATE security_functions SET _delete = 'StudentBodyMasses.delete' WHERE name = 'Student Body Mass' ");

        //change view,add,edit and delete to controller of Student Insurance
        $this->execute("UPDATE security_functions SET _view = 'StudentInsurances.view|StudentInsurances.index' WHERE name = 'Student Insurance' ");
        $this->execute("UPDATE security_functions SET  _edit = 'StudentInsurances.edit' WHERE name = 'Student Insurance' ");
        $this->execute("UPDATE security_functions SET  _add = 'StudentInsurances.add' WHERE name = 'Student Insurance' ");
        $this->execute("UPDATE security_functions SET _delete = 'StudentInsurances.delete' WHERE name = 'Student Insurance' ");

        //enable Execute checkbox for export data
        $this->execute("UPDATE security_functions SET _execute = 'Healths.excel' WHERE name = 'Overview' AND category = 'Students - Health' ");
        $this->execute("UPDATE security_functions SET _execute = 'HealthAllergies.excel' WHERE name = 'Allergies' AND category = 'Students - Health' ");
        $this->execute("UPDATE security_functions SET _execute = 'HealthConsultations.excel' WHERE name = 'Consultations' AND category = 'Students - Health' ");
        $this->execute("UPDATE security_functions SET _execute = 'HealthFamilies.excel' WHERE name = 'Families' AND category = 'Students - Health' ");
        $this->execute("UPDATE security_functions SET _execute = 'HealthHistories.excel' WHERE name = 'Histories' AND category = 'Students - Health' ");
        $this->execute("UPDATE security_functions SET _execute = 'HealthImmunizations.excel' WHERE name = 'Vaccinations' AND category = 'Students - Health' ");
        $this->execute("UPDATE security_functions SET _execute = 'HealthMedications.excel' WHERE name = 'Medications' AND category = 'Students - Health' ");
        $this->execute("UPDATE security_functions SET _execute = 'HealthTests.excel' WHERE name = 'Tests' AND category = 'Students - Health' ");
        $this->execute("UPDATE security_functions SET _execute = 'StudentBodyMasses.excel' WHERE name = 'Student Body Mass' AND category = 'Students - Health' ");
        $this->execute("UPDATE security_functions SET _execute = 'StudentInsurances.excel' WHERE name = 'Student Insurance' AND category = 'Students - Health' ");
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_6131_security_functions` TO `security_functions`');
    }
}
