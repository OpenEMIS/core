<?php
use Migrations\AbstractMigration;

class POCOR6816 extends AbstractMigration
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
        //back up table
        // $this->execute('CREATE TABLE `zz_6281_institution_grades` LIKE `institution_grades`');
        // $this->execute('INSERT INTO `zz_6281_institution_grades` SELECT * FROM `institution_grades`');

        // $this->execute('RENAME TABLE `transfer_connections` TO `data_management_connections`');
        // Create tables
        // $this->execute("CREATE TABLE IF NOT EXISTS `data_management_logs` (
        //     `id` int(11) NOT NULL AUTO_INCREMENT,
        //     `type` varchar(200) COLLATE utf8mb4_unicode_ci,
        //     `name` varchar(200) COLLATE utf8mb4_unicode_ci ,
        //     `path` varchar(200) COLLATE utf8mb4_unicode_ci ,
        //     `feature` varchar(200) COLLATE utf8mb4_unicode_ci,
        //     `from_academic_period_id` int(11),
        //     `to_academic_period_id` int(11),
        //     `created_user_id` int(11),
        //     `created` date ,
        //     PRIMARY KEY (`id`)
        //   )");

          $managementCopyData = $this->query("SELECT * FROM data_management_copy");

          $getManagementCopyData = $managementCopyData->fetchAll();
          foreach($getManagementCopyData AS $getManagementCopy){
            $this->insert('data_management_logs', [
                'id' => $getManagementCopy['id'],
                'type' => "aaa",
                'name' => "addsf",
                'path' => "dfs",
                'feature' => "dfdsfsd",
                'from_academic_period_id' => 25,
                'to_academic_period_id' => 26,
                'created_user_id' => "2",
                'created' => date('Y-m-d')
            ]);
          }


          $transferLogsData = $this->query("SELECT * FROM transfer_logs");

          $transferLogsDataVal = $transferLogsData->fetchAll();
          echo "<pre>";print_r($transferLogsDataVal);die;
          // foreach($transferLogsDataVal AS $transferLogsData){
          //   $this->insert('data_management_logs', [
          //       'type' => 'Transfer',
          //       'name' => $transferLogsData['features'],
          //       'path' => 'Data Management -> Backup',
          //       'feature' => $transferLogsData['features'],
          //       'from_academic_period_id' => $transferLogsData['academic_period_id'],
          //       'to_academic_period_id' => '0',
          //       'created_user_id' => $transferLogsData['generated_by'],
          //       'created' => date('Y-m-d')
          //   ]);
          // }


          // $backupLogsData = $this->query("SELECT * FROM backup_logs");

          // $backupLogsDataVal = $backupLogsData->fetchAll();
          // foreach($backupLogsDataVal AS $backupLogsData){
          //   $this->insert('data_management_logs', [
          //       'type' => 'Back up',
          //       'name' => $backupLogsData['name'],
          //       'path' => $backupLogsData['path'],
          //       'feature' => 'Back up',
          //       'from_academic_period_id' => '0',
          //       'to_academic_period_id' => '0',
          //       'created_user_id' => $backupLogsData['generated_by'],
          //       'created' => date('Y-m-d')
          //   ]);
          // }
    }
}
