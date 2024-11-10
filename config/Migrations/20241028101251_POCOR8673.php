<?php

use Migrations\AbstractMigration;

class POCOR8673 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * This method is reversible by the down() method.
     *
     * @return void
     */
    public function up()
    {
        // Backup the existing tables
        $this->execute('CREATE TABLE `z_8673_institution_students_gpa` LIKE `institution_students_gpa`');
        $this->execute('INSERT INTO `z_8673_institution_students_gpa` SELECT * FROM `institution_students_gpa`');
        
        $this->execute('CREATE TABLE `z_8673_gpa_grading_options` LIKE `gpa_grading_options`');
        $this->execute('INSERT INTO `z_8673_gpa_grading_options` SELECT * FROM `gpa_grading_options`');

        $this->execute('CREATE TABLE `zz_8673_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_8673_security_functions` SELECT * FROM `security_functions`');

        $this->execute('CREATE TABLE `zz_8673_education_grades_gpa` LIKE `education_grades_gpa`');
        $this->execute('INSERT INTO `zz_8673_education_grades_gpa` SELECT * FROM `education_grades_gpa`');
   
        // Adding foreign key constraints

        $this->execute("ALTER TABLE `gpa_grading_options`
            ADD CONSTRAINT `fk_gpa_grading_type_id`
            FOREIGN KEY (`gpa_grading_type_id`) REFERENCES `gpa_grading_types`(`id`)");

        // Add Foreign Key Constraints
        $this->execute("ALTER TABLE `institution_students_gpa`
            ADD CONSTRAINT `fk_student_id`
            FOREIGN KEY (`student_id`) REFERENCES `security_users`(`id`)");

        $this->execute("ALTER TABLE `institution_students_gpa`
            ADD CONSTRAINT `fk_institution_id`
            FOREIGN KEY (`institution_id`) REFERENCES `institutions`(`id`)");

        $this->execute("ALTER TABLE `institution_students_gpa`
            ADD CONSTRAINT `fk_academic_period_id`
            FOREIGN KEY (`academic_period_id`) REFERENCES `academic_periods`(`id`)");

        $this->execute("ALTER TABLE `institution_students_gpa`
            ADD CONSTRAINT `fk_education_grade_id`
            FOREIGN KEY (`education_grade_id`) REFERENCES `education_grades`(`id`)");

        // Add Comments to Columns

        $this->execute("ALTER TABLE `gpa_grading_options`
            MODIFY `gpa_grading_type_id` INT(11) COMMENT 'link to gpa_grading_type.id for grading type'");

        $this->execute("ALTER TABLE `institution_students_gpa`
            MODIFY `student_id` INT(11) COMMENT 'link to security_users.id for users'");

        $this->execute("ALTER TABLE `institution_students_gpa`
            MODIFY `institution_id` INT(11) COMMENT 'link to institutions.id for institution'");

        $this->execute("ALTER TABLE `institution_students_gpa`
            MODIFY `academic_period_id` INT(11) COMMENT 'link to academic_periods.id for academic period'");

        $this->execute("ALTER TABLE `institution_students_gpa`
            MODIFY `education_grade_id` INT(11) COMMENT 'link to education_grades.id for grade'");

        //  add permission in security function

        $createdAt = (new DateTime())->format('Y-m-d H:i:s');
        $order = $this->fetchRow("SELECT MAX(`order`) FROM `security_functions` WHERE `module` = 'Institutions' AND `category` = 'Report Cards'");
        $parent_id = $this->fetchRow("SELECT MAX(`parent_id`) FROM `security_functions` WHERE `module` = 'Institutions' AND `category` = 'Report Cards'");
        $parent_id = $parent_id[0] + 1;
        $order = $order[0] + 1;

        $record = [
            [
                'name' => 'GpaGenerate', 'controller' => 'Institutions', 'module' => 'Institutions', 'category' => 'Report Cards', 'parent_id' => $parent_id,'_view' => NULL, '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => 'ReportCardGpa.generate|ReportCardGpa.generateAll', 'order' => $order, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => $createdAt,
            ]
        ];
        $this->table('security_functions')->insert($record)->save();

        $parent_id = $parent_id + 1;
        $order = $order + 1;

        $record = [
            [
                'name' => 'CumulativeGpaGenerate', 'controller' => 'Institutions', 'module' => 'Institutions', 'category' => 'Report Cards', 'parent_id' => $parent_id,'_view' => NULL, '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => 'ReportCardCumulativeGpa.generate|ReportCardCumulativeGpa.generateAll', 
                'order' => $order, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => $createdAt,
            ]
        ];
        $this->table('security_functions')->insert($record)->save();

        $parent_id = $parent_id + 1;
        $order = $order + 1;

        $record = [
            [
                'name' => 'Gpa Generate All', 'controller' => 'Institutions', 'module' => 'Institutions', 'category' => 'Report Cards', 'parent_id' => $parent_id,'_view' => NULL, '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => 'ReportCardGpa.generate|ReportCardGpa.generateAll', 'order' => $order, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => $createdAt,
            ]
        ];
        $this->table('security_functions')->insert($record)->save();

        $parent_id = $parent_id + 1;
        $order = $order + 1;

        $record = [
            [
                'name' => 'Cumulative Gpa Generate All', 'controller' => 'Institutions', 'module' => 'Institutions', 'category' => 'Report Cards', 'parent_id' => $parent_id,'_view' => NULL, '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => 'ReportCardCumulativeGpa.generate|ReportCardCumulativeGpa.generateAll', 'order' => $order, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' =>$createdAt,
            ]
        ];
        $this->table('security_functions')->insert($record)->save();
        // add name column 
        $this->execute("ALTER TABLE `education_grades_gpa` ADD COLUMN `name` VARCHAR(100) NULL AFTER `id`");

        // Add the new column
        $this->execute("ALTER TABLE `education_grades_cumulative_gpa` DROP COLUMN `education_grade_gpa_id`");

        $this->execute("ALTER TABLE `education_grades_cumulative_gpa` ADD COLUMN `main_education_grade_id` INT(11) AFTER `id`");

         $this->execute("ALTER TABLE `institution_students_gpa` ADD COLUMN `education_grades_gpa_id` INT(11) AFTER `education_grade_id`");

         // Add the foreign key constraint

         $this->execute("ALTER TABLE `institution_students_gpa`
            ADD CONSTRAINT `fk_education_grades_gpa_id`
            FOREIGN KEY (`education_grades_gpa_id`) REFERENCES `education_grades_gpa`(`id`)");

         $this->execute("ALTER TABLE `education_grades_cumulative_gpa`
            ADD CONSTRAINT `fk_main_education_grade_id`
            FOREIGN KEY (`main_education_grade_id`) REFERENCES `education_grades`(`id`)");

         // add comment
         $this->execute("ALTER TABLE `education_grades_cumulative_gpa`
            MODIFY `main_education_grade_id` INT(11) COMMENT 'link to education_grade_id.id for grade id'");
         $this->execute("ALTER TABLE `institution_students_gpa`
            MODIFY `education_grades_gpa_id` INT(11) COMMENT 'link to education_grades_gpa.id'");

        

        // update permission
        $this->execute("UPDATE `security_functions` SET `_delete` = NULL, `_add` = NULL, `_edit` = NULL, `_execute` = 'ReportCardGpa.index|ReportCardGpa.view' WHERE `name` = 'Institution Student GPA'");

        $this->execute("UPDATE `security_functions` SET `_delete` = NULL, `_add` = NULL, `_edit` = NULL, `_execute` = 'ReportCardCumulativeGpa.index|ReportCardCumulativeGpa.view' WHERE `name` = 'Institution Student Cumulative GPA'");
                
    }


    /**
     * Down Method to revert the changes and restore the backup.
     *
     * @return void
     */
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_students_gpa`');
        $this->execute('RENAME TABLE `z_8673_institution_students_gpa` TO `institution_students_gpa`');

        $this->execute('DROP TABLE IF EXISTS `gpa_grading_options`');
        $this->execute('RENAME TABLE `z_8673_gpa_grading_options` TO `gpa_grading_options`');

        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_8673_security_functions` TO `security_functions`');

        $this->execute('DROP TABLE IF EXISTS `education_grades_gpa`');
        $this->execute('RENAME TABLE `zz_8673_education_grades_gpa` TO `education_grades_gpa`');

    }
}
