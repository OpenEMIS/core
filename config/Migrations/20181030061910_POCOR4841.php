<?php

use Phinx\Migration\AbstractMigration;

class POCOR4841 extends AbstractMigration
{
    public function up()
    {
        // backup the table
        $this->execute('CREATE TABLE `z_4841_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_4841_security_functions` SELECT * FROM `security_functions`');
        // end backup

        //export for student outcomes
        $this->execute("UPDATE security_functions
            SET `_execute` = 'StudentOutcomes.excel'
            WHERE `id` = 1081");
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_4841_security_functions` TO `security_functions`');
    }

}
