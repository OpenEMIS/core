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
//        $this->execute("INSERT INTO `locale_contents`
//            (`en`,`created`,`created_user_id`)
//            values ('Disability Type',1,NOW())");
//        $this->execute("INSERT INTO `locale_contents`
//            (`en`,`created`,`created_user_id`)
//            values ('Difficulty Type',1,NOW())");
//        $this->execute("INSERT INTO `locale_contents`
//            (`en`,`created`,`created_user_id`)
//            values ('Program Assigned',1,NOW())");
//        $this->execute("INSERT INTO `locale_contents`
//            (`en`,`created`,`created_user_id`)
//            values ('Guardian OpenEmisId',1,NOW())");
//        $this->execute("INSERT INTO `locale_contents`
//            (`en`,`created`,`created_user_id`)
//            values ('Guardian Name',1,NOW())");
        
        
        
        
        $localeContent = [
            [
                'en' => 'Disability Type',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Difficulty Type',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Program Assigned',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Guardian OpenEmis Id',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Guardian Name',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('locale_contents', $localeContent);
        
        
        
    }

    public function down()
    {
        // For locale_contents
        $this->execute("RENAME TABLE `z_5156_locale_contents` TO `locale_contents`");
    }
}
