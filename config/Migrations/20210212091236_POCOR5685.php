<?php
use Migrations\AbstractMigration;

class POCOR5685 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        // Backup locale_contents table
        $this->execute('CREATE TABLE `z_5685_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_5685_locale_contents` SELECT * FROM `locale_contents`');
        $this->execute('CREATE TABLE `z_5685_locale_content_translations` LIKE `locale_content_translations`');
        $this->execute('INSERT INTO `z_5685_locale_content_translations` SELECT * FROM `locale_content_translations`');
        // End
		
		$localeContent = [

            [
                'en' => 'People',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
            
        ];
        $this->insert('locale_contents', $localeContent);


        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'People'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        $localeContentsId = $getLocaleContentsId[0]['id'];

        $getLocales1= $this->query("SELECT * FROM locales WHERE `name` = 'العربية' AND `iso` = 'ar'");
        $getLocalesId1 = $getLocales1->fetchAll();
        $localeId1 = $getLocalesId1[0]['id'];

        $getLocales2= $this->query("SELECT * FROM locales WHERE `name` = '中文' AND `iso` = 'zh'");
        $getLocalesId2 = $getLocales2->fetchAll();
        $localeId2 = $getLocalesId2[0]['id'];
        
        $getLocales4= $this->query("SELECT * FROM locales WHERE `name` = 'Français' AND `iso` = 'fr'");
        $getLocalesId4 = $getLocales4->fetchAll();
        $localeId4 = $getLocalesId4[0]['id'];

        $getLocales5= $this->query("SELECT * FROM locales WHERE `name` = 'русский' AND `iso` = 'ru'");
        $getLocalesId5 = $getLocales5->fetchAll();
        $localeId5 = $getLocalesId5[0]['id'];

        $getLocales6= $this->query("SELECT * FROM locales WHERE `name` = 'español' AND `iso` = 'es'");
        $getLocalesId6 = $getLocales6->fetchAll();
        $localeId6 = $getLocalesId6[0]['id'];


        $locale_content_translations_data = [
            [
                'translation' => 'الأشخاص',
                'locale_content_id' => $localeContentsId,
                'locale_id' => $localeId1,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'translation' => '',
                'locale_content_id' => $localeContentsId,
                'locale_id' => $localeId2,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'translation' => '',
                'locale_content_id' => $localeContentsId,
                'locale_id' => $localeId4,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'translation' => '',
                'locale_content_id' => $localeContentsId,
                'locale_id' => $localeId5,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'translation' => '',
                'locale_content_id' => $localeContentsId,
                'locale_id' => $localeId6,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];

        $this->insert('locale_content_translations', $locale_content_translations_data);
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `z_5685_locale_contents` TO `locale_contents`');
        $this->execute('DROP TABLE IF EXISTS `locale_content_translations`');
        $this->execute('RENAME TABLE `z_5685_locale_content_translations` TO `locale_content_translations`');
    }
}
