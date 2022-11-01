<?php

use Phinx\Migration\AbstractMigration;

class POCOR4609 extends AbstractMigration
{
    public function up()
    {
        // create a backup for institution_staff_appraisals
        $this->execute('CREATE TABLE `z_4609_institution_staff_appraisals` LIKE `institution_staff_appraisals`');
        $this->execute('INSERT INTO `z_4609_institution_staff_appraisals` SELECT * FROM `institution_staff_appraisals`');

        //to remove title column
        $this->execute('ALTER TABLE `institution_staff_appraisals` DROP `title`');

    }

    public function down()
    {
        // institution_staff_appraisals
        $this->execute('DROP TABLE IF EXISTS `institution_staff_appraisals`');
        $this->execute('RENAME TABLE `z_4609_institution_staff_appraisals` TO `institution_staff_appraisals`');

    }
}
