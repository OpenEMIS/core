<?php
use Migrations\AbstractMigration;

class POCOR8043 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_8043_report_progress` LIKE `report_progress`');
        $this->execute('INSERT INTO `zz_8043_report_progress` SELECT * FROM `report_progress`');

        // Alter table
        $this->execute("ALTER TABLE `report_progress` CHANGE `name` `name` VARCHAR(300) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL;");
    }
         
    // rollback
    public function down()
    {
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `report_progress`');
        $this->execute('RENAME TABLE `zz_8043_report_progress` TO `report_progress`');

    }
}
