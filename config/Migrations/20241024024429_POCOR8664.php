<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8664 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_8664_user_identities` LIKE `user_identities`');
        $this->execute('INSERT INTO `z_8664_user_identities` SELECT * FROM `user_identities`');

        $this->execute("ALTER TABLE `user_identities` ADD `preferred` INT NOT NULL DEFAULT '1' AFTER `comments`");
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `user_identities`');
        $this->execute('RENAME TABLE `z_8664_user_identities` TO `user_identities`');
    }
}
