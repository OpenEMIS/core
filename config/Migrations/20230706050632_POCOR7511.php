<?php
use Migrations\AbstractMigration;

class POCOR7511 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
      // commit
    public function up()
    {
        //examination_subjects
        $this->execute('RENAME TABLE `examination_items` TO `examination_subjects`');
        //examination_student_subject_results
        $this->execute('CREATE TABLE `zz_7511_examination_item_results` LIKE `examination_item_results`');
        $this->execute('INSERT INTO `zz_7511_examination_item_results` SELECT * FROM `examination_item_results`');
        $this->execute('RENAME TABLE `examination_item_results` TO `examination_student_subject_results`');
        $this->execute('ALTER TABLE `examination_student_subject_results` CHANGE `examination_item_id` `examination_subject_id` INT(11) NOT NULL COMMENT "links to `examination_subjects.id`"');
        //examination_centres_examinations_subjects
        $this->execute('CREATE TABLE `zz_7511_examination_centres_examinations_subjects` LIKE `examination_centres_examinations_subjects`');
        $this->execute('INSERT INTO `zz_7511_examination_centres_examinations_subjects` SELECT * FROM `examination_centres_examinations_subjects`');
        $this->execute('ALTER TABLE `examination_centres_examinations_subjects` CHANGE `examination_item_id` `examination_subject_id` INT(11) NOT NULL COMMENT "links to `examination_subjects.id`"');
        //examination_centres_examinations_subjects_students
        $this->execute('CREATE TABLE `zz_7511_examination_centres_examinations_subjects_students` LIKE `examination_centres_examinations_subjects_students`');
        $this->execute('INSERT INTO `zz_7511_examination_centres_examinations_subjects_students` SELECT * FROM `examination_centres_examinations_subjects_students`');
        $this->execute('ALTER TABLE `examination_centres_examinations_subjects_students` CHANGE `examination_item_id` `examination_subject_id` INT(11) NOT NULL COMMENT "links to `examination_subjects.id`"');
        //examination_student_subjects
        $this->execute('CREATE TABLE IF NOT EXISTS `examination_student_subjects` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `student_id` int(11) ,
            `examination_subject_id` int(11),
            PRIMARY KEY (`id`),
            FOREIGN KEY (`student_id`) REFERENCES `security_users` (`id`),
            FOREIGN KEY (`examination_subject_id`) REFERENCES `examination_subjects` (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
          ');    
    
    }

    // rollback
    public function down()
    {   
        //examination_student_subjects
        $this->execute('DROP TABLE IF EXISTS `examination_student_subjects`');
        //examination_centres_examinations_subjects_students
        $this->execute('DROP TABLE IF EXISTS `examination_centres_examinations_subjects_students`');
        $this->execute('RENAME TABLE  `zz_7511_examination_centres_examinations_subjects_students` TO `examination_centres_examinations_subjects_students`');
        //examination_centres_examinations_subjects
        $this->execute('DROP TABLE IF EXISTS `examination_centres_examinations_subjects`');
        $this->execute('RENAME TABLE  `zz_7511_examination_centres_examinations_subjects` TO `examination_centres_examinations_subjects`');
        //examination_student_subject_results
        $this->execute('DROP TABLE IF EXISTS `examination_student_subject_results`');
        $this->execute('RENAME TABLE  `zz_7511_examination_item_results` TO `examination_item_results`');
        //examination_subjects
        $this->execute('RENAME TABLE  `examination_subjects` TO `examination_items`');
      
        
    }
}
