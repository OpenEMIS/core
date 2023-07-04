<?php
use Migrations\AbstractMigration;

class POCOR7558 extends AbstractMigration
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
        // $this->execute('CREATE TABLE `zz_7558_alerts` LIKE `alerts`');
        // $this->execute('INSERT INTO `zz_7558_alerts` SELECT * FROM `alerts`');
        $this->execute("ALTER TABLE `alerts` ADD COLUMN frequency VARCHAR(255) NOT NULL AFTER process_id ");
    }

    // rollback
    public function down()
    {
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `alerts`');
        $this->execute('RENAME TABLE `zz_7558_alerts` TO `alerts`');
    }
}
