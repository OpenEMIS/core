<?php

use Migrations\AbstractMigration;

class POCOR9415 extends AbstractMigration
{
    public function up()
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');

        // Backup original table
        $this->execute('DROP TABLE IF EXISTS `z_9415_institution_shifts`');
        $this->execute('CREATE TABLE `z_9415_institution_shifts` LIKE `institution_shifts`');
        $this->execute('INSERT INTO `z_9415_institution_shifts` SELECT * FROM `institution_shifts`');

        // Delete the specific shift
        $this->execute('DELETE FROM `institution_shifts` WHERE `id` = 327');

        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down()
    {
        if ($this->hasTable('z_9415_institution_shifts')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('DROP TABLE IF EXISTS `institution_shifts`');
            $this->execute('RENAME TABLE `z_9415_institution_shifts` TO `institution_shifts`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
}
