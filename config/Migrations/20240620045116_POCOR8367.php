<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8367 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        //backup
        $this->execute('CREATE TABLE `z_8367_report_progress` LIKE `report_progress`');
        $this->execute('INSERT INTO `z_8367_report_progress` SELECT * FROM `report_progress`');

        //enable Execute checkbox for export and import data
        $this->execute("ALTER TABLE `report_progress` CHANGE COLUMN `name` `name` VARCHAR(700);");
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `report_progress`');
        $this->execute('RENAME TABLE `z_8367_report_progress` TO `report_progress`');
    }
}
