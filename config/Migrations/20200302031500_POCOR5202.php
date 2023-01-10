<?php

use Phinx\Migration\AbstractMigration;

class POCOR5202 extends AbstractMigration
{
    public function up()
    {
        // backup the table
        $this->execute('CREATE TABLE `z_5202_institution_counsellings` LIKE `institution_counsellings`');
        $this->execute('INSERT INTO `z_5202_institution_counsellings` SELECT * FROM `institution_counsellings`');
        $this->execute('ALTER TABLE `institution_counsellings` ADD COLUMN requester_id INT(11) AFTER guidance_type_id');  
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_counsellings`');
        $this->execute('RENAME TABLE `z_5202_institution_counsellings` TO `institution_counsellings`'); 
    }
}
