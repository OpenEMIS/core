<?php

use Phinx\Migration\AbstractMigration;

class POCOR4497 extends AbstractMigration
{
    public function up()
    {
        $this->execute('ALTER TABLE `user_healths` MODIFY COLUMN `doctor_name` varchar(150) NULL');
        $this->execute('ALTER TABLE `user_healths` MODIFY COLUMN `doctor_contact` varchar(11) NULL');
    }

    public function down()
    {
        $this->execute('ALTER TABLE `user_healths` MODIFY COLUMN `doctor_name` varchar(150) NOT NULL');
        $this->execute('ALTER TABLE `user_healths` MODIFY COLUMN `doctor_contact` varchar(11) NOT NULL');
    }
}
