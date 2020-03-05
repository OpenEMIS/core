<?php

use Phinx\Migration\AbstractMigration;

class POCOR4210 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `z_4210_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_4210_locale_contents` SELECT * FROM `locale_contents`');

        $this->execute('CREATE TABLE `z_4210_locale_content_translations` LIKE `locale_content_translations`');
        $this->execute('INSERT INTO `z_4210_locale_content_translations` SELECT * FROM `locale_content_translations`');

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

        $this->execute('
            INSERT INTO `locale_content_translations` (`translation`,`locale_content_id`, `locale_id`,`created_user_id`,`created`)
            VALUES("404 ممنوع: الصفحة غير موجودة
                ",(SELECT id FROM locale_contents WHERE en="404 Forbidden: Page Not Found"),
            1,
            1,
            "'.date("Y-m-d H:i:s").'"
        )');  
        $this->execute('
            INSERT INTO `locale_content_translations` (`translation`,`locale_content_id`, `locale_id`,`created_user_id`,`created`)
            VALUES("ربما تمت إزالة الصفحة التي تبحث عنها أو إعادة تسميتها أو أنها غير متاحة مؤقتًا. إذا كان لديك أي استفسارات ، يرجى الاتصال بالمسؤول ه
                ",(SELECT id FROM locale_contents WHERE en="The page you are looking for might have been removed, renamed or is temporarily unavailable. If you have any enquiries, please contact the administrator"),
            1,
            1,
            "'.date("Y-m-d H:i:s").'"
            )');
        $this->execute('
            INSERT INTO `locale_content_translations` (`translation`,`locale_content_id`, `locale_id`,`created_user_id`,`created`)
            VALUES("هنا
                ",(SELECT id FROM locale_contents WHERE en="here"),
            1,
            1,
            "'.date("Y-m-d H:i:s").'"
            )');       
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `z_4210_locale_contents` TO `locale_contents`');
        $this->execute('DROP TABLE IF EXISTS `locale_content_translations`');
        $this->execute('RENAME TABLE `z_4210_locale_content_translations` TO `locale_content_translations`');
    }
}
