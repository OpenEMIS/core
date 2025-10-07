<?php

use Migrations\AbstractMigration;

class POCOR9423 extends AbstractMigration
{
    public function up()
    {
        // Temporarily disable foreign key checks
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');

        // Backup original table
        $this->execute('DROP TABLE IF EXISTS `z_9423_institutions`');
        $this->execute('CREATE TABLE `z_9423_institutions` LIKE `institutions`');
        $this->execute('INSERT INTO `z_9423_institutions` SELECT * FROM `institutions`');

        // Get highest existing ID from security_groups, then add 10 to ensure non-existent
        $result = $this->fetchRow('SELECT MAX(id) as max_id FROM security_groups');
        $nonExistentGroupId = $result['max_id'] + 10;

        // Set a non-existent security_group_id for institution ID 6
        $this->execute("UPDATE `institutions` SET `security_group_id` = $nonExistentGroupId WHERE `id` = 6");

        // Re-enable foreign key checks
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down()
    {
        // Restore from backup if available
        if ($this->hasTable('z_9423_institutions')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('DROP TABLE IF EXISTS `institutions`');
            $this->execute('RENAME TABLE `z_9423_institutions` TO `institutions`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
}
