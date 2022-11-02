<?php

use Phinx\Migration\AbstractMigration;

class POCOR4852 extends AbstractMigration
{
    public function up()
    {
        $today = date('Y-m-d H:i:s');

        // alerts
        $this->execute('CREATE TABLE `z_4852_alerts` LIKE `alerts`');
        $this->execute('INSERT INTO `z_4852_alerts` SELECT * FROM `alerts`');

        $alertData = [
            'name' => 'ScholarshipDisbursement',
            'process_name' => 'AlertScholarshipDisbursement',
            'created_user_id' => 1,
            'created' => $today
        ];

        $this->insert('alerts', $alertData);
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `alerts`');
        $this->execute('RENAME TABLE `z_4852_alerts` TO `alerts`');
    }
}
