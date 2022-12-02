<?php
use Migrations\AbstractMigration;

class POCOR7080 extends AbstractMigration
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
        // Set FOREIGN_KEY_CHECKS to 0 to avoid errors caused by inconsisted records already present in the table (if any exist)
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');

        // Setup foreing keys for remaining tables
        $this->execute("ALTER TABLE institution_student_admission ADD CONSTRAINT `insti_stude_admis_fk_ins_id` FOREIGN KEY (`institution_id`) REFERENCES institutions(`id`)");
        $this->execute("ALTER TABLE institution_student_transfers ADD CONSTRAINT `insti_stude_trans_fk_ins_id` FOREIGN KEY (`institution_id`) REFERENCES institutions(`id`)");
        $this->execute("ALTER TABLE institution_student_admission ADD CONSTRAINT `insti_stude_admis_fk_aca_per_id` FOREIGN KEY (`academic_period_id`) REFERENCES academic_periods(`id`)");
        $this->execute("ALTER TABLE institution_student_transfers ADD CONSTRAINT `insti_stude_trans_fk_aca_per_id` FOREIGN KEY (`academic_period_id`) REFERENCES academic_periods(`id`)");
        $this->execute("ALTER TABLE institution_staff_transfers ADD CONSTRAINT `insti_staff_trans_fk_ass_id` FOREIGN KEY (`assignee_id`) REFERENCES security_users(`id`)");
        $this->execute("ALTER TABLE institution_student_admission ADD CONSTRAINT `insti_stude_admis_fk_ass_id` FOREIGN KEY (`assignee_id`) REFERENCES security_users(`id`)");
        $this->execute("ALTER TABLE institution_student_transfers ADD CONSTRAINT `insti_stude_trans_fk_ass_id` FOREIGN KEY (`assignee_id`) REFERENCES security_users(`id`)");
        $this->execute("ALTER TABLE institution_student_admission ADD CONSTRAINT `insti_stude_admis_fk_edu_gra_id` FOREIGN KEY (`education_grade_id`) REFERENCES education_grades(`id`)");
        $this->execute("ALTER TABLE institution_student_transfers ADD CONSTRAINT `insti_stude_trans_fk_edu_gra_id` FOREIGN KEY (`education_grade_id`) REFERENCES education_grades(`id`)");
        $this->execute("ALTER TABLE institution_student_admission ADD CONSTRAINT `insti_stude_admis_fk_ins_cla_id` FOREIGN KEY (`institution_class_id`) REFERENCES institution_classes(`id`)");
        $this->execute("ALTER TABLE institution_student_transfers ADD CONSTRAINT `insti_stude_trans_fk_ins_cla_id` FOREIGN KEY (`institution_class_id`) REFERENCES institution_classes(`id`)");
        $this->execute("ALTER TABLE institution_staff_transfers ADD CONSTRAINT `insti_staff_trans_fk_new_insti_id` FOREIGN KEY (`new_institution_id`) REFERENCES institutions(`id`)");
        $this->execute("ALTER TABLE institution_staff_transfers ADD CONSTRAINT `insti_staff_trans_fk_new_insti_posit_id` FOREIGN KEY (`new_institution_position_id`) REFERENCES institution_positions(`id`)");
        $this->execute("ALTER TABLE institution_staff_transfers ADD CONSTRAINT `insti_staff_trans_fk_new_staff_type_id` FOREIGN KEY (`new_staff_type_id`) REFERENCES staff_types(`id`)");
        $this->execute("ALTER TABLE institution_student_transfers ADD CONSTRAINT `insti_stude_trans_fk_previ_acade_perio_id` FOREIGN KEY (`previous_academic_period_id`) REFERENCES academic_periods(`id`)");
        $this->execute("ALTER TABLE institution_student_transfers ADD CONSTRAINT `insti_stude_trans_fk_previ_educa_grade_id` FOREIGN KEY (`previous_education_grade_id`) REFERENCES education_grades(`id`)");
        $this->execute("ALTER TABLE institution_staff_transfers ADD CONSTRAINT `insti_staff_trans_fk_previ_insti_id` FOREIGN KEY (`previous_institution_id`) REFERENCES institutions(`id`)");
        $this->execute("ALTER TABLE institution_student_transfers ADD CONSTRAINT `insti_stude_trans_fk_previ_insti_id` FOREIGN KEY (`previous_institution_id`) REFERENCES institutions(`id`)");
        $this->execute("ALTER TABLE institution_staff_transfers ADD CONSTRAINT `insti_staff_trans_fk_previ_insti_staff_id` FOREIGN KEY (`previous_institution_staff_id`) REFERENCES institution_staff(`id`)");
        $this->execute("ALTER TABLE institution_staff_transfers ADD CONSTRAINT `insti_staff_trans_fk_previ_staff_type_id` FOREIGN KEY (`previous_staff_type_id`) REFERENCES staff_types(`id`)");
        $this->execute("ALTER TABLE institution_staff_transfers ADD CONSTRAINT `insti_staff_trans_fk_staff_id` FOREIGN KEY (`staff_id`) REFERENCES security_users(`id`)");
        $this->execute("ALTER TABLE institution_staff_transfers ADD CONSTRAINT `insti_staff_trans_fk_statu_id` FOREIGN KEY (`status_id`) REFERENCES workflow_steps(`id`)");
        $this->execute("ALTER TABLE institution_student_admission ADD CONSTRAINT `insti_stude_admis_fk_statu_id` FOREIGN KEY (`status_id`) REFERENCES workflow_steps(`id`)");
        $this->execute("ALTER TABLE institution_student_transfers ADD CONSTRAINT `insti_stude_trans_fk_statu_id` FOREIGN KEY (`status_id`) REFERENCES workflow_steps(`id`)");
        $this->execute("ALTER TABLE institution_student_admission ADD CONSTRAINT `insti_stude_admis_fk_stude_id` FOREIGN KEY (`student_id`) REFERENCES security_users(`id`)");
        $this->execute("ALTER TABLE institution_student_transfers ADD CONSTRAINT `insti_stude_trans_fk_stude_id` FOREIGN KEY (`student_id`) REFERENCES security_users(`id`)");
        $this->execute("ALTER TABLE institution_student_transfers ADD CONSTRAINT `insti_stude_trans_fk_stude_trans_reaso_id` FOREIGN KEY (`student_transfer_reason_id`) REFERENCES student_transfer_reasons(`id`)");
        
        // Set FOREIGN_KEY_CHECKS back to 1
        $this->execute('SET SESSION FOREIGN_KEY_CHECKS=1;');
    
    }

    // rollback
    public function down()
    {
        // Set FOREIGN_KEY_CHECKS to 0 to avoid errors caused by inconsisted records already present in the table (if any exist)
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');

        // Setup foreing keys for remaining tables
        $this->execute("ALTER TABLE institution_student_admission DROP FOREIGN KEY `insti_stude_admis_fk_ins_id`");
        $this->execute("ALTER TABLE institution_student_transfers DROP FOREIGN KEY `insti_stude_trans_fk_ins_id`");
        $this->execute("ALTER TABLE institution_student_admission DROP FOREIGN KEY `insti_stude_admis_fk_aca_per_id`");
        $this->execute("ALTER TABLE institution_student_transfers DROP FOREIGN KEY `insti_stude_trans_fk_aca_per_id`");
        $this->execute("ALTER TABLE institution_staff_transfers DROP FOREIGN KEY `insti_staff_trans_fk_ass_id`");
        $this->execute("ALTER TABLE institution_student_admission DROP FOREIGN KEY `insti_stude_admis_fk_ass_id`");
        $this->execute("ALTER TABLE institution_student_transfers DROP FOREIGN KEY `insti_stude_trans_fk_ass_id`");
        $this->execute("ALTER TABLE institution_student_admission DROP FOREIGN KEY `insti_stude_admis_fk_edu_gra_id`");
        $this->execute("ALTER TABLE institution_student_transfers DROP FOREIGN KEY `insti_stude_trans_fk_edu_gra_id`");
        $this->execute("ALTER TABLE institution_student_admission DROP FOREIGN KEY `insti_stude_admis_fk_ins_cla_id`");
        $this->execute("ALTER TABLE institution_student_transfers DROP FOREIGN KEY `insti_stude_trans_fk_ins_cla_id`");
        $this->execute("ALTER TABLE institution_staff_transfers DROP FOREIGN KEY `insti_staff_trans_fk_new_insti_id`");
        $this->execute("ALTER TABLE institution_staff_transfers DROP FOREIGN KEY `insti_staff_trans_fk_new_insti_posit_id`");
        $this->execute("ALTER TABLE institution_staff_transfers DROP FOREIGN KEY `insti_staff_trans_fk_new_staff_type_id`");
        $this->execute("ALTER TABLE institution_student_transfers DROP FOREIGN KEY `insti_stude_trans_fk_previ_acade_perio_id`");
        $this->execute("ALTER TABLE institution_student_transfers DROP FOREIGN KEY `insti_stude_trans_fk_previ_educa_grade_id`");
        $this->execute("ALTER TABLE institution_staff_transfers DROP FOREIGN KEY `insti_staff_trans_fk_previ_insti_id`");
        $this->execute("ALTER TABLE institution_student_transfers DROP FOREIGN KEY `insti_stude_trans_fk_previ_insti_id`");
        $this->execute("ALTER TABLE institution_staff_transfers DROP FOREIGN KEY `insti_staff_trans_fk_previ_insti_staff_id`");
        $this->execute("ALTER TABLE institution_staff_transfers DROP FOREIGN KEY `insti_staff_trans_fk_previ_staff_type_id`");
        $this->execute("ALTER TABLE institution_staff_transfers DROP FOREIGN KEY `insti_staff_trans_fk_staff_id`");
        $this->execute("ALTER TABLE institution_staff_transfers DROP FOREIGN KEY `insti_staff_trans_fk_statu_id`");
        $this->execute("ALTER TABLE institution_student_admission DROP FOREIGN KEY `insti_stude_admis_fk_statu_id`");
        $this->execute("ALTER TABLE institution_student_transfers DROP FOREIGN KEY `insti_stude_trans_fk_statu_id`");
        $this->execute("ALTER TABLE institution_student_admission DROP FOREIGN KEY `insti_stude_admis_fk_stude_id`");
        $this->execute("ALTER TABLE institution_student_transfers DROP FOREIGN KEY `insti_stude_trans_fk_stude_id`");
        $this->execute("ALTER TABLE institution_student_transfers DROP FOREIGN KEY `insti_stude_trans_fk_stude_trans_reaso_id`");

        // Set FOREIGN_KEY_CHECKS back to 1
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }
}