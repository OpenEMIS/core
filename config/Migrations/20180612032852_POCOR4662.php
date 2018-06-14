<?php

use Phinx\Migration\AbstractMigration;

class POCOR4662 extends AbstractMigration
{
    public function up()
    {
        $this->execute('UPDATE `security_functions` SET `category` = "Scholarships - Details" WHERE `category` = "Scholarships - Setup"');
    }

    public function down()
    { 
        $this->execute('UPDATE `security_functions` SET `category` = "Scholarships - Setup" WHERE `category` = "Scholarships - Details"');
    }
}
