<?php

use Phinx\Migration\AbstractMigration;

class POCOR4518 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `z_4518_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_4518_locale_contents` SELECT * FROM `locale_contents`');

        $localeContent = [
            [
                'en' => 'The selected institution only accepts Male student.',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'The selected institution only accepts Female student.',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Gender is not editable in Directories',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Please review the information before proceeding with the operation',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Code',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Name',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Security Role',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Position Grade Selection',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Select Position Grade',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Select All Position Grades',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Position Grades',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Length should not exceed 5 digits or decimal places should not exceed 3 digits',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Current Assignee',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'New Assignee',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Reassign',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Error Validation: New Assignee cannot be the same as Current Assignee',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Reopen',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Appraisal Type',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Appraisal Form',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Appraisal Period From',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Appraisal Period To',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Date Appraised',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Pending Approval From Sending Institution',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Pending Student Admission',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Admitted',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('locale_contents', $localeContent);
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `z_4518_locale_contents` TO `locale_contents`');
    }
}
