<?php
use Migrations\AbstractMigration;

class POCOR7559 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `zz_7559_alerts` LIKE `alerts`');
        $this->execute('INSERT INTO `zz_7559_alerts` SELECT * FROM `alerts`');
        $today = date('Y-m-d H:i:s');

        // alerts
        $alertData = [
            'name' => 'SystemUpdates',
            'process_name' => 'AlertSystemUpdates',
            'created_user_id' => 1,
            'created' => $today,
            'frequency'=>'Once'
        ];

        $this->insert('alerts', $alertData);
    }

    public function down()
    {
       $this->execute('DROP TABLE IF EXISTS `alerts`');
       $this->execute('RENAME TABLE `zz_7559_alerts` TO `alerts`');
    }
}
