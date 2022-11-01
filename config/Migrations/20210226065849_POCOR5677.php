<?php
use Migrations\AbstractMigration;

class POCOR5677 extends AbstractMigration
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
                'en' => 'Bulk Student Transfer In',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Pending Student Transfer',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Pending Approval From Receiving Institution',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Sending Institution',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        
        $this->insert('locale_contents', $localeContent);
    }

    // rollback
    public function down()
    {
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'Bulk Student Transfer In'");
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'Pending Student Transfer'");
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'Pending Approval From Receiving Institution'");
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'Sending Institution'");
    }
}
