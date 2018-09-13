<?php

use Phinx\Migration\AbstractMigration;

class POCOR4788 extends AbstractMigration
{
    public function up()
    {
        // alerts
        $Alerts = $this->table('alerts');

        $alertData = [
            'name' => 'ScholarshipApplication',
            'process_name' => 'AlertScholarshipApplication',
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ];

        $Alerts
            ->insert($alertData)
            ->save();

        // locale_contents
        $this->execute('CREATE TABLE `z_4788_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_4788_locale_contents` SELECT * FROM `locale_contents`');
        $today = date('Y-m-d H:i:s');

        $localeContentData = [
            [
                'en' => 'Current Workflow Assignee',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Scholarship Application',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Days before end of application close date',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'To Do',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'In Progress',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Done',
                'created_user_id' => 1,
                'created' => $today
            ]
        ];

        $this->insert('locale_contents', $localeContentData);
    }   

    public function down()
    {
        $this->execute('DELETE FROM `alerts` WHERE name = "ScholarshipApplication"');
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `z_4788_locale_contents` TO `locale_contents`');
    }
}
