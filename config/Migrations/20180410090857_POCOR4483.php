<?php

use Phinx\Migration\AbstractMigration;

class POCOR4483 extends AbstractMigration
{
    public function up()
    {
        // locale_contents
        $localeContent = [
            [
                'en' => 'File records exceeds maximum size allowed',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'File format not supported',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'The file cannot be imported due to errors encountered.',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('locale_contents', $localeContent);
    }

    public function down()
    {
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'File records exceeds maximum size allowed'");
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'File format not supported'");
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'The file cannot be imported due to errors encountered.'");
    }
}
