<?php
use Phinx\Migration\AbstractMigration;

class POCOR7298 extends AbstractMigration
{
   /**
 * POCOR-7298
 * done
**/
    public function up()
    {
      $this->execute("INSERT INTO `staff_change_types` (`id`, `code`, `name`) VALUES ('0', 'HOMEROOM_TEACHER', 'Homeroom Teacher')");
    }

    public function down(){
        $this->execute("DELETE FROM `staff_change_types` WHERE `staff_change_types`.`id` = 6");
    }
}
