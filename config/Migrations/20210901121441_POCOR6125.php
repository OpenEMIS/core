<?php
use Migrations\AbstractMigration;

class POCOR6125 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_6125_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6125_security_functions` SELECT * FROM `security_functions`');

        //enable Execute checkbox for export data
        $this->execute("UPDATE security_functions SET _execute = 'Shifts.excel' WHERE name = 'Shifts' AND controller = 'Institutions' AND category = 'Academic' ");
    }

     //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6125_security_functions` TO `security_functions`');
    }
}
