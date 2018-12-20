<?php

use Phinx\Migration\AbstractMigration;

class POCOR4857a extends AbstractMigration
{
    public function up()
    {
        //Security Table backup
        $this->execute('CREATE TABLE `z_4857a_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_4857a_security_functions` SELECT * FROM `security_functions`');

        $this->execute("UPDATE `security_functions` SET `controller` = 'MoodleApiLog' WHERE `security_functions`.`id` = 9000;");
    }

    public function down()
    {
        // Security Table recover backup
        $this->execute('DROP TABLE security_functions');
        $this->execute('RENAME TABLE `z_4784a_security_functions` TO `security_functions`');
    }
}
