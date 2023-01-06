<?php

use Phinx\Migration\AbstractMigration;

class POCOR4832 extends AbstractMigration
{
    public function up()
    {
        // risk_criterias
        $this->execute('CREATE TABLE `z_4832_risk_criterias` LIKE `risk_criterias`');
        $this->execute('INSERT INTO `z_4832_risk_criterias` SELECT * FROM `risk_criterias`');
        $this->execute('UPDATE `risk_criterias` SET `criteria` = "SpecialNeedsAssessments" WHERE `criteria` = "SpecialNeeds"');

        // institution_student_risks
        $this->execute('CREATE TABLE `z_4832_institution_student_risks` LIKE `institution_student_risks`');
        $this->execute('INSERT INTO `z_4832_institution_student_risks` SELECT * FROM `institution_student_risks`');
    }

    public function down()
    {
        // risk_criterias
        $this->execute('DROP TABLE IF EXISTS `risk_criterias`');
        $this->execute('RENAME TABLE `z_4832_risk_criterias` TO `risk_criterias`');

        // institution_student_risks
        $this->execute('DROP TABLE IF EXISTS `institution_student_risks`');
        $this->execute('RENAME TABLE `z_4832_institution_student_risks` TO `institution_student_risks`');

    }
}
