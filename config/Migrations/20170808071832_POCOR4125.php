<?php

use Phinx\Migration\AbstractMigration;

class POCOR4125 extends AbstractMigration
{
    // commit
    public function up()
    {
        // backup the table
        $this->execute('CREATE TABLE `z_4125_security_users` LIKE `security_users`');
        $this->execute('INSERT INTO `z_4125_security_users` SELECT * FROM `security_users`');

        // password is not null field.
        $this->execute("UPDATE `security_users` SET `password` = '' WHERE password IS NULL");
        $this->execute('ALTER TABLE `security_users` CHANGE `password` `password` CHAR(60) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ""');
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_users`');
        $this->execute('RENAME TABLE `z_4125_security_users` TO `security_users`');
    }
}
