<?php

use Phinx\Migration\AbstractMigration;

class POCOR5156 extends AbstractMigration
{
    public function up()
    {

        // Backup locale_contents table
        $this->execute('CREATE TABLE `z_5156_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_5156_locale_contents` SELECT * FROM `locale_contents`');
        // End

        // For locale_contents table
        $this->execute("INSERT INTO `locale_contents`
            (`en`,`created`,`created_user_id`)
            values ('Disability Type',1,NOW())");
        $this->execute("INSERT INTO `locale_contents`
            (`en`,`created`,`created_user_id`)
            values ('Difficulty Type',1,NOW())");
        $this->execute("INSERT INTO `locale_contents`
            (`en`,`created`,`created_user_id`)
            values ('Program Assigned',1,NOW())");
        $this->execute("INSERT INTO `locale_contents`
            (`en`,`created`,`created_user_id`)
            values ('Guardian OpenEmisId',1,NOW())");
        $this->execute("INSERT INTO `locale_contents`
            (`en`,`created`,`created_user_id`)
            values ('Guardian Name',1,NOW())");
    }

    public function down()
    {
        // For locale_contents
        $this->execute("RENAME TABLE `z_5156_locale_contents` TO `locale_contents`");
    }
}
