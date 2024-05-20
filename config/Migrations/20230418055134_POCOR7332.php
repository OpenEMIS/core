<?php
use Migrations\AbstractMigration;

class POCOR7332 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_7332_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_7332_locale_contents` SELECT * FROM `locale_contents`');
        $this->execute('CREATE TABLE `z_7332_locale_content_translations` LIKE `locale_content_translations`');
        $this->execute('INSERT INTO `z_7332_locale_content_translations` SELECT * FROM `locale_content_translations`');
        // End

        $localeContent = [

            [
                'en' => 'Weighted Marks',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];

        $this->insert('locale_contents', $localeContent);
        $getLocales1= $this->query("SELECT * FROM locales WHERE `name` = 'العربية' AND `iso` = 'ar'");
        $getLocalesId1 = $getLocales1->fetchAll();
        $localeId1 = $getLocalesId1[0]['id'];
        
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Weighted Marks'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'العلامات المرجحة',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }

    }


    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `z_7332_locale_contents` TO `locale_contents`');
        $this->execute('DROP TABLE IF EXISTS `locale_content_translations`');
        $this->execute('RENAME TABLE `z_7332_locale_content_translations` TO `locale_content_translations`');
    }
}
