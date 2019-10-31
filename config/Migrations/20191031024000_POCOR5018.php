<?php

use Phinx\Migration\AbstractMigration;

class POCOR5018 extends AbstractMigration
{
    public function up()
    {
        // security_functions

        $this->execute("INSERT into `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) values('9001','Competencies','Students','Institutions','Students - Academic','2000','Competencies.index|Competencies.view',NULL,NULL,NULL,NULL,'391','1',NULL,'2','2019-10-31 06:44:39','1','2019-10-31 11:05:55')");
    }

    public function down()
    {
        $this->execute("DELETE FROM `security_functions` WHERE `id` = 9001");
    }
}
