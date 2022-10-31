<?php

use Phinx\Migration\AbstractMigration;

class POCOR5149 extends AbstractMigration {

    public function up() {

        // Backup locale_contents table
        $this->execute('CREATE TABLE `z_5149_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_5149_locale_contents` SELECT * FROM `locale_contents`');
        // End


        $localeContent = [
            [
                'en' => 'Default Identity type',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Student Absent',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Parent Name',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];

        $this->insert('locale_contents', $localeContent);
    }

    public function down() {
        // For locale_contents
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute("RENAME TABLE `z_5149_locale_contents` TO `locale_contents`");
    }

}
