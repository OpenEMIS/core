<?php
use Migrations\AbstractMigration;

class POCOR6179 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_6179_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_6179_locale_contents` SELECT * FROM `locale_contents`');

        // End

        $localeContent = [
            [
                'en' => 'Wealth Quintile',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Indigenous People',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Referrer Name',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Referrer Type',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Service Name',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Device Name',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Plan Name',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Scholarships',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Institutions Completeness',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ], [
                'en' => 'Parents and Guardians Information',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Mother Living with Student',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Mother is Deceased',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Father Living with Student',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Father is Deceased',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Guardian Living with Student',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Guardian is Deceased',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Student Outcomes',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Outcome Criteria',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ], [
                'en' => 'Outcome Grading Option',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'All Competencies',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Requester',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Benefit Type',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Day',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Total paid',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Right to Left',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Left to Right',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Params',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'en' => 'Response',
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
        $this->execute('RENAME TABLE `z_6179_locale_contents` TO `locale_contents`');

    }
}
