<?php

use Migrations\AbstractMigration;

class POCOR6417 extends AbstractMigration
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
        // Backup
        $this->execute('CREATE TABLE `zz_6417_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `zz_6417_locale_contents` SELECT * FROM `locale_contents`');

        // Remove
        $this->execute("DELETE FROM `locale_contents` WHERE `en` IN ('Exam','Practical','Attendance Days','Certificate')");

        // Adding locale 
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

    // Rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `zz_6417_locale_contents` TO `locale_contents`');
    }
}
