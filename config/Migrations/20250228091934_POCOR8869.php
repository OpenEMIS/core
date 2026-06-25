<?php
use Migrations\AbstractMigration;

class POCOR8869 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `zz_8869_alerts` LIKE `alerts`');
        $this->execute('INSERT INTO `zz_8869_alerts` SELECT * FROM `alerts`');
        $today = date('Y-m-d H:i:s');

        // alerts
        $alertData = [
            'name' => 'StudentAdmission',
            'process_name' => 'AlertStudentAdmission',
            'created_user_id' => 1,
            'created' => $today,
            'frequency'=>'Once'
        ];

        // $this->insert('alerts', $alertData);

        $this->execute("INSERT INTO `alerts` 
                (`name`, `process_name`, `process_id`, `frequency`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
                VALUES 
                ('StudentAdmission', 'AlertStudentAdmission', NULL, 'Once', NULL, '$today', 1, '$today')");
    }

    public function down()
    {
       $this->execute('DROP TABLE IF EXISTS `alerts`');
       $this->execute('RENAME TABLE `zz_8869_alerts` TO `alerts`');
    }
}
