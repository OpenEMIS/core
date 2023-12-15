<?php
use Migrations\AbstractMigration;

class POCOR7980 extends AbstractMigration
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
        // Backup table
        $this->execute('CREATE TABLE `zz_7980_nationalities` LIKE `nationalities`');
        $this->execute('INSERT INTO `zz_7980_nationalities` SELECT * FROM `nationalities`');


        // Alter table
		$this->execute("ALTER TABLE `nationalities` ADD `is_refugee` INT NOT NULL DEFAULT '0' AFTER `external_validation`;");
    }

    // rollback
    public function down()
    {
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `nationalities`');
        $this->execute('RENAME TABLE `zz_7980_nationalities` TO `nationalities`');

    }
}
