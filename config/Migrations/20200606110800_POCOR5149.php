<?php

use Phinx\Migration\AbstractMigration;

class POCOR5149 extends AbstractMigration
{
    public function up()
    {

        // Backup locale_contents table
        $this->execute('CREATE TABLE `z_5149_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_5149_locale_contents` SELECT * FROM `locale_contents`');
        // End

        // For locale_contents table
        $this->execute("INSERT INTO `locale_contents`
            (`en`,`created`,`created_user_id`)
            values ('Default Identity type',1,NOW())");
    }

    public function down()
    {
        // For locale_contents
        $this->execute("RENAME TABLE `z_5149_locale_contents` TO `locale_contents`");
    }
}
