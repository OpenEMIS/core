<?php

use Migrations\AbstractMigration;

class POCOR6391 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_6391_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_6391_locale_contents` SELECT * FROM `locale_contents`');

        //locale 
        $now = date('Y-m-d H:i:s');
        $localeContent = [
            [
                'en' => 'Certificate',
                'created_user_id' => 1,
                'created' => $now
            ],
            [
                'en' => 'Attendance Days',
                'created_user_id' => 1,
                'created' => $now
            ],
            [
                'en' => 'Exam',
                'created_user_id' => 1,
                'created' => $now
            ],
            [
                'en' => 'Practical',
                'created_user_id' => 1,
                'created' => $now
            ]
        ];
        $this->insert('locale_contents', $localeContent);
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `z_6391_locale_contents` TO `locale_contents`');
    }
}
