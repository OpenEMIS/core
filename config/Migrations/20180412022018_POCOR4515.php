<?php

use Phinx\Migration\AbstractMigration;

class POCOR4515 extends AbstractMigration
{
    public function up()
    {
        $this->execute("UPDATE `locale_contents` SET `en` = 'Loading' WHERE `en` = 'Loading...'");
    }

    public function down()
    {
        $this->execute("UPDATE `locale_contents` SET `en` = 'Loading...' WHERE `en` = 'Loading'");
    }
}
