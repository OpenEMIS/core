<?php

use Phinx\Migration\AbstractMigration;

class POCOR7984 extends AbstractMigration
{
    public function up()
    {
        // Backup locale_contents table
        $this->execute('CREATE TABLE `z_7984_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_7984_locale_contents` SELECT * FROM `locale_contents`');
        // End


        $localeContent = [

            [
                'en' => 'Number Of Students By Grade',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
        ];
        $this->insert('locale_contents', $localeContent);

    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `z_7984_locale_contents` TO `locale_contents`');
    }

}
