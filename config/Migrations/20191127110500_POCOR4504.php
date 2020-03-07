<?php

use Phinx\Migration\AbstractMigration;

class POCOR4504 extends AbstractMigration
{
    // commit
    public function up()
    {		
        $this->execute('ALTER TABLE `textbooks` MODIFY author VARCHAR(200)');
    }

    // rollback
    public function down()
    {
        $this->execute('ALTER TABLE `textbooks` MODIFY author VARCHAR(100)');
    }
}
