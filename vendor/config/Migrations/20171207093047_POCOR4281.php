<?php
use Migrations\AbstractMigration;

class POCOR4281 extends AbstractMigration
{
    public function change()
    {
        $this->execute("UPDATE `security_roles` SET `code`= '' WHERE `code` IS NULL");
    }
}
