<?php

use Phinx\Migration\AbstractMigration;

class POCOR4210 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `z_4210_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_4210_locale_contents` SELECT * FROM `locale_contents`');

        $localeContent = [
            [
                'en' => '404 Forbidden: Page Not Found',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
			[
                'en' => 'The page you are looking for might have been removed, renamed or is temporarily unavailable. If you have any enquiries, please contact the administrator',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'here',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('locale_contents', $localeContent);
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `z_4210_locale_contents` TO `locale_contents`');
    }
}
