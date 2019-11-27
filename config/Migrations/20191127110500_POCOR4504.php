<?php

use Phinx\Migration\AbstractMigration;

class POCOR4504 extends AbstractMigration
{
    // commit
    public function up()
    {
        // backup the table
        $this->execute('CREATE TABLE `z_4504_textbooks` LIKE `textbooks`');
        $this->execute('INSERT INTO `z_4504_textbooks` SELECT * FROM `textbooks`');
		
        $this->execute('ALTER TABLE `textbooks` MODIFY author VARCHAR(200)');
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `textbooks`');
        $this->execute('RENAME TABLE `z_4504_textbooks` TO `textbooks`');
    }
}
