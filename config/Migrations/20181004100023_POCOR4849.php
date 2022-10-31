<?php

use Phinx\Migration\AbstractMigration;

/**
* For Custom Reports to add a single columns called conditions for 
* assigning permissions to each report
**/
class POCOR4849 extends AbstractMigration
{
    public function up() 
    {
        $this->execute('ALTER TABLE `reports` ADD COLUMN `conditions` text COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `filter`');
    }

    public function down()
    {
        $this->execute('ALTER TABLE `reports` DROP COLUMN `conditions`');
    }
}
