<?php

use Phinx\Migration\AbstractMigration;

class POCOR5219 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `z_5219_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_5219_locale_contents` SELECT * FROM `locale_contents`');

        $localeContent = [
            [
                'en' => 'Student First Name',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
			[
                'en' => 'Student Last Name',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Guardians First Name',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Guardians Last Name',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Guardians Address',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Guardians Email',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Guardians Primary Phone Contact',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('locale_contents', $localeContent);
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `z_5219_locale_contents` TO `locale_contents`');
    }
}
