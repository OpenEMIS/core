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
        //Arabic
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
        // Chinese

        $this->execute('
            INSERT INTO `locale_content_translations` (`translation`,`locale_content_id`, `locale_id`,`created_user_id`,`created`)
            VALUES("404禁止：找不到页面
                ",(SELECT id FROM locale_contents WHERE en="404 Forbidden: Page Not Found"),
            2,
            1,
            "'.date("Y-m-d H:i:s").'"
        )');  
        $this->execute('
            INSERT INTO `locale_content_translations` (`translation`,`locale_content_id`, `locale_id`,`created_user_id`,`created`)
            VALUES("您要查找的页面可能已被删除，重命名或暂时不可用。如有任何疑问，请联系管理员
                ",(SELECT id FROM locale_contents WHERE en="The page you are looking for might have been removed, renamed or is temporarily unavailable. If you have any enquiries, please contact the administrator"),
            2,
            1,
            "'.date("Y-m-d H:i:s").'"
            )');
        $this->execute('
            INSERT INTO `locale_content_translations` (`translation`,`locale_content_id`, `locale_id`,`created_user_id`,`created`)
            VALUES("这里
                ",(SELECT id FROM locale_contents WHERE en="here"),
            2,
            1,
            "'.date("Y-m-d H:i:s").'"
            )');
        //French
        $this->execute('
            INSERT INTO `locale_content_translations` (`translation`,`locale_content_id`, `locale_id`,`created_user_id`,`created`)
            VALUES("404 Interdit: page introuvable
                ",(SELECT id FROM locale_contents WHERE en="404 Forbidden: Page Not Found"),
            4,
            1,
            "'.date("Y-m-d H:i:s").'"
        )');  
        $this->execute('
            INSERT INTO `locale_content_translations` (`translation`,`locale_content_id`, `locale_id`,`created_user_id`,`created`)
            VALUES
            ("La page que vous recherchez a peut-être été supprimée, renommée ou temporairement indisponible. Si vous avez des questions, veuillez contacter l administrateur
                ",(SELECT id FROM locale_contents WHERE en="The page you are looking for might have been removed, renamed or is temporarily unavailable. If you have any enquiries, please contact the administrator"),
            4,
            1,
            "'.date("Y-m-d H:i:s").'"
            )');
        $this->execute('
            INSERT INTO `locale_content_translations` (`translation`,`locale_content_id`, `locale_id`,`created_user_id`,`created`)
            VALUES("ici
                ",(SELECT id FROM locale_contents WHERE en="here"),
            4,
            1,
            "'.date("Y-m-d H:i:s").'"
            )');
        //Russian
        $this->execute('
            INSERT INTO `locale_content_translations` (`translation`,`locale_content_id`, `locale_id`,`created_user_id`,`created`)
            VALUES("404 Запрещено: Страница не найдена
                ",(SELECT id FROM locale_contents WHERE en="404 Forbidden: Page Not Found"),
            5,
            1,
            "'.date("Y-m-d H:i:s").'"
        )');  
        $this->execute('
            INSERT INTO `locale_content_translations` (`translation`,`locale_content_id`, `locale_id`,`created_user_id`,`created`)
            VALUES
            ("Возможно, страница, которую вы ищете, была удалена, переименована или временно недоступна. Если у вас есть какие-либо вопросы, пожалуйста, свяжитесь с администратором
                ",(SELECT id FROM locale_contents WHERE en="The page you are looking for might have been removed, renamed or is temporarily unavailable. If you have any enquiries, please contact the administrator"),
            5,
            1,
            "'.date("Y-m-d H:i:s").'"
            )');
        $this->execute('
            INSERT INTO `locale_content_translations` (`translation`,`locale_content_id`, `locale_id`,`created_user_id`,`created`)
            VALUES("Вот
                ",(SELECT id FROM locale_contents WHERE en="here"),
            5,
            1,
            "'.date("Y-m-d H:i:s").'"
            )');
        //Spanish
        $this->execute('
            INSERT INTO `locale_content_translations` (`translation`,`locale_content_id`, `locale_id`,`created_user_id`,`created`)
            VALUES("404 Prohibida: Página no encontrada
                ",(SELECT id FROM locale_contents WHERE en="404 Forbidden: Page Not Found"),
            6,
            1,
            "'.date("Y-m-d H:i:s").'"
        )');  
        $this->execute('
            INSERT INTO `locale_content_translations` (`translation`,`locale_content_id`, `locale_id`,`created_user_id`,`created`)
            VALUES
            ("La página que está buscando podría haberse eliminado, renombrado o no está disponible temporalmente. Si tiene alguna consulta, comuníquese con el administrador
                ",(SELECT id FROM locale_contents WHERE en="The page you are looking for might have been removed, renamed or is temporarily unavailable. If you have any enquiries, please contact the administrator"),
            6,
            1,
            "'.date("Y-m-d H:i:s").'"
            )');
        $this->execute('
            INSERT INTO `locale_content_translations` (`translation`,`locale_content_id`, `locale_id`,`created_user_id`,`created`)
            VALUES("aquí
                ",(SELECT id FROM locale_contents WHERE en="here"),
            6,
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
