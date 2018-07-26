<?php
use Migrations\AbstractMigration;

class POCOR4550 extends AbstractMigration
{
    public function up()
    {
        $sql = 'UPDATE `user_body_masses` SET `height` = `height` * 100';
        $this->execute($sql);
    }

    public function down()
    {
        $sql = 'UPDATE `user_body_masses` SET `height` = `height` / 100';
        $this->execute($sql);
    }
}
