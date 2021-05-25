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
        $this->execute('CREATE TABLE `z_6054_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_6054_locale_contents` SELECT * FROM `locale_contents`');

        // End

        $localeContent = [
            [
                'en' => 'Status Checked',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Database Name',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Host Port',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Host',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Database Type',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Rule Events',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Implementer',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'National Content',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Targeting',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ], [
                'en' => 'Scholarship Name',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Financial Assistance Type',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Recipient',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Bond',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Annual Award Amount',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Application Close Date',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Application Open Date',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Scholarship Name',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Appraisal Types',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ], [
                'en' => 'Final Score',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Generate End Date',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Generate Start Date',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Translation',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Add Meeting',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Date of Meeting',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Student Meals',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Distribution',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Add Funding Source',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Add Need',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Select Report Class',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'No. of Staff on Late',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Duty Types',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Current Class',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Parents and Guardian Informations section',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
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
        $this->execute('RENAME TABLE `z_6054_locale_contents` TO `locale_contents`');

    }
}
