<?php

use Phinx\Migration\AbstractMigration;

class Pocor6186 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * @return void
     */
    public function change()
    {
        //backup
        $this->execute('CREATE TABLE `z_6186_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_6186_security_functions` SELECT * FROM `security_functions`');

        //enable execute checkbox in Map permission

        $this->execute("UPDATE security_functions SET _execute = 'InstitutionMaps.excel' WHERE name = 'Map' AND controller = 'Institutions' AND module = 'Institutions' AND category = 'General'");
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_6186_security_functions` TO `security_functions`');
    }
}
