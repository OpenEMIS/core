<?php
use Migrations\AbstractMigration;

class POCOR5788 extends AbstractMigration
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
		$this->execute('UPDATE security_functions SET _add = "StudentAttendances.add" WHERE `name` = "Attendance" AND `controller` = "Institutions" AND `module` = "Institutions" AND `category` = "Students"');
    }

    //  rollback
    public function down()
    {
    }
}
