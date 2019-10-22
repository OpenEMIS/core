<?php

use Phinx\Migration\AbstractMigration;

class POCOR4944 extends AbstractMigration
{
    public function up()
    {
		// locale_contents
        $this->execute('CREATE TABLE `zz_4944_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `zz_4944_locale_contents` SELECT * FROM `locale_contents`');
		
        // locale_contents
        $localeContents = [
            [
                'en' => 'Staff Position Titles',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Security Role',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Project Director',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'District Education Manager',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Administrative Officer',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Assistant Education Officer',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Director Education Planning Unit*',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Director Policy, Planning, Research & Eval Unit',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'District Education Officer',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];


        foreach ($localeContents as $localeContent) {

            // query()
            $localeContentData = $this->query('SELECT * FROM `locale_contents` WHERE `en`="' . $localeContent['en'] . '"'); // returns PDOStatement
            $rows = $localeContentData->fetchAll(); // returns the result as an array

            if (count($rows) <= 0) {
                $this->insert('locale_contents', $localeContent);
            }
        }
    }

    public function down()
    {
        // locale_contents
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `zz_4944_locale_contents` TO `locale_contents`');
    }
}
