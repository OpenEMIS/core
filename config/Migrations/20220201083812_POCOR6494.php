<?php
use Migrations\AbstractMigration;

class POCOR6494 extends AbstractMigration
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
        // Backup locale_contents table
        $this->execute('DROP TABLE IF EXISTS `zz_6494_locale_contents`');
        $this->execute('CREATE TABLE `zz_6494_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `zz_6494_locale_contents` SELECT * FROM `locale_contents`');

        $current_time  = date('Y-m-d H:i:s');
        $localeContent = [
            [
                'en'              => 'Student Special Needs',
                'created_user_id' => 1,
                'created'         => $current_time
            ],
            [
                'en'              => 'Special Needs Device Type',
                'created_user_id' => 1,
                'created'         => $current_time
            ],
            [
                'en'              => 'Special Needs Service Type',
                'created_user_id' => 1,
                'created'         => $current_time
            ],
            [
                'en'              => 'Special Need Difficulty',
                'created_user_id' => 1,
                'created'         => $current_time
            ],
            [
                'en'              => 'Referrer',
                'created_user_id' => 1,
                'created'         => $current_time
            ],
            [
                'en'              => 'Special Needs Referrer Type',
                'created_user_id' => 1,
                'created'         => $current_time
            ],
            [
                'en'              => 'Reason Type',
                'created_user_id' => 1,
                'created'         => $current_time
            ],
        ];
        $this->insert('locale_contents', $localeContent);
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `zz_6494_locale_contents` TO `locale_contents`');
    }
}
