<?php
use Cake\ORM\TableRegistry;
use Migrations\AbstractMigration;

class POCOR4558 extends AbstractMigration
{
    public function up()
    {
        $this->execute('UPDATE `user_body_masses` SET `height` = `height` * 100');
    }

    public function down()
    {
        $this->execute('UPDATE `user_body_masses` SET `height` = `height` / 100');
    }
}
