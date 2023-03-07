<?php
use Migrations\AbstractMigration;

class POCOR7237 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    // commit
    public function up()
    {
        // Backup table
        $this->execute('CREATE TABLE `zz_7237_transfer_logs` LIKE `transfer_logs`');
        $this->execute('INSERT INTO `zz_7237_transfer_logs` SELECT * FROM `transfer_logs`');

        $this->execute("ALTER TABLE `transfer_logs` ADD `process_status` INT(11) DEFAULT NULL");
        $this->execute("ALTER TABLE `transfer_logs` ADD `p_id` INT(11) DEFAULT NULL");
    }

    // rollback
    public function down()
    {
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `transfer_logs`');
        $this->execute('RENAME TABLE `zz_7237_transfer_logs` TO `transfer_logs`');
    }
}
