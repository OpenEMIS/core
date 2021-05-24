<?php
use Migrations\AbstractMigration;
use Cake\I18n\Date;


class POCOR6054 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_6056_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_6056_locale_contents` SELECT * FROM `locale_contents`');

        // End

        $localeContent = [
            [
                'en' => 'Next Institution Class',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Current Class',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Institution Trip',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'No Student record found',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Assign',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Search Unassigned Students',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Select Staff or Leave Blank',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'There are no records',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Districts',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Scholarship Name',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'All categories',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Scholarship Applications',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Scholarship',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Insurances',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Demographic',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'End Staff Positions',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'End Infrastructure Usage',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Withdraw Students',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'New Status',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Current Status',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Active',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Inactive',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Logo Content',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Institution Provider',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
    ];
        $this->insert('locale_contents', $localeContent);
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `z_6056_locale_contents` TO `locale_contents`');

    }
}
