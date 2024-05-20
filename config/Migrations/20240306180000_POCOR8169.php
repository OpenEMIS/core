<?php
use Migrations\AbstractMigration;

class POCOR8169 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_8169_institution_students` LIKE `institution_students`');
        $this->execute('INSERT INTO `zz_8169_institution_students` SELECT * FROM `institution_students`');

        /*Remove duplicates*/
        //Create temp table specifically for duplicate records
        $this->execute("CREATE TABLE institution_students_temp_v1 LIKE institution_students;");
        $this->execute("ALTER TABLE institution_students_temp_v1 MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;");
        $this->execute("INSERT INTO institution_students_temp_v1(`student_status_id`, `student_id`, `education_grade_id`, `academic_period_id`, `start_date`, `start_year`, `end_date`, `end_year`, `institution_id`, `previous_institution_student_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT institution_students.student_status_id ,institution_students.student_id ,institution_students.education_grade_id ,institution_students.academic_period_id ,institution_students.start_date ,institution_students.start_year ,institution_students.end_date ,institution_students.end_year ,institution_students.institution_id ,institution_students.previous_institution_student_id ,institution_students.modified_user_id ,institution_students.modified ,institution_students.created_user_id ,institution_students.created FROM institution_students WHERE CONCAT(institution_students.student_status_id, institution_students.academic_period_id, institution_students.institution_id, institution_students.education_grade_id, institution_students.student_id, institution_students.start_date, institution_students.end_date) IN ( SELECT CONCAT(institution_students.student_status_id, institution_students.academic_period_id, institution_students.institution_id, institution_students.education_grade_id, institution_students.student_id, institution_students.start_date, institution_students.end_date) FROM institution_students GROUP BY institution_students.academic_period_id ,institution_students.institution_id ,institution_students.education_grade_id ,institution_students.student_id ,institution_students.student_status_id ,institution_students.start_date ,institution_students.end_date HAVING COUNT(*) > 1);");
        
        //DELETE all records enrolment records of the users who have dplicates from institution_students
        $this->execute("DELETE institution_students FROM institution_students WHERE CONCAT(institution_students.student_status_id, institution_students.academic_period_id, institution_students.institution_id, institution_students.education_grade_id, institution_students.student_id, institution_students.start_date, institution_students.end_date) IN( SELECT * FROM ( SELECT CONCAT(institution_students.student_status_id, institution_students.academic_period_id, institution_students.institution_id, institution_students.education_grade_id, institution_students.student_id, institution_students.start_date, institution_students.end_date) FROM institution_students GROUP BY institution_students.academic_period_id ,institution_students.institution_id ,institution_students.education_grade_id ,institution_students.student_id ,institution_students.student_status_id ,institution_students.start_date ,institution_students.end_date HAVING COUNT(*) > 1) subq );");

        //Clean data in the temp table
        $this->execute("DELETE t1 FROM institution_students_temp_v1 t1 INNER JOIN institution_students_temp_v1 t2 ON t1.student_status_id = t2.student_status_id AND t1.academic_period_id = t2.academic_period_id AND t1.institution_id = t2.institution_id AND t1.education_grade_id = t2.education_grade_id AND t1.student_id = t2.student_id AND t1.start_date = t2.start_date AND t1.end_date = t2.end_date AND t1.id < t2.id; ");

        //INSERT clean data from temp table back to institution_students
        $this->execute("INSERT INTO `institution_students`(`id`, `student_status_id`, `student_id`, `education_grade_id`, `academic_period_id`, `start_date`, `start_year`, `end_date`, `end_year`, `institution_id`, `previous_institution_student_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT uuid() ,institution_students_temp_v1.student_status_id ,institution_students_temp_v1.student_id ,institution_students_temp_v1.education_grade_id ,institution_students_temp_v1.academic_period_id ,institution_students_temp_v1.start_date ,institution_students_temp_v1.start_year ,institution_students_temp_v1.end_date ,institution_students_temp_v1.end_year ,institution_students_temp_v1.institution_id ,institution_students_temp_v1.previous_institution_student_id ,institution_students_temp_v1.modified_user_id ,institution_students_temp_v1.modified ,institution_students_temp_v1.created_user_id ,institution_students_temp_v1.created FROM institution_students_temp_v1;");

        //DROP temp table
        $this->execute("DROP TABLE institution_students_temp_v1;");

        // ALTER TABLE
       /* $this->execute("ALTER TABLE `institution_students` DROP PRIMARY KEY, ADD PRIMARY KEY( `student_status_id`, `student_id`, `education_grade_id`, `academic_period_id`, `start_date`, `end_date`, `institution_id` )");*/

       $this->execute("ALTER TABLE `institution_students` ADD UNIQUE KEY `unique_institution_students` (`student_status_id`, `student_id`, `education_grade_id`, `academic_period_id`, `start_date`, `end_date`, `institution_id`)");
       // Drop existing primary key 
        $this->execute("ALTER TABLE `institution_students` DROP PRIMARY KEY");

        // Add new primary key with auto-increment column
        $this->execute("ALTER TABLE `institution_students` ADD PRIMARY KEY (`id`)");


    }
         
    // rollback
    public function down()
    {
        
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `institution_students`');
        $this->execute('RENAME TABLE `zz_8169_institution_students` TO `institution_students`');

    }
}