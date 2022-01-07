<?php
use Migrations\AbstractMigration;

class POCOR6170 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_6170_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_6170_security_functions` SELECT * FROM `security_functions`');

        //enable execute checkbox in Map permission

        $this->execute("UPDATE security_functions SET _execute = 'Cases.excel' WHERE name = 'Cases' AND controller = 'Institutions' AND module = 'Institutions' AND category = 'Cases'");
    }

    public function down()

    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_6170_security_functions` TO `security_functions`');
    }
}
