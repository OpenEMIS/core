<?php
use Migrations\AbstractMigration;

class POCOR6144 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_6144_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6144_security_functions` SELECT * FROM `security_functions`');


        //Change Contact people Controller
        $this->execute("UPDATE security_functions SET controller = 'Institutions' WHERE name = 'Infrastructure Utility Internet'");

        //chnage view,add,edit and delete to controller
        $this->execute("UPDATE security_functions SET _view = 'InfrastructureUtilityInternets.view|InfrastructureUtilityInternets.index|InfrastructureUtilityInternets.download' WHERE name = 'Infrastructure Utility Internet' ");
        $this->execute("UPDATE security_functions SET  _edit = 'InfrastructureUtilityInternets.edit' WHERE name = 'Infrastructure Utility Internet' ");
        $this->execute("UPDATE security_functions SET  _add = 'InfrastructureUtilityInternets.add' WHERE name = 'Infrastructure Utility Internet' ");
        $this->execute("UPDATE security_functions SET _delete = 'InfrastructureUtilityInternets.delete' WHERE name = 'Infrastructure Utility Internet' ");

        //enable Execute checkbox for export data
        $this->execute("UPDATE security_functions SET _execute = 'InfrastructureUtilityInternets.excel' WHERE name = 'Infrastructure Utility Internet' ");
    }

     //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6144_security_functions` TO `security_functions`');
    }
}
