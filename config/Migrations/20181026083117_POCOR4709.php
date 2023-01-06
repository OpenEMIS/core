<?php

use Phinx\Migration\AbstractMigration;

class POCOR4709 extends AbstractMigration
{
    public function up()
    {
        // backup the table
        $this->execute('CREATE TABLE `z_4709_security_users` LIKE `security_users`');
        $this->execute('INSERT INTO `z_4709_security_users` SELECT * FROM `security_users`');

        $this->execute('DELETE FROM security_functions WHERE id = 2033');

        //update rows below order 164 to shitf forward
        $this->execute('UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` > 164' );

    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_users`');
        $this->execute('RENAME TABLE `z_4709_security_users` TO `security_users`');
    }
}
