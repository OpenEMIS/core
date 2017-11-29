<?php

use Phinx\Migration\AbstractMigration;

class POCOR2829 extends AbstractMigration
{
    // commit
    public function up()
    {
        $this->execute('ALTER TABLE `institution_positions` CHANGE `is_homeroom` `is_homeroom` INT(1) NOT NULL DEFAULT 0');
    }

    // rollback
    public function down()
    {
        $this->execute('ALTER TABLE `institution_positions` CHANGE `is_homeroom` `is_homeroom` INT(1) NOT NULL DEFAULT 1');
    }
}
