<?php
use Migrations\AbstractMigration;

class POCOR7462 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `zz_7462_alerts` LIKE `alerts`');
        $this->execute('INSERT INTO `zz_7462_alerts` SELECT * FROM `alerts`');
        $today = date('Y-m-d H:i:s');

        // alerts
        $alertData = [
            'name' => 'CaseEscalation',
            'process_name' => 'AlertCaseEscalation',
            'created_user_id' => 1,
            'created' => $today,
            'frequency'=>'Daily'
        ];

        $this->insert('alerts', $alertData);
    }

    public function down()
    {
       $this->execute('DROP TABLE IF EXISTS `alerts`');
       $this->execute('RENAME TABLE `zz_7462_alerts` TO `alerts`');
    }
}
