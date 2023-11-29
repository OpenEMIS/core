<?php

use Phinx\Migration\AbstractMigration;

class POCOR7870 extends AbstractMigration
{
    public function up()
    {
        // Set FOREIGN_KEY_CHECKS to 0 to avoid errors caused by inconsisted records already present in the table (if any exist)
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        //backup
        $this->execute('DROP TABLE IF EXISTS `z_7870_institution_staff_transfers`');
        $this->execute('CREATE TABLE `z_7870_institution_staff_transfers` LIKE `institution_staff_transfers`');
        $this->execute('INSERT INTO `z_7870_institution_staff_transfers` SELECT * FROM `institution_staff_transfers`');

        $this->execute("ALTER TABLE `institution_staff_transfers` ADD `is_homeroom` TINYINT(1) NOT NULL DEFAULT '0' AFTER `all_visible`");
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }

    //rollback
    public function down()
    {
        // Set FOREIGN_KEY_CHECKS to 0 to avoid errors caused by inconsisted records already present in the table (if any exist)
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->execute('DROP TABLE IF EXISTS `institution_staff_transfers`');
        $this->execute('RENAME TABLE `z_7870_institution_staff_transfers` TO `institution_staff_transfers`');
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }
}
