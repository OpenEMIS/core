<?php
use Migrations\AbstractMigration;

class POCOR5845 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_5845_api_securities` LIKE `api_securities`');
        $this->execute("INSERT INTO `api_securities` (`id`, `name`, `model`, `index`, `view`, `add`, `edit`, `delete`, `execute`) VALUES
                (1032, 'Institution Absence Types', 'Institution.AbsenceTypes', 1, 1, 1, 1, 0, 0),
                (1033, 'Institution Student Absence Reasons', 'Institution.StudentAbsenceReasons', 1, 1, 1, 1, 0, 0),
                (1034, 'Institution Staff Attendances', 'Staff.InstitutionStaffAttendances', 1, 1, 1, 1, 0, 0)");

    }


    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `api_securities`');
        $this->execute('RENAME TABLE `z_5845_api_securities` TO `api_securities`');
        $this->execute('DELETE FROM api_securities WHERE id = 1032');
        $this->execute('DELETE FROM api_securities WHERE id = 1033');
        $this->execute('DELETE FROM api_securities WHERE id = 1034');
    }
}
