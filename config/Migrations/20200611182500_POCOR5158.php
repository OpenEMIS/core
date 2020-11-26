<?php

use Phinx\Migration\AbstractMigration;

class POCOR5158 extends AbstractMigration
{
    public function up()
    { 

        // Backup locale_contents table
        $this->execute('CREATE TABLE `z_5158_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_5158_locale_contents` SELECT * FROM `locale_contents`');
        // End
        
        
        $localeContent = [
            [
                'en' => 'Area Administrative',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Assignee',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Homeroom Teacher',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'All Aread',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Position Summary Report',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        
        $this->insert('locale_contents', $localeContent);

    }

    public function down()
    {
        // For locale_contents
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute("RENAME TABLE `z_5158_locale_contents` TO `locale_contents`");
    }
}
