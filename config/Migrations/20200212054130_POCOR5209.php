<?php

use Phinx\Migration\AbstractMigration;

class POCOR5209 extends AbstractMigration
{
    // commit
    public function up()
    {
        // backup the table
        $this->execute('CREATE TABLE `z_5209_institution_lands` LIKE `institution_lands`');
        $this->execute('INSERT INTO `z_5209_institution_lands` SELECT * FROM `institution_lands`');

        // Alter Table.
		$this->execute('UPDATE institution_lands set year_acquired = start_year where year_acquired IS NULL');
        $this->execute('ALTER TABLE `institution_lands` CHANGE `year_acquired` `year_acquired` INT(4) NOT NULL;');
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_lands`');
        $this->execute('RENAME TABLE `z_5209_institution_lands` TO `institution_lands`');
    }
}
