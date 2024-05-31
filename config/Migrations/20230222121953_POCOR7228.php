<?php
use Migrations\AbstractMigration;

class POCOR7228 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        // Backup table
        $this->execute('CREATE TABLE `zz_7228_transfer_logs` LIKE `transfer_logs`');
        $this->execute('INSERT INTO `zz_7228_transfer_logs` SELECT * FROM `transfer_logs`');
        
        $this->execute("ALTER TABLE `transfer_logs` ADD COLUMN `features` varchar(200)  AFTER `id`");
        
    }
         
    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `transfer_logs`');
        $this->execute('RENAME TABLE `zz_7228_transfer_logs` TO `transfer_logs`');
    }
}
