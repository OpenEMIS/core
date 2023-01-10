<?php
use Migrations\AbstractMigration;

class POCOR6149 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function change()
    {
        //backup
        $this->execute('CREATE TABLE `z_6149_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_6149_security_functions` SELECT * FROM `security_functions`');

        //Change Contact people Controller
        $this->execute("UPDATE security_functions SET controller = 'Institutions' WHERE name = 'Infrastructure WASH Sewage'");

        //chnage view,add,edit and delete to controller
        $this->execute("UPDATE security_functions SET _view = 'InfrastructureWashSewages.view|InfrastructureWashSewages.index|InfrastructureWashSewages.download' WHERE name = 'Infrastructure WASH Sewage' ");
        $this->execute("UPDATE security_functions SET  _edit = 'InfrastructureWashSewages.edit' WHERE name = 'Infrastructure WASH Sewage' ");
        $this->execute("UPDATE security_functions SET  _add = 'InfrastructureWashSewages.add' WHERE name = 'Infrastructure WASH Sewage' ");
        $this->execute("UPDATE security_functions SET _delete = 'InfrastructureWashSewages.delete' WHERE name = 'Infrastructure WASH Sewage' ");


        //enable Execute checkbox for export data
        $this->execute("UPDATE security_functions SET _execute = 'InfrastructureWashSewages.excel' WHERE name = 'Infrastructure WASH Sewage' ");
    }

     //rollback
     public function down()
     {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_6149_security_functions` TO `security_functions`');
     }
}
