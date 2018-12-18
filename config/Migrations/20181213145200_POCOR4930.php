<?php
use Phinx\Migration\AbstractMigration;

class POCOR4930 extends AbstractMigration
{
    public function up()
    {
        $this->execute('ALTER TABLE `institution_staff_attendance_activities` MODIFY COLUMN `field` varchar(200) NULL');
        $this->execute('ALTER TABLE `institution_staff_attendance_activities` MODIFY COLUMN `field_type` varchar(200) NULL');
    }

    public function down()
    {
      $this->execute('ALTER TABLE `institution_staff_attendance_activities` MODIFY COLUMN `field` varchar(200) NOT NULL');
      $this->execute('ALTER TABLE `institution_staff_attendance_activities` MODIFY COLUMN `field_type` varchar(200) NOT NULL');
    }
}
