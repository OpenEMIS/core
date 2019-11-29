<?php

use Phinx\Migration\AbstractMigration;

class POCOR5043 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `z_5043_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_5043_locale_contents` SELECT * FROM `locale_contents`');

        $localeContent = [
            [
                'en' => 'Timetable',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
			[
                'en' => 'Timetables',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Intervals',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Terms',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Schedules',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Time Slots',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Timetable will be automatically saved',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Interval (mins)',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Interval',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'mins',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('locale_contents', $localeContent);
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `z_5043_locale_contents` TO `locale_contents`');
    }
}
