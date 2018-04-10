<?php

use Phinx\Migration\AbstractMigration;

class POCOR4435 extends AbstractMigration
{
    public function up()
    {
        // locale_contents
        $localeContent = [
            [
                'en' => 'You have entered an invalid URL.',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('locale_contents', $localeContent);
    }

    public function down()
    {
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'You have entered an invalid URL.'");
    }
}
