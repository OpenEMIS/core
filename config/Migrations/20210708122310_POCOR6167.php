<?php
use Migrations\AbstractMigration;

class POCOR6167 extends AbstractMigration
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
       
        $this->execute('CREATE TABLE `z_6167_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_6167_security_functions` SELECT * FROM `security_functions`');

        //enable execute checkbox in Map permission

        $this->execute("UPDATE security_functions SET _execute = 'excel' WHERE name = 'Providers' AND controller = 'InstitutionTransportProviders' AND module = 'Institutions' AND category = 'Transport'");
    }

 
    public function down()

    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_6167_security_functions` TO `security_functions`');
    }
}
