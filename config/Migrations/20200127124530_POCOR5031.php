<?php

use Phinx\Migration\AbstractMigration;

class POCOR5031 extends AbstractMigration
{
   
    public function up()
    {
       $this->execute('CREATE TABLE `z_5031_labels` LIKE `labels`');
       $this->execute('INSERT INTO `z_5031_labels` SELECT * FROM `labels`');
       $this->execute('UPDATE labels SET field_name = REPLACE(field_name, "OpenEMIS ID", "BEMIS ID")');
       
    }

    public function down()
    {
      $this->execute('DROP TABLE IF EXISTS `labels`');
      $this->execute('RENAME TABLE `z_5031_labels` TO `labels`');  
    }
}
