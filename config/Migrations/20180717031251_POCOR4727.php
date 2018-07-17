<?php

use Phinx\Migration\AbstractMigration;

class POCOR4727 extends AbstractMigration
{
    public function up()
    {         
        $this->execute('CREATE TABLE `employment_types` LIKE `z_4177_employment_types`');
        $this->execute('INSERT INTO `employment_types` SELECT * FROM `z_4177_employment_types`');
    }

    public function down()
    {
        $this->dropTable('employment_types');
    }
}
