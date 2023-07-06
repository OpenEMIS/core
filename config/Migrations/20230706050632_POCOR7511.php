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
      
        $this->execute('RENAME TABLE `examination_items` TO `examination_subjects`');
        $this->execute('RENAME TABLE `examination_item_results` TO `examination_student_subject_results`');
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
        $this->execute('DROP TABLE IF EXISTS `examination_student_subjects`');
        $this->execute('RENAME TABLE  `examination_subjects` TO `examination_items`');
        $this->execute('RENAME TABLE  `examination_student_subject_results` TO `examination_item_results`');
      
    }
}
