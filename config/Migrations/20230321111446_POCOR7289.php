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
       // Backup table
        $this->execute('CREATE TABLE `zz_7289_staff_change_types` LIKE `staff_change_types`');
        $this->execute('INSERT INTO `zz_7289_staff_change_types` SELECT * FROM `staff_change_types`');


      $this->execute("INSERT INTO `staff_change_types` (`id`,`code`, `name`) VALUES ( '6','HOMEROOM_TEACHER', 'Homeroom Teacher')");
    }

    public function down(){

        // Restore table
        $this->execute('DROP TABLE IF EXISTS `staff_change_types`');
        $this->execute('RENAME TABLE `zz_7289_staff_change_types` TO `staff_change_types`');

    }
}
?>
