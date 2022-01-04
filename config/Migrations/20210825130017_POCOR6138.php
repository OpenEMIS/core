<?php

use Phinx\Migration\AbstractMigration;

class POCOR6138 extends AbstractMigration
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
    public function up()
    {
        //backup
        $this->execute('CREATE TABLE `zz_6138_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6138_security_functions` SELECT * FROM `security_functions`');


        //Change Contact people Controller
        $this->execute("UPDATE security_functions SET controller = 'Staff' WHERE name = 'Staff Body Mass'");
        $this->execute("UPDATE security_functions SET controller = 'Staff' WHERE name = 'Staff Insurance'");

        //chnage view,add,edit and delete to controller of Staff Body Mass
        $this->execute("UPDATE security_functions SET _view = 'StaffBodyMasses.view|StaffBodyMasses.index' WHERE name = 'Staff Body Mass' ");
        $this->execute("UPDATE security_functions SET  _edit = 'StaffBodyMasses.edit' WHERE name = 'Staff Body Mass' ");
        $this->execute("UPDATE security_functions SET  _add = 'StaffBodyMasses.add' WHERE name = 'Staff Body Mass' ");
        $this->execute("UPDATE security_functions SET _delete = 'StaffBodyMasses.delete' WHERE name = 'Staff Body Mass' ");

        //change view,add,edit and delete to controller of Staff Insurance
        $this->execute("UPDATE security_functions SET _view = 'StaffInsurances.view|StaffInsurances.index' WHERE name = 'Staff Insurance' ");
        $this->execute("UPDATE security_functions SET  _edit = 'StaffInsurances.edit' WHERE name = 'Staff Insurance' ");
        $this->execute("UPDATE security_functions SET  _add = 'StaffInsurances.add' WHERE name = 'Staff Insurance' ");
        $this->execute("UPDATE security_functions SET _delete = 'StaffInsurances.delete' WHERE name = 'Staff Insurance' ");

        //enable Execute checkbox for export data
        $this->execute("UPDATE security_functions SET _execute = 'Healths.excel' WHERE name = 'Overview' AND category = 'Staff - Health' ");
        $this->execute("UPDATE security_functions SET _execute = 'HealthAllergies.excel' WHERE name = 'Allergies' AND category = 'Staff - Health' ");
        $this->execute("UPDATE security_functions SET _execute = 'HealthConsultations.excel' WHERE name = 'Consultations' AND category = 'Staff - Health' ");
        $this->execute("UPDATE security_functions SET _execute = 'HealthFamilies.excel' WHERE name = 'Families' AND category = 'Staff - Health' ");
        $this->execute("UPDATE security_functions SET _execute = 'HealthHistories.excel' WHERE name = 'Histories' AND category = 'Staff - Health' ");
        $this->execute("UPDATE security_functions SET _execute = 'HealthImmunizations.excel' WHERE name = 'Vaccinations' AND category = 'Staff - Health' ");
        $this->execute("UPDATE security_functions SET _execute = 'HealthMedications.excel' WHERE name = 'Medications' AND category = 'Staff - Health' ");
        $this->execute("UPDATE security_functions SET _execute = 'HealthTests.excel' WHERE name = 'Tests' AND category = 'Staff - Health' ");
        $this->execute("UPDATE security_functions SET _execute = 'StaffBodyMasses.excel' WHERE name = 'Staff Body Mass' AND category = 'Staff - Health' ");
        $this->execute("UPDATE security_functions SET _execute = 'StaffInsurances.excel' WHERE name = 'Staff Insurance' AND category = 'Staff - Health' ");
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6138_security_functions` TO `security_functions`');
    }
}
