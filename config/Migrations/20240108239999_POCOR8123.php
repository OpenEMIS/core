<?php

use Phinx\Migration\AbstractMigration;

class POCOR8123 extends AbstractMigration
{

    public function up()
    {
        // backup
        $this->execute('SET FOREIGN_KEY_CHECKS = 0');
        $this->execute('CREATE TABLE `z_8123_institution_staff_surveys` LIKE `institution_staff_surveys`');
        $this->execute('INSERT INTO `z_8123_institution_staff_surveys` SELECT * FROM `institution_staff_surveys`');
        $this->execute('ALTER TABLE `institution_staff_surveys` DROP FOREIGN KEY `institution_staff_surveys_ibfk_6`'); 
        $this->execute('ALTER TABLE `institution_staff_surveys` ADD CONSTRAINT `institution_staff_surveys_ibfk_6` FOREIGN KEY (`parent_form_id`) REFERENCES `institution_surveys`(`survey_form_id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
        $this->execute('SET FOREIGN_KEY_CHECKS = 1');

    }

    // rollback
    public function down()
    {
        // institution_staff_surveys
        $this->execute('SET FOREIGN_KEY_CHECKS = 0');
        $this->execute('DROP TABLE IF EXISTS `institution_staff_surveys`');
        $this->execute('RENAME TABLE `z_institution_staff_surveys` TO `institution_staff_surveys`');
        $this->execute('SET FOREIGN_KEY_CHECKS = 0');
    }
}
