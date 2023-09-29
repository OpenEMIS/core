<?php

use Phinx\Migration\AbstractMigration;

class POCOR3536 extends AbstractMigration
{
    public function up()
    {
        $this->execute('ALTER TABLE `report_progress` MODIFY COLUMN `name` varchar(200) COLLATE utf8_general_ci NOT NULL');
    }

    public function down()
    {
        $this->execute('ALTER TABLE `report_progress` MODIFY COLUMN `name` varchar(100) COLLATE utf8_general_ci NOT NULL');
    }
}
