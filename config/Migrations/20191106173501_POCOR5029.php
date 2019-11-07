<?php

use Phinx\Migration\AbstractMigration;

class POCOR5029 extends AbstractMigration
{
    public function up()
    {
       $this->execute("Delete from config_items where name = 'Title'");
    }

    public function down()
    {
       $this->execute("Insert into `config_items` (`name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) values('Title','adaptation','System','Title','BEMIS Core','OpenEMIS Core','1','1','','','180324','2019-05-20 09:34:17','1','1970-01-01 00:00:00')");
    }   
}
