<?php
use Migrations\AbstractMigration;

class POCOR6164 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_6164_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_6164_security_functions` SELECT * FROM `security_functions`');

        //enable execute checkbox in Map permission

        $this->execute("UPDATE security_functions SET _execute = 'Fees.excel' WHERE name = 'Fees' AND controller = 'Institutions' AND module = 'Institutions' AND category = 'Finance'");
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_6164_security_functions` TO `security_functions`');
    }
}
