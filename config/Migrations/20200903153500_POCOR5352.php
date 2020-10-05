<?php

use Phinx\Migration\AbstractMigration;

class POCOR5352 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `z_5352_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_5352_locale_contents` SELECT * FROM `locale_contents`');
        // locale_contents
        $localeContent = [
            [
                'en' => 'Period',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Total Marked',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Total Unmarked',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],            
            [
                'en' => 'Total No days to be marked',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
        ];
        $this->insert('locale_contents', $localeContent);
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `z_5352_locale_contents` TO `locale_contents`');
    }
}