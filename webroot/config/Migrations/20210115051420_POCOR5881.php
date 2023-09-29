<?php
use Migrations\AbstractMigration;

class POCOR5881 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_5881_student_guardians` LIKE `student_guardians`');
        $this->execute('INSERT INTO `zz_5881_student_guardians` SELECT * FROM `student_guardians`');
        // End
        //add indexes in columns
        $this->execute('ALTER TABLE `student_guardians` ADD INDEX `created_user_id` (`created_user_id`)');
        $this->execute('ALTER TABLE `student_guardians` ADD INDEX `modified_user_id` (`modified_user_id`)');

    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `student_guardians`');
        $this->execute('RENAME TABLE `zz_5881_student_guardians` TO `student_guardians`');
    }
}
