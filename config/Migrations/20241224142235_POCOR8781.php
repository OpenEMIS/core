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
