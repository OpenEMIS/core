<?php
use Cake\I18n\Date;
use Phinx\Migration\AbstractMigration;

class POCOR4887 extends AbstractMigration
{
    public function up()
    {
        // locale_contents - start
        // backup
        $this->execute('CREATE TABLE `z_4887_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_4887_locale_contents` SELECT * FROM `locale_contents`');
        $today = date('Y-m-d H:i:s');
        $localeContent = [
            [
                'en' => 'No. of Staff on Leave',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Time In',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Time Out',
                'created_user_id' => 1,
                'created' => $today
            ],
        ];

        $this->insert('locale_contents', $localeContent);
        // locale_contents - end
    }

    public function down()
    {
        $this->dropTable('locale_contents');
        $this->execute('RENAME TABLE `z_4887_locale_contents` TO `locale_contents`');
    }
}
