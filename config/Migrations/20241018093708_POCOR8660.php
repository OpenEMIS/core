<?php

declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8660 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        //backup
        $this->execute('CREATE TABLE `z_8660_security_users` LIKE `security_users`');
        $this->execute('INSERT INTO `z_8660_security_users` SELECT * FROM `security_users`');

        // alter table to add new column
        $this->execute('ALTER TABLE `security_users` ADD COLUMN mobile_number varchar(200) default NULL AFTER email');
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_users`');
        $this->execute('RENAME TABLE `z_8660_security_users` TO `security_users`');
    }
}
