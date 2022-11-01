<?php

use Phinx\Migration\AbstractMigration;

class POCOR4427 extends AbstractMigration
{
       public function up()
    {
        $this->execute("UPDATE `locale_contents` SET `en` = 'OpenEMIS ID, Identity Number or Name' WHERE `en` = 'OpenEMIS ID or Name'");
    }

    public function down()
    {
        $this->execute("UPDATE `locale_contents` SET `en` = 'OpenEMIS ID or Name' WHERE `en` = 'OpenEMIS ID, Identity Number or Name'");
    }
}
