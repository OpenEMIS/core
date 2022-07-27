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

        // Create tables
        // $this->execute("CREATE TABLE IF NOT EXISTS `data_management_logs` (
        //     `id` int(11) NOT NULL AUTO_INCREMENT,
        //     `type` int(11),
        //     `name` int(11) ,
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
            // echo "<pre>";print_r($getManagementCopy);die;
            $this->insert('data_management_logs', [
                'id' => $getManagementCopy['id'],
                'type' => 'Copy',
                'name' => $getManagementCopy['features'],
                'path' => 'Archive > Archives > Copy',
                'feature' => $getManagementCopy['features'],
                'from_academic_period_id' => $getManagementCopy['from_academic_period'],
                'to_academic_period_id' => $getManagementCopy['to_academic_period'],
                'created_user_id' => 2,
                'created' => date('Y-m-d')
            ]);
          }
    }
}
