<?php

use Phinx\Migration\AbstractMigration;

class POCOR5174 extends AbstractMigration
{
    public function up()
    {

        // Backup locale_contents table
        $this->execute('CREATE TABLE `z_5174_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_5174_locale_contents` SELECT * FROM `locale_contents`');
        // End
        
        
        $localeContent = [
            [
                'en' => 'Student OpenEMIS ID',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Risk Index',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Extra Activities',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Mother OpenEMIS ID',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Mother Name',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Mother Contact',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Father OpenEMIS ID',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Father Name',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Father Contact',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Guardian OpenEMIS ID',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Guardian Name',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Guardian Gender',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Guardian Date of Birth',
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
        $this->execute("RENAME TABLE `z_5174_locale_contents` TO `locale_contents`");
    }
}
