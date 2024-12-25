<?php
use Migrations\AbstractMigration;

class POCOR8781 extends AbstractMigration
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
        $this->execute('START TRANSACTION;');
        try {
            // Backup locale_contents table
            $this->execute('CREATE TABLE `z_8781_institution_staff` LIKE `institution_staff`');
            $this->execute('INSERT INTO `z_8781_institution_staff` SELECT * FROM `institution_staff`');

            // Delete duplicates
            $this->execute('
            DELETE t1
            FROM institution_staff t1
            JOIN institution_staff t2
                ON t1.staff_id = t2.staff_id
                AND t1.FTE = t2.FTE
                AND t1.start_date = t2.start_date
                AND t1.start_year = t2.start_year
                AND t1.institution_id = t2.institution_id
                AND t1.staff_type_id = t2.staff_type_id
                AND t1.staff_status_id = t2.staff_status_id
                AND t1.institution_position_id = t2.institution_position_id
                AND t1.is_homeroom = t2.is_homeroom
                AND t1.staff_position_grade_id = t2.staff_position_grade_id
                AND t1.security_group_user_id = t2.security_group_user_id
                AND t1.id > t2.id;');

            $this->execute('ALTER TABLE institution_staff
    ADD UNIQUE KEY unique_staff (
                                 staff_id, FTE, start_date, start_year, institution_id,
                                 staff_type_id, staff_status_id, institution_position_id,
                                 is_homeroom, staff_position_grade_id,
                                 security_group_user_id
        );');

            $this->execute('COMMIT;');
        } catch (\Exception $e) {
            $this->execute('ROLLBACK;');
            throw $e;
        }
    }


    // rollback
    public function down()
    {
        $this->execute('START TRANSACTION;');
        try {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('DROP TABLE IF EXISTS `institution_staff`');
            $this->execute('RENAME TABLE `z_8781_institution_staff` TO `institution_staff`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
            $this->execute('COMMIT;');
        } catch (\Exception $e) {
            $this->execute('ROLLBACK;');
            throw $e;
        }
    }
}
