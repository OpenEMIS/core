<?php
use Migrations\AbstractMigration;

class POCOR7292 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_7292_staff_position_titles_grades` LIKE `staff_position_titles_grades`');
        $this->execute('INSERT INTO `z_7292_staff_position_titles_grades` SELECT * FROM `staff_position_titles_grades`');
        // Backup table not needed
        $this->execute('SET FOREIGN_KEY_CHECKS = 0;');
        // update the delete to 'delete' instead of remove
        $this->execute('ALTER TABLE `staff_position_titles_grades` DROP FOREIGN KEY staff_posit_title_grade_fk_staff_posit_grade_id');
        // Backup table not needed
        $this->execute('SET foreign_key_checks = 1;');
        
    }

    // rollback
    public function down()
    {
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `staff_position_titles_grades`');
        $this->execute('RENAME TABLE `zz_7292_staff_position_titles_grades` TO `staff_position_titles_grades`');
    }
}
