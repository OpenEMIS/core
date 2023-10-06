<?php
use Migrations\AbstractMigration;

class POCOR7303 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_7303_report_progress` LIKE `report_progress`');
        $this->execute('INSERT INTO `zz_7303_report_progress` SELECT * FROM `report_progress`');

        $this->execute("ALTER TABLE `report_progress` MODIFY `sql` LONGTEXT");
    }

    // rollback
    public function down()
    {
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `report_progress`');
        $this->execute('RENAME TABLE `zz_7303_report_progress` TO `report_progress`');
    }
}
