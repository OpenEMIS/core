<?php
use Migrations\AbstractMigration;
use Cake\ORM\TableRegistry;

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
        //backup
        $this->execute('CREATE TABLE `z_5681_education_systems` LIKE `education_systems`');
        $this->execute('INSERT INTO `z_5681_education_systems` SELECT * FROM `education_systems`');

        //insert new column
        $this->execute('ALTER TABLE `education_systems` ADD `academic_period_id` INT NOT NULL AFTER `name`');
        
        $this->execute("ALTER TABLE `education_systems` CHANGE `academic_period_id` `academic_period_id` INT(11) NOT NULL COMMENT 'links to academic_periods.id'");

        //updating academic_period_id column value
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $academicPeriodId = $AcademicPeriods
                    ->find()
                    ->where([
                        $AcademicPeriods->aliasField('current') => 1
                    ])
                  ->first();
        
        $this->execute('UPDATE `education_systems` SET `academic_period_id` = '.$academicPeriodId['id']);
    }


    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `education_systems`');
        $this->execute('RENAME TABLE `z_5681_education_systems` TO `education_systems`');
    }
}
