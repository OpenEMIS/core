<?php
use Phinx\Migration\AbstractMigration;
  /**
 * POCOR-7289
 * adding homeroom teacher
**/
class POCOR7289 extends AbstractMigration
{

    public function up()
    {
      $this->execute("INSERT INTO `staff_change_types` (`id`,`code`, `name`) VALUES ( '6','CHANGE_IN_HOMEROOM_TEACHER', 'Change In Homeroom Teacher')");
    }

    public function down(){
        $this->execute("DELETE FROM `staff_change_types` WHERE `staff_change_types`.`id` = 6");
    }
}
?>
