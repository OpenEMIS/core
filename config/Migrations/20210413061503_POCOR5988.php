<?php
use Migrations\AbstractMigration;

class POCOR5988 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_5988_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_5988_locale_contents` SELECT * FROM `locale_contents`');  

         $data = [
            [
                'en' => 'Enrolled (Repeater)',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s'),
            ]
        ];

        $this->insert('locale_contents', $data);


    }

    // rollback
    public function down()
    {
        $this->execute('RENAME TABLE `z_5988_locale_contents` TO `locale_contents`');  
    }
}
