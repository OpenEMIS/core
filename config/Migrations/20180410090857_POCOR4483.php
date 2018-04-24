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
            ],
            [
                'en' => 'The uploaded file exceeds the max filesize upload limits.',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'The uploaded file was only partially uploaded.',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'No file was uploaded.',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Missing a temporary folder. Please contact your network administrator for assistance.',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Failed to write file to disk. Please contact your network administrator for assistance.',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'A PHP extension stopped the file upload. Please contact your network administrator for assistance.',
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
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'The uploaded file exceeds the max filesize upload limits.'");
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.'");
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'The uploaded file was only partially uploaded.'");
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'No file was uploaded.'");
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'Missing a temporary folder. Please contact your network administrator for assistance.'");
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'Failed to write file to disk. Please contact your network administrator for assistance.'");
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'A PHP extension stopped the file upload. Please contact your network administrator for assistance.'");
    }
}
