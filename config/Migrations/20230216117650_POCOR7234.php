<?php
use Migrations\AbstractMigration;
use Cake\ORM\TableRegistry;

class POCOR7234 extends AbstractMigration
{
    /**
     * Change Method.
     * add academic_periood_id column in institution_grades and update value
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        // Backup locale_contents table
        $this->execute('CREATE TABLE `z_7234_institution_grades` LIKE `institution_grades`');
        $this->execute('INSERT INTO `z_7234_institution_grades` SELECT * FROM `institution_grades`');
        
        $this->execute("ALTER TABLE `institution_grades` ADD `academic_period_id` INT(11) NOT NULL COMMENT 'links to academic_periods.id' AFTER `education_grade_id`");

        $this->execute("ALTER TABLE `institution_grades` MODIFY COLUMN `start_date` DATE NULL");
        $this->execute("ALTER TABLE `institution_grades` MODIFY COLUMN `start_year` INT(4) NULL");

        $grades = TableRegistry::get('institution_grades');
        $academicPeriod = TableRegistry::get('academic_periods');
        $institutionGrades = $grades->find()->toArray();

        foreach($institutionGrades as $value){
            $year = $value->start_year;
            $institutionGradeId = $value->id;
            $academicPeriodId = $academicPeriod->find()->where(['start_year'=>$year,$academicPeriod->aliasField('academic_period_level_id') => 1])->first()->id;
            $this->execute("UPDATE `institution_grades` SET `academic_period_id` = $academicPeriodId WHERE `id`= $institutionGradeId");

        }
        
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_grades`');
        $this->execute('RENAME TABLE `z_7234_institution_grades` TO `institution_grades`');
    }
}
