<?php
use Migrations\AbstractMigration;

class POCOR7165 extends AbstractMigration
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
        // Backup tables
        $this->execute('CREATE TABLE `zz_7165_institution_student_absences` LIKE `institution_student_absences`');
        $this->execute('INSERT INTO `zz_7165_institution_student_absences` SELECT * FROM `institution_student_absences`');

        $this->execute('CREATE TABLE `zz_7165_institution_student_absence_days` LIKE `institution_student_absence_days`');
        $this->execute('INSERT INTO `zz_7165_institution_student_absence_days` SELECT * FROM `institution_student_absence_days`');

        $this->execute('CREATE TABLE `zz_7165_institution_student_absence_details` LIKE `institution_student_absence_details`');
        $this->execute('INSERT INTO `zz_7165_institution_student_absence_details` SELECT * FROM `institution_student_absence_details`');

        // Set foreign key checks to disabled
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');

        // Remove records that have absence_type_id = 0
        $this->execute('DELETE FROM institution_student_absences WHERE institution_student_absences.absence_type_id = 0');
        $this->execute('DELETE FROM institution_student_absence_days WHERE institution_student_absence_days.absence_type_id = 0');
        $this->execute('DELETE FROM institution_student_absence_details WHERE institution_student_absence_details.absence_type_id = 0');



        $this->execute("ALTER TABLE institution_student_absence_details ADD CONSTRAINT `insti_stude_absen_detai_fk_abs_typ_id` FOREIGN KEY (`absence_type_id`) REFERENCES absence_types(`id`)");
        $this->execute("ALTER TABLE institution_student_absence_details ADD CONSTRAINT `insti_stude_absen_detai_fk_stude_absen_reaso_id` FOREIGN KEY (`student_absence_reason_id`) REFERENCES student_absence_reasons(`id`)");
        $this->execute('SET SESSION FOREIGN_KEY_CHECKS=1;');

    }

    // Rollback
    public function down()
    {

        $this->execute('DROP TABLE IF EXISTS `institution_student_absences`');
        $this->execute('RENAME TABLE `zz_7165_institution_student_absences` TO `institution_student_absences`');

        $this->execute('DROP TABLE IF EXISTS `institution_student_absence_days`');
        $this->execute('RENAME TABLE `zz_7165_institution_student_absence_days` TO `institution_student_absence_days`');

        $this->execute('DROP TABLE IF EXISTS `institution_student_absence_details`');
        $this->execute('RENAME TABLE `zz_7165_institution_student_absence_details` TO `institution_student_absence_details`');

    }
}
