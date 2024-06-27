<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8385 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_8385_security_users` LIKE `security_users`');
        $this->execute('INSERT INTO `z_8385_security_users` SELECT * FROM `security_users`');

        //enable Execute checkbox for export and import data
        $this->execute("UPDATE `security_users` SET super_admin = 1 WHERE `username` = 'superrole';");
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_users`');
        $this->execute('RENAME TABLE `z_8385_security_users` TO `security_users`');
    }
}
