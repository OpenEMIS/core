<?php

use Phinx\Migration\AbstractMigration;

class POCOR7957 extends AbstractMigration
{
    public function up()
    {
        // Backup table
//        try {
            $this->execute('CREATE TABLE `z_7957 _transfer_logs` LIKE `transfer_logs`');
//        } catch (\Exception $e) {
//
//        }
//        try {
            $this->execute('INSERT INTO `z_7957 _transfer_logs` SELECT * FROM `transfer_logs`');
//        } catch (\Exception $e) {
//
//        }
//        try {
            $this->execute("ALTER TABLE `transfer_logs` ADD `completed_on` datetime DEFAULT NULL after `generated_on`");
//        } catch (\Exception $e) {
//
//        }
    }

    // rollback
    public function down()
    {
        // Restore table
//        try {
            $this->execute('DROP TABLE IF EXISTS `transfer_logs`');
//        } catch (\Exception $e) {
//
//        }
//        try {
            $this->execute('RENAME TABLE `z_7957 _transfer_logs` TO `transfer_logs`');
//        } catch (\Exception $e) {
//
//        }
    }
}
