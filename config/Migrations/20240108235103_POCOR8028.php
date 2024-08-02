<?php

use Phinx\Migration\AbstractMigration;

class POCOR8028 extends AbstractMigration
{

    public function up()
    {
        // backup
        $this->execute('CREATE TABLE `z_8028_institution_curriculars` LIKE `institution_curriculars`');
        $this->execute('INSERT INTO `z_8028_institution_curriculars` SELECT * FROM `institution_curriculars`');
        $this->execute('ALTER TABLE `institution_curriculars` DROP COLUMN `academic_period_id`');

    }

    // rollback
    public function down()
    {
        // institution_curriculars
        $this->execute('DROP TABLE IF EXISTS `institution_curriculars`');
        $this->execute('RENAME TABLE `z_8028_institution_curriculars` TO `institution_curriculars`');
    }
}
