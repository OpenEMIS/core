<?php

use Phinx\Migration\AbstractMigration;

class POCOR4914 extends AbstractMigration
{
    public function up()
    {
        $today = date('Y-m-d H:i:s');

        // locale_contents
        $this->execute('CREATE TABLE `z_4914_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_4914_locale_contents` SELECT * FROM `locale_contents`');

        $localeContentData = [
            [
                'en' => 'Current Staff',
                'created_user_id' => 1,
                'created' => $today
            ]
        ];

        $this->insert('locale_contents', $localeContentData);
    }   

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `z_4914_locale_contents` TO `locale_contents`');
    }
}
