<?php
use Migrations\AbstractMigration;

class POCOR6141 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_6141_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6141_security_functions` SELECT * FROM `security_functions`');

        //chnage view,add,edit and delete to controller
        $this->execute("UPDATE security_functions SET _view = 'StaffDuties.index|StaffDuties.view' WHERE name = 'Duties' AND module = 'Institutions' AND controller = 'Institutions' ");
        $this->execute("UPDATE security_functions SET  _edit = 'StaffDuties.edit' WHERE name = 'Duties' AND module = 'Institutions' AND controller = 'Institutions' ");
        $this->execute("UPDATE security_functions SET  _add = 'StaffDuties.add' WHERE name = 'Duties' AND module = 'Institutions' AND controller = 'Institutions' ");
        $this->execute("UPDATE security_functions SET _delete = 'StaffDuties.remove' WHERE name = 'Duties' AND module = 'Institutions' AND controller = 'Institutions' ");

        //enable Execute checkbox for export data
        $this->execute("UPDATE security_functions SET _execute = 'StaffDuties.excel' WHERE name = 'Duties' AND module = 'Institutions' AND controller = 'Institutions' ");

        // update category with Appointment
        $this->execute("UPDATE security_functions SET category = 'Appointment' WHERE name = 'Duties' AND module = 'Institutions' AND controller = 'Institutions' ");
    }

     //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6141_security_functions` TO `security_functions`');
    }
}
