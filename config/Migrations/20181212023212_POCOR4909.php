<?php

use Phinx\Migration\AbstractMigration;

class POCOR4909 extends AbstractMigration
{
    public function up()
    {
       // $this->execute('CREATE TABLE `z_4909_security_functions` LIKE `security_functions`');
      //  $this->execute('INSERT INTO `z_4909_security_functions` SELECT * FROM `security_functions`');
       // $this->execute("UPDATE `security_functions` SET `_execute` = 'StudentAttendances.excel' WHERE `id` = 1014");
    }

    public function down()
    {
      //  $this->dropTable('security_functions');
       // $this->table('z_4909_security_functions')->rename('security_functions');
    }   
}
