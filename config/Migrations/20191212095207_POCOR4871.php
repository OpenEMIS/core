<?php

use Phinx\Migration\AbstractMigration;

class POCOR4871 extends AbstractMigration
{
    public function up()
    {
        // locale_contents
        $localeContent = [
            [
                'en' => 'Students with Special Needs',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('locale_contents', $localeContent);
    }

    public function down()
    {
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'Students with Special Needs'");
    }
}
