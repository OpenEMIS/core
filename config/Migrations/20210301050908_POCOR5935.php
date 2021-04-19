<?php
use Migrations\AbstractMigration;

class POCOR5935 extends AbstractMigration
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
        //backup
        $this->execute('CREATE TABLE `z_5935_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_5935_locale_contents` SELECT * FROM `locale_contents`');

        // locale_contents
        $localeContent = [
            [
                'en' => 'Institution Shifts',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Institution Status',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Needs Code',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],            
            [
                'en' => 'Needs Name',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Needs Type',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('locale_contents', $localeContent);
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `z_5935_locale_contents` TO `locale_contents`');
    }
}
