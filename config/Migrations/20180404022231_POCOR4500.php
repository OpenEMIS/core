<?php

use Phinx\Migration\AbstractMigration;

class POCOR4500 extends AbstractMigration
{
    public function up()
    {
        $this->execute('ALTER TABLE `student_extracurriculars` ADD COLUMN `position` varchar(50) COLLATE utf8_general_ci NULL AFTER `location`');
    }

    public function down()
    {
        $this->execute('ALTER TABLE `student_extracurriculars` DROP COLUMN `position`');
    }

}
