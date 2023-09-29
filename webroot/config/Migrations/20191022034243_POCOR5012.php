<?php
use Cake\I18n\Date;
use Phinx\Migration\AbstractMigration;

class POCOR5012 extends AbstractMigration
{
    public function up()
    {
		
		// backup 
		
        $this->execute('CREATE TABLE `z_POCOR5012_institution_students` LIKE `institution_students`');
        $this->execute('INSERT INTO `z_POCOR5012_institution_students` SELECT * FROM `institution_students`');
		
		$academicPeriod = $this->fetchRow("select * from academic_periods where editable=1 AND visible > 0 AND parent_id > 0 AND current = 1");
		$currentAcademicPeriod = $academicPeriod['id'];
		
		$studentStatusUpdateRecords = $this->fetchAll("select * from student_status_updates where academic_period_id=$currentAcademicPeriod AND model = 'StudentStatusUpdates'");
		
		foreach($studentStatusUpdateRecords as $studentStatusUpdateRecord){			
			$this->execute("UPDATE institution_students
            SET `student_status_id` = '".$studentStatusUpdateRecord['status_id']."'
            WHERE `student_id` = '".$studentStatusUpdateRecord['security_user_id']."' AND `institution_id` = '".$studentStatusUpdateRecord['institution_id']."' AND `academic_period_id` = '".$studentStatusUpdateRecord['academic_period_id']."' AND `education_grade_id` = '".$studentStatusUpdateRecord['education_grade_id']."' AND student_status_id = 4");
		}
		
    }

    public function down()
    {
        $this->dropTable('institution_students');
        $this->execute('RENAME TABLE `z_POCOR5012_institution_students` TO `institution_students`');
    }

}
