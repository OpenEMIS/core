<?php
use Migrations\AbstractMigration;

class POCOR5167 extends AbstractMigration
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
        $localeContent = [
            [
                'en' => 'Source',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Budget',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        
        $this->insert('locale_contents', $localeContent);

    }

    // rollback
    public function down()
    {
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'Source'");
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'Budget'");
    }
}
