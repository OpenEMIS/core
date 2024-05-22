<?php
use Migrations\AbstractMigration;

class POCOR7650 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_7650_scholarships` LIKE `scholarships`');
        $this->execute('INSERT INTO `z_7650_scholarships` SELECT * FROM `scholarships`');
        $this->execute('ALTER TABLE `scholarships` ADD `bonded_organisation` VARCHAR(255) NULL AFTER `duration`');
    }

    // rollback
    public function down()
    {
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `scholarships`');
        $this->execute('RENAME TABLE `zz_7650_scholarships` TO `scholarships`');
    }
}
