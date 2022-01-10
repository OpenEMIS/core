<?php
use Migrations\AbstractMigration;

class POCOR6021 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_6021_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `zz_6021_locale_contents` SELECT * FROM `locale_contents`');

        //deleting record if already exist
        $this->execute("DELETE FROM locale_contents WHERE en = 'No Scheduled Class'");

        //inserting data
        $this->execute("INSERT INTO `locale_contents` (`en`, `created_user_id`, `created`) VALUES ('No Scheduled Class', '1', NOW())");

        //backup
        $this->execute('CREATE TABLE `zz_6021_student_attendance_marked_records` LIKE `student_attendance_marked_records`');
        $this->execute('INSERT INTO `zz_6021_student_attendance_marked_records` SELECT * FROM `student_attendance_marked_records`');

        //adding new column
        $this->execute('ALTER TABLE `student_attendance_marked_records` ADD `no_scheduled_class` TINYINT NOT NULL DEFAULT 0');
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `zz_6021_locale_contents` TO `locale_contents`');
        $this->execute('DROP TABLE IF EXISTS `student_attendance_marked_records`');
        $this->execute('RENAME TABLE `zz_6021_student_attendance_marked_records` TO `student_attendance_marked_records`');
    }
}
