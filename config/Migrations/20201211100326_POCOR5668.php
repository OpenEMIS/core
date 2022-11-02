<?php
use Migrations\AbstractMigration;

class POCOR5668 extends AbstractMigration
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

        $this->execute('CREATE TABLE `z_5668_nationalities` LIKE `nationalities`');
        $this->execute('INSERT INTO `z_5668_nationalities` SELECT * FROM `nationalities`');

        $this->execute("ALTER TABLE `nationalities`  ADD `external_validation` INT(1) NOT NULL DEFAULT '0'  AFTER `national_code`"); 
    }
}
