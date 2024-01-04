<?php

use Phinx\Migration\AbstractMigration;

class POCOR8054 extends AbstractMigration
{
    public function up()
    {
        //backup
        $this->execute('CREATE TABLE `z_8054_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_8054_security_functions` SELECT * FROM `security_functions`');

        //enable Execute checkbox for export and import data
        $this->execute("UPDATE `security_functions` SET `category` = 'Data Management' WHERE `category` = 'Archive' ");
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_8054_security_functions` TO `security_functions`');
    }
}
