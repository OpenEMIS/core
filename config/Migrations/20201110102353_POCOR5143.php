<?php
use Migrations\AbstractMigration;

class POCOR5143 extends AbstractMigration
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
                'en' => 'First Shift Gender',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Second Shift Gender',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Third Shift Gender',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Fourth Shift Gender',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Total Gender',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        
        $this->insert('locale_contents', $localeContent);

    }

    // rollback
    public function down()
    {
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'First Shift Gender'");
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'Second Shift Gender'");
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'Third Shift Gender'");
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'Fourth Shift Gender'");
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'Total Gender'");
    }
}
