<?php

use Migrations\AbstractMigration;

class POCOR5063 extends AbstractMigration
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
		// backup 
        $this->execute('CREATE TABLE `z_5063_institution_students` LIKE `institution_students`');
		$this->execute('CREATE TABLE `z_5063_institution_student_withdraw` LIKE `institution_student_withdraw`');
		
        // start
        $sql = 'SELECT ist.* FROM institution_students ist
				LEFT JOIN institution_student_withdraw isw
				ON ist.student_id = isw.student_id
				AND ist.academic_period_id = isw.academic_period_id
				AND ist.institution_id = isw.institution_id
				AND ist.education_grade_id = isw.education_grade_id
				WHERE student_status_id = 4
				AND isw.id IS NULL';
				
		$widthDrawStudents = $this->fetchAll($sql);
		
		foreach ($widthDrawStudents as $row) {
			$udateQuery = 'UPDATE `institution_students` SET `student_status_id` = 1 WHERE `student_status_id` = ' . $row['student_status_id'].' AND `student_id` = '.$row['student_id'].' AND `academic_period_id` = '.$row['academic_period_id']. ' AND `institution_id` = '.$row['institution_id'].' AND education_grade_id = '.$row['education_grade_id'];
            $this->execute($udateQuery);			
        }
		
        // end 
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_students`');
        $this->execute('RENAME TABLE `z_5063_institution_students` TO `institution_students`');
		$this->execute('DROP TABLE IF EXISTS `institution_student_withdraw`');
        $this->execute('RENAME TABLE `z_5063_institution_student_withdraw` TO `institution_student_withdraw`');
    }
}
