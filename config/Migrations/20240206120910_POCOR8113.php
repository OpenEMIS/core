<?php
use Migrations\AbstractMigration;

class POCOR8113 extends AbstractMigration
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
        // backup locale_contents table
        $this->execute('CREATE TABLE `z_8113_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_8113_locale_contents` SELECT * FROM `locale_contents`');

        #Insert data into locale_contents table
        $this->execute("UPDATE `locale_contents` SET `en` = 'Promotion / Repeating / Graduation' WHERE `locale_contents`.`en` = 'Promotion / Graduation'");
    }

    public function down() {
       $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `z_8113_locale_contents` TO `locale_contents`');
    }
}
