<?php
use Migrations\AbstractMigration;

class POCOR7130 extends AbstractMigration
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
        // Backup table not needed
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->execute("ALTER TABLE institution_student_absence_details DROP FOREIGN KEY `insti_stude_absen_detai_fk_abs_typ_id`");
        $this->execute("ALTER TABLE institution_student_absence_details DROP FOREIGN KEY `insti_stude_absen_detai_fk_stude_absen_reaso_id`");
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    
    }

    // rollback
    public function down()
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->execute("ALTER TABLE institution_student_absence_details ADD CONSTRAINT `insti_stude_absen_detai_fk_abs_typ_id` FOREIGN KEY (`absence_type_id`) REFERENCES absence_types(`id`)");
        $this->execute("ALTER TABLE institution_student_absence_details ADD CONSTRAINT `insti_stude_absen_detai_fk_stude_absen_reaso_id` FOREIGN KEY (`student_absence_reason_id`) REFERENCES student_absence_reasons(`id`)");
        $this->execute('SET SESSION FOREIGN_KEY_CHECKS=1;');

    }
}
?>
