<?php
use Migrations\AbstractMigration;

class POCOR5327 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        $this->execute('CREATE TABLE `zz_5327_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `zz_5327_locale_contents` SELECT * FROM `locale_contents`');

        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'Nursery'");

    }

    public function down() {
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `zz_5327_locale_contents` TO `locale_contents`');
    }
}
