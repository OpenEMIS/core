<?php
use Migrations\AbstractMigration;

class POCOR5294 extends AbstractMigration
{
    public function up()
    {
      $this->execute('CREATE TABLE `zz_5294_labels` LIKE `labels`');
      $this->execute('INSERT INTO `zz_5294_labels` SELECT * FROM `labels`');
      $this->execute("INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `created_user_id`, `created`) VALUES (uuid(), 'Counselling', 'Counselling', 'Institution-> Students-> Counselling', 'Requester', 1, NOW())");
    }

    //rollback
    public function down()
    {
      $this->execute('DROP TABLE IF EXISTS `labels`');
      $this->execute('RENAME TABLE `zz_5294_labels` TO `labels`');  
    }
}
