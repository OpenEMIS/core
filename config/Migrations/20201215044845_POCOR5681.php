<?php
use Migrations\AbstractMigration;

class POCOR5681 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_5681_education_systems` LIKE `education_systems`');//backup
        $this->execute('ALTER TABLE `education_systems` ADD `academic_period_id` INT NOT NULL AFTER `name`');
        
        $this->execute("ALTER TABLE `education_systems` CHANGE `academic_period_id` `academic_period_id` INT(11) NOT NULL COMMENT 'links to academic_periods.id'");

        $this->execute('UPDATE `education_systems` SET `academic_period_id` = 29');
    }


    public function down()
    {
     $this->execute('RENAME TABLE `z_5681_education_systems` TO `education_systems`');
     $this->execute('ALTER TABLE `education_systems` DROP COLUMN `academic_period_id`');
    }
}
