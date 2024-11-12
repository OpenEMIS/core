<?php
use Phinx\Migration\AbstractMigration;
class POCOR8615 extends AbstractMigration
{
    public function up()
    {
        // Backup the existing table
        $this->execute('CREATE TABLE `z_8615_appraisal_criterias` LIKE `appraisal_criterias`');
        $this->execute('INSERT INTO `z_8615_appraisal_criterias` SELECT * FROM `appraisal_criterias`');
        // Change the name column from varchar(250) to varchar(500)
        $this->execute('ALTER TABLE `appraisal_criterias` CHANGE `name` `name` VARCHAR(500) COLLATE utf8mb4_unicode_ci NOT NULL');
    }
    // Rollback
    public function down()
    {
        // Restore the original table
        $this->execute('DROP TABLE IF EXISTS `appraisal_criterias`');
        $this->execute('RENAME TABLE `z_8615_appraisal_criterias` TO `appraisal_criterias`');
    }
}