<?php
use Migrations\AbstractMigration;

class POCOR6143 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        //backup
        $this->execute('CREATE TABLE `zz_6143_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6143_security_functions` SELECT * FROM `security_functions`');

        //chnage view,add,edit and delete to controller
        $this->execute("UPDATE security_functions SET _view = 'InfrastructureUtilityElectricities.view|InfrastructureUtilityElectricities.index|InfrastructureUtilityElectricities.download' WHERE name = 'Infrastructure Utility Electricity' ");
        $this->execute("UPDATE security_functions SET  _edit = 'InfrastructureUtilityElectricities.edit' WHERE name = 'Infrastructure Utility Electricity' ");
        $this->execute("UPDATE security_functions SET  _add = 'InfrastructureUtilityElectricities.add' WHERE name = 'Infrastructure Utility Electricity' ");
        $this->execute("UPDATE security_functions SET _delete = 'InfrastructureUtilityElectricities.delete' WHERE name = 'Infrastructure Utility Electricity' ");
        $this->execute("UPDATE security_functions SET controller = 'Institutions' WHERE name = 'Infrastructure Utility Electricity' ");
        
        //enable Execute checkbox for export data
        $this->execute("UPDATE security_functions SET _execute = 'InfrastructureUtilityElectricities.excel' WHERE name = 'Infrastructure Utility Electricity' ");
    }

    //rollback
    public function down()
    {
       $this->execute('DROP TABLE IF EXISTS `security_functions`');
       $this->execute('RENAME TABLE `zz_6143_security_functions` TO `security_functions`');
    }
}
