<?php
declare(strict_types=1);

use Cake\ORM\TableRegistry;
use Phinx\Migration\AbstractMigration;
use Cake\Utility\Text;

class POCOR8434 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {   

        $this->execute('SET FOREIGN_KEY_CHECKS = 0;');
        //Backup `workflow_models` table 
        $this->execute('CREATE TABLE `z_8434_workflow_models` LIKE `workflow_models`');
        $this->execute('INSERT INTO `z_8434_workflow_models` SELECT * FROM `workflow_models`');

        //Backup `workflows` table 
        $this->execute('CREATE TABLE `z_8434_workflows` LIKE `workflows`');
        $this->execute("ALTER TABLE `z_8434_workflows` ADD CONSTRAINT z_workf_fk_workf_model_id FOREIGN KEY (workflow_model_id) REFERENCES workflow_models(id);");
        $this->execute('INSERT INTO `z_8434_workflows` SELECT * FROM `workflows`');
        
        //Backup `workflow_steps` table 
        $this->execute('CREATE TABLE `z_8434_workflow_steps` LIKE `workflow_steps`');
        $this->execute("ALTER TABLE `z_8434_workflow_steps` ADD CONSTRAINT z_workf_steps_fk_workf_id FOREIGN KEY (workflow_id) REFERENCES workflows(id);");
        $this->execute('INSERT INTO `z_8434_workflow_steps` SELECT * FROM `workflow_steps`');
        
        //Backup `workflow_actions` table 
        $this->execute('CREATE TABLE `z_8434_workflow_actions` LIKE `workflow_actions`');
        $this->execute("ALTER TABLE `z_8434_workflow_actions` ADD CONSTRAINT z_workf_actio_fk_workf_step_id FOREIGN KEY (workflow_step_id) REFERENCES workflow_steps(id);");
        $this->execute('INSERT INTO `z_8434_workflow_actions` SELECT * FROM `workflow_actions`');
        
        //Backup `institution_student_admission` table 
        $this->execute('CREATE TABLE `z_8434_institution_student_admission` LIKE `institution_student_admission`');
        $this->execute('INSERT INTO `z_8434_institution_student_admission` SELECT * FROM `institution_student_admission`');
        $this->execute("ALTER TABLE `z_8434_institution_student_admission`
            ADD CONSTRAINT z_insti_stude_admis_fk_stude_id FOREIGN KEY (student_id) REFERENCES security_users(id),
            ADD CONSTRAINT z_insti_stude_admis_fk_status_id FOREIGN KEY (status_id) REFERENCES workflow_steps(id),
            ADD CONSTRAINT z_insti_stude_admis_fk_ins_id FOREIGN KEY (institution_id) REFERENCES institutions(id),
            ADD CONSTRAINT z_insti_stude_admis_fk_aca_per_id FOREIGN KEY (academic_period_id) REFERENCES academic_periods(id),
            ADD CONSTRAINT z_insti_stude_admis_fk_edu_gra_id FOREIGN KEY (education_grade_id) REFERENCES education_grades(id),
            ADD CONSTRAINT z_insti_stude_admis_fk_ins_cla_id FOREIGN KEY (institution_class_id) REFERENCES institution_classes(id);");
        
        //Backup `custom_modules` table 
        $this->execute('CREATE TABLE `z_8434_custom_modules` LIKE `custom_modules`');
        $this->execute('INSERT INTO `z_8434_custom_modules` SELECT * FROM `custom_modules`');
                
        //create new record for `Student Enrolment` in `workflow_models` table 
        $WorkflowModelsTable = TableRegistry::get('Workflow.WorkflowModels');
        $WorkflowModelsRes = $WorkflowModelsTable->find()->order([$WorkflowModelsTable->aliasField('id')=> 'DESC'])->first();
        if(!empty($WorkflowModelsRes)){
            $id = $WorkflowModelsRes->id + 1;
            $this->execute("INSERT INTO `workflow_models` (`id`, `name`, `model`, `filter`, `is_school_based`, `created_user_id`, `created`) VALUES ($id, 'Institutions > Students > Student Enrolment', 'Institution.StudentEnrolment', NULL, '1', '1', NOW());");
        }
        
        $WorkflowModelsTable = TableRegistry::get('Workflow.WorkflowModels');
        $WorkflowModels = $WorkflowModelsTable->find()->select(['id' => $WorkflowModelsTable->aliasField('id')])->where([$WorkflowModelsTable->aliasField('name')=> 'Institutions > Students > Student Enrolment'])->first();
        if(!empty($WorkflowModels)){
            $WorkflowModelId = $WorkflowModels['id'];

            //New Workflow create for `Student Enrolment` in `workflows` table
            $this->execute("INSERT INTO `workflows` (`id`, `code`, `name`, `message`, `workflow_model_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'STUDENT-Enrolment-1001', 'Student Enrolment', NULL, $WorkflowModelId, NULL, NULL, '1', NOW());");
            
            //get workflow id of `Student Enrolment`
            $WorkflowsTable = TableRegistry::get('Workflow.Workflows');
            $Workflows = $WorkflowsTable->find()->select(['id' => $WorkflowsTable->aliasField('id')])->where([ $WorkflowsTable->aliasField('code')=> 'STUDENT-Enrolment-1001' ])->first();
            if(!empty($Workflows)){
                $WorkflowId = $Workflows['id'];

                //create new steps for `Student Enrolment` in `workflow_steps` table 
                //For `Open` status
                $this->execute("INSERT INTO `workflow_steps` (`id`, `name`, `category`, `is_editable`, `is_removable`, `is_system_defined`, `workflow_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Open', '1', '1', '1', '1', $WorkflowId, NULL, NULL, '1', NOW());");
                //For `Pending Approval` status
                $this->execute("INSERT INTO `workflow_steps` (`id`, `name`, `category`, `is_editable`, `is_removable`, `is_system_defined`, `workflow_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Pending Approval', '2', '0', '0', '1', $WorkflowId, NULL, NULL, '1', NOW());");
                //For `Approved` status
                $this->execute("INSERT INTO `workflow_steps` (`id`, `name`, `category`, `is_editable`, `is_removable`, `is_system_defined`, `workflow_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Approved', '3', '0', '0', '1', $WorkflowId, NULL, NULL, '1', NOW());");
                //For `Rejected` status
                $this->execute("INSERT INTO `workflow_steps` (`id`, `name`, `category`, `is_editable`, `is_removable`, `is_system_defined`, `workflow_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Rejected', '3', '0', '0', '1', $WorkflowId, NULL, NULL, '1', NOW());");
                //For `Pending Cancellation` status
                $this->execute("INSERT INTO `workflow_steps` (`id`, `name`, `category`, `is_editable`, `is_removable`, `is_system_defined`, `workflow_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Pending Cancellation', '2', '0', '0', '1', $WorkflowId, NULL, NULL, '1', NOW());");
                //For `Cancelled` status
                $this->execute("INSERT INTO `workflow_steps` (`id`, `name`, `category`, `is_editable`, `is_removable`, `is_system_defined`, `workflow_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Cancelled', '3', '0', '0', '1', $WorkflowId, NULL, NULL, '1', NOW());");
                

                //get workflow_step_ids of `Student Enrolment`
                $WorkflowStepsTable = TableRegistry::get('Workflow.WorkflowSteps');
                $WorkflowStepsData = $WorkflowStepsTable->find('list', ['keyField' => 'id', 'valueField' => 'name'])->where([ $WorkflowStepsTable->aliasField('workflow_id') => $WorkflowId ])->toArray();
                if(!empty($WorkflowStepsData)){
                    $statusMap = [
                        'Open' => 'OpenStatusId',
                        'Pending Approval' => 'PendingApprovalId',
                        'Approved' => 'ApprovedId',
                        'Rejected' => 'RejectedId',
                        'Pending Cancellation' => 'PendingCancellationId',
                        'Cancelled' => 'CancelledId'
                    ];
                    
                    foreach ($WorkflowStepsData as $id => $name) {
                        if (isset($statusMap[$name])) {
                            ${$statusMap[$name]} = $id;
                        }
                    }

                    //create new actions for `Student Enrolment` in `workflow_actions`
                    //For `Submit For Approval` status
                    $this->execute("INSERT INTO `workflow_actions` (`id`, `name`, `description`, `action`, `visible`, `comment_required`, `allow_by_assignee`, `event_key`, `workflow_step_id`, `next_workflow_step_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Submit For Approval', NULL, '0', '1', '0', '1', NULL, $OpenStatusId, $PendingApprovalId, NULL, NULL, '1', NOW());");
                    //For `Approve` status
                    $this->execute("INSERT INTO `workflow_actions` (`id`, `name`, `description`, `action`, `visible`, `comment_required`, `allow_by_assignee`, `event_key`, `workflow_step_id`, `next_workflow_step_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Approve', NULL, '0', '1', '0', '0', 'Workflow.onApprove', $PendingApprovalId, $ApprovedId, NULL, NULL, '1', NOW());");
                    //For `Reject` status
                    $this->execute("INSERT INTO `workflow_actions` (`id`, `name`, `description`, `action`, `visible`, `comment_required`, `allow_by_assignee`, `event_key`, `workflow_step_id`, `next_workflow_step_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Reject', NULL, '1', '1', '1', '0', NULL, $PendingApprovalId, $RejectedId, NULL, NULL, '1', NOW());");
                    //For `Submit For Cancellation` status
                    $this->execute("INSERT INTO `workflow_actions` (`id`, `name`, `description`, `action`, `visible`, `comment_required`, `allow_by_assignee`, `event_key`, `workflow_step_id`, `next_workflow_step_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Submit For Cancellation', NULL, NULL, '1', '1', '1', NULL, $ApprovedId, $PendingCancellationId, NULL, NULL, '1', NOW());");
                    //For `Approve` status
                    $this->execute("INSERT INTO `workflow_actions` (`id`, `name`, `description`, `action`, `visible`, `comment_required`, `allow_by_assignee`, `event_key`, `workflow_step_id`, `next_workflow_step_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Approve', NULL, '0', '1', '0', '0', 'Workflow.onCancel', $PendingCancellationId, $CancelledId, NULL, NULL, '1', NOW());");
                    //For `Approve` status
                    $this->execute("INSERT INTO `workflow_actions` (`id`, `name`, `description`, `action`, `visible`, `comment_required`, `allow_by_assignee`, `event_key`, `workflow_step_id`, `next_workflow_step_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Reject', NULL, '1', '1', '1', '0', NULL, $PendingCancellationId, $RejectedId, NULL, NULL, '1', NOW());");

                }
            }
        }

        //create new record for `Student > Registrations` in `custom_modules` table   
        $this->execute("INSERT INTO `custom_modules` (`id`, `code`, `name`, `model`, `visible`, `parent_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Student > Registrations', 'Student > Registrations', 'Institution.StudentAdmission', '1', '0', NULL, NULL, '1', NOW());");
        //Rename `institution_student_admission` INTO `institution_student_enrolment` table
        $this->execute("RENAME TABLE `institution_student_admission` TO `institution_student_enrolment`");
        //Drop FOREIGN KEY CONSTRAINT from `institution_student_enrolment` table
        $this->execute('SET FOREIGN_KEY_CHECKS = 0;');
        $this->execute("ALTER TABLE `institution_student_enrolment` DROP FOREIGN KEY `insti_stude_admis_fk_aca_per_id`;");
        $this->execute("ALTER TABLE `institution_student_enrolment` DROP FOREIGN KEY `insti_stude_admis_fk_ass_id`;");
        $this->execute("ALTER TABLE `institution_student_enrolment` DROP FOREIGN KEY `insti_stude_admis_fk_edu_gra_id`;");
        $this->execute("ALTER TABLE `institution_student_enrolment` DROP FOREIGN KEY `insti_stude_admis_fk_ins_cla_id`;");
        $this->execute("ALTER TABLE `institution_student_enrolment` DROP FOREIGN KEY `insti_stude_admis_fk_ins_id`;");
        $this->execute("ALTER TABLE `institution_student_enrolment` DROP FOREIGN KEY `insti_stude_admis_fk_statu_id`;");
        $this->execute("ALTER TABLE `institution_student_enrolment` DROP FOREIGN KEY `insti_stude_admis_fk_stude_id`;");
        //Not able to add FOREIGN KEY CONSTRAINT in `institution_student_enrolment` table because in this table in assignee_id column have 0 and -1 value 
        //ADD CONSTRAINT insti_stude_enrol_fk_ass_id FOREIGN KEY (assignee_id) REFERENCES security_users(id),
        //ADD CONSTRAINT insti_stude_enrol_fk_ins_cla_id FOREIGN KEY (institution_class_id) REFERENCES institution_classes(id) in `institution_student_admission` table some records have null values in institution_class_id column
        $this->execute("ALTER TABLE `institution_student_enrolment`
            ADD CONSTRAINT insti_stude_enrol_fk_stude_id FOREIGN KEY (student_id) REFERENCES security_users(id),
            ADD CONSTRAINT insti_stude_enrol_fk_status_id FOREIGN KEY (status_id) REFERENCES workflow_steps(id),
            ADD CONSTRAINT insti_stude_enrol_fk_ins_id FOREIGN KEY (institution_id) REFERENCES institutions(id),
            ADD CONSTRAINT insti_stude_enrol_fk_aca_per_id FOREIGN KEY (academic_period_id) REFERENCES academic_periods(id),
            ADD CONSTRAINT insti_stude_enrol_fk_edu_gra_id FOREIGN KEY (education_grade_id) REFERENCES education_grades(id);");
        
        //create new table `institution_student_admission`    
        $this->execute("CREATE TABLE IF NOT EXISTS `institution_student_admission` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT,
                        `start_date` DATE NOT NULL,
                        `end_date` DATE NOT NULL,
                        `student_id` INT(11) NOT NULL COMMENT 'links to security_users.id',
                        `status_id` INT(11) NOT NULL COMMENT 'links to workflow_steps.id',
                        `assignee_id` INT(11) NOT NULL COMMENT 'links to security_users.id',
                        `institution_id` INT(11) NOT NULL COMMENT 'links to institutions.id',
                        `academic_period_id` INT(11) NOT NULL COMMENT 'links to academic_periods.id',
                        `education_grade_id` INT(11) NOT NULL COMMENT 'links to education_grades.id',
                        `institution_class_id` INT(11) DEFAULT NULL COMMENT 'links to institution_classes.id',
                        `test_score` INT(11) DEFAULT NULL,
                        `interview_score` INT(11) DEFAULT NULL,
                        `comment` TEXT COLLATE utf8mb4_unicode_ci  DEFAULT NULL,
                        `modified_user_id` INT(11) DEFAULT NULL COMMENT 'links to security_users.id',
                        `modified` DATETIME DEFAULT NULL,
                        `created_user_id` INT(11) NOT NULL COMMENT 'links to security_users.id',
                        `created` DATETIME NOT NULL,
                        PRIMARY KEY (`id`),
                        KEY `student_id` (`student_id`),
                        KEY `status_id` (`status_id`),
                        KEY `assignee_id` (`assignee_id`),
                        KEY `institution_id` (`institution_id`),
                        KEY `academic_period_id` (`academic_period_id`),
                        KEY `education_grade_id` (`education_grade_id`),
                        KEY `institution_class_id` (`institution_class_id`),
                        KEY `modified_user_id` (`modified_user_id`),
                        KEY `created_user_id` (`created_user_id`),
                        CONSTRAINT `insti_stude_admis_fk_stude_id` FOREIGN KEY (`student_id`) REFERENCES `security_users`(`id`),
                        CONSTRAINT `insti_stude_admis_fk_statu_id` FOREIGN KEY (`status_id`) REFERENCES `workflow_steps`(`id`),
                        CONSTRAINT `insti_stude_admis_fk_ass_id` FOREIGN KEY (`assignee_id`) REFERENCES `security_users`(`id`),
                        CONSTRAINT `insti_stude_admis_fk_ins_id` FOREIGN KEY (`institution_id`) REFERENCES `institutions`(`id`),
                        CONSTRAINT `insti_stude_admis_fk_aca_per_id` FOREIGN KEY (`academic_period_id`) REFERENCES `academic_periods`(`id`),
                        CONSTRAINT `insti_stude_admis_fk_edu_gra_id` FOREIGN KEY (`education_grade_id`) REFERENCES `education_grades`(`id`),
                        CONSTRAINT `insti_stude_admis_fk_ins_cla_id` FOREIGN KEY (`institution_class_id`) REFERENCES `institution_classes`(`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        
        //create new table `student_admission_custom_field_values`    
        $this->execute("CREATE TABLE IF NOT EXISTS `student_admission_custom_field_values` (
                        `id` char(36) NOT NULL,
                        `text_value` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                        `number_value` int(11) DEFAULT NULL,
                        `decimal_value` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                        `textarea_value` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                        `date_value` date DEFAULT NULL,
                        `time_value` time DEFAULT NULL,
                        `file` longblob DEFAULT NULL,
                        `student_custom_field_id` int(11) NOT NULL COMMENT 'links to student_custom_fields.id',
                        `institution_student_admission_id` int(11) NOT NULL COMMENT 'link to institution_student_admission',
                        `modified_user_id` int(11) DEFAULT NULL COMMENT 'link to security_users.id',
                        `modified` datetime DEFAULT NULL,
                        `created_user_id` int(11) NOT NULL COMMENT 'link to security_users.id',
                        `created` datetime NOT NULL,
                        PRIMARY KEY (`id`),
                        KEY `number_value` (`number_value`),
                        KEY `student_custom_field_id` (`student_custom_field_id`),
                        KEY `modified_user_id` (`modified_user_id`),
                        KEY `created_user_id` (`created_user_id`),
                        CONSTRAINT `student_admission_custom_field_values_ibfk_1` FOREIGN KEY (`student_custom_field_id`) REFERENCES `student_custom_fields` (`id`),
                        CONSTRAINT `student_admission_custom_field_values_ibfk_2` FOREIGN KEY (`institution_student_admission_id`) REFERENCES `institution_student_admission` (`id`),
                        CONSTRAINT `student_admission_custom_field_values_ibfk_3` FOREIGN KEY (`modified_user_id`) REFERENCES `security_users` (`id`),
                        CONSTRAINT `student_admission_custom_field_values_ibfk_4` FOREIGN KEY (`created_user_id`) REFERENCES `security_users` (`id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        
        //create new table `student_custom_filters`  
        $this->execute("CREATE TABLE IF NOT EXISTS `student_custom_filters` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(250) COLLATE utf8_general_ci NOT NULL,
            `custom_module_id` INT(11) NOT NULL COMMENT 'Links to custom_modules.id',
            `student_custom_form_id` INT(11) NOT NULL COMMENT 'Link to student_custom_forms.id',
            `education_programme_id` INT(11) NOT NULL COMMENT 'Links to education_programmes.id',
            `academic_period_id` INT(11) NOT NULL COMMENT 'Links to academic_periods.id',
            `modified_user_id` INT(11) NULL DEFAULT NULL,
            `modified` DATETIME NULL DEFAULT NULL,
            `created_user_id` INT(11) NOT NULL,
            `created` DATETIME NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");
    }

    public function down() {

        $this->execute('SET FOREIGN_KEY_CHECKS = 0;');
        ////////////////// workflow_actions /////////////////////
        //Drop `workflow_actions` table
        $this->execute('DROP TABLE IF EXISTS `workflow_actions`');
        //Drop FOREIGN KEY CONSTRAINT from `z_8434_workflow_actions` table
        $this->execute("ALTER TABLE `z_8434_workflow_actions` DROP FOREIGN KEY z_workf_actio_fk_workf_step_id;");
        //Restore workflow_actions table 
        $this->execute('RENAME TABLE `z_8434_workflow_actions` TO `workflow_actions`');
        //ADD FOREIGN KEY CONSTRAINT in `workflow_actions` table
        $this->execute("ALTER TABLE `workflow_actions` ADD CONSTRAINT workf_actio_fk_workf_step_id FOREIGN KEY (workflow_step_id) REFERENCES workflow_steps(id);");
        
        ////////////////// workflow_steps /////////////////////
        //DROP workflow_steps table 
        $this->execute('DROP TABLE IF EXISTS `workflow_steps`');
        //DROP FOREIGN KEY CONSTRAINT from `z_8434_workflow_steps` table
        $this->execute("ALTER TABLE `z_8434_workflow_steps` DROP FOREIGN KEY z_workf_steps_fk_workf_id;");
        //Restore workflow_steps table 
        $this->execute('RENAME TABLE `z_8434_workflow_steps` TO `workflow_steps`');
        //ADD FOREIGN KEY CONSTRAINT in `workflow_steps` table
        $this->execute("ALTER TABLE `workflow_steps` ADD CONSTRAINT workf_steps_fk_workf_id FOREIGN KEY (workflow_id) REFERENCES workflows(id);");
        
        ////////////////// workflows /////////////////////
        //DROP workflows table   
        $this->execute('DROP TABLE IF EXISTS `workflows`');
        //DROP FOREIGN KEY CONSTRAINT from `z_8434_workflows` table
        $this->execute("ALTER TABLE `z_8434_workflows` DROP FOREIGN KEY z_workf_fk_workf_model_id;");
        //Restore workflows table 
        $this->execute('RENAME TABLE `z_8434_workflows` TO `workflows`');
        //Add FOREIGN KEY CONSTRAINT in `workflows` table
        $this->execute("ALTER TABLE `workflows` ADD CONSTRAINT workf_fk_workf_model_id FOREIGN KEY (workflow_model_id) REFERENCES workflow_models(id);");
        
        //Restore workflow_models table 
        $this->execute('DROP TABLE IF EXISTS `workflow_models`');
        $this->execute('RENAME TABLE `z_8434_workflow_models` TO `workflow_models`');

        //Drop institution_student_enrolment table 
        $this->execute('DROP TABLE IF EXISTS `institution_student_enrolment`');
        
        //DROP institution_student_admission table 
        $this->execute('DROP TABLE IF EXISTS `institution_student_admission`');
        //Drop FOREIGN KEY CONSTRAINT from `z_8434_institution_student_admission` table
        $this->execute("ALTER TABLE `z_8434_institution_student_admission` DROP FOREIGN KEY `z_insti_stude_admis_fk_aca_per_id`;");
        $this->execute("ALTER TABLE `z_8434_institution_student_admission` DROP FOREIGN KEY `z_insti_stude_admis_fk_edu_gra_id`;");
        $this->execute("ALTER TABLE `z_8434_institution_student_admission` DROP FOREIGN KEY `z_insti_stude_admis_fk_ins_cla_id`;");
        $this->execute("ALTER TABLE `z_8434_institution_student_admission` DROP FOREIGN KEY `z_insti_stude_admis_fk_ins_id`;");
        $this->execute("ALTER TABLE `z_8434_institution_student_admission` DROP FOREIGN KEY `z_insti_stude_admis_fk_status_id`;");
        $this->execute("ALTER TABLE `z_8434_institution_student_admission` DROP FOREIGN KEY `z_insti_stude_admis_fk_stude_id`;");
        //Restore `institution_student_admission` table 
        $this->execute('RENAME TABLE `z_8434_institution_student_admission` TO `institution_student_admission`');
        //Add FOREIGN KEY CONSTRAINT in `institution_student_admission`
        //ADD CONSTRAINT insti_stude_admis_fk_ins_cla_id FOREIGN KEY (institution_class_id) REFERENCES institution_classes(id) in `institution_student_admission` table some records have null values in institution_class_id column
        //ADD CONSTRAINT insti_stude_admis_fk_ass_id FOREIGN KEY (assignee_id) REFERENCES security_users(id),
        $this->execute("ALTER TABLE `institution_student_admission`
            ADD CONSTRAINT `insti_stude_admis_fk_stude_id` FOREIGN KEY (`student_id`) REFERENCES `security_users`(`id`),
            ADD CONSTRAINT `insti_stude_admis_fk_statu_id` FOREIGN KEY (`status_id`) REFERENCES `workflow_steps`(`id`),
            ADD CONSTRAINT `insti_stude_admis_fk_ins_id` FOREIGN KEY (`institution_id`) REFERENCES `institutions`(`id`),
            ADD CONSTRAINT `insti_stude_admis_fk_aca_per_id` FOREIGN KEY (`academic_period_id`) REFERENCES `academic_periods`(`id`),
            ADD CONSTRAINT `insti_stude_admis_fk_edu_gra_id` FOREIGN KEY (`education_grade_id`) REFERENCES `education_grades`(`id`);");

        //Restore custom_modules table 
        $this->execute('DROP TABLE IF EXISTS `custom_modules`');
        $this->execute('RENAME TABLE `z_8434_custom_modules` TO `custom_modules`');        
        //Drop student_admission_custom_field_values table 
        $this->execute('DROP TABLE IF EXISTS `student_admission_custom_field_values`');
        $this->execute('DROP TABLE IF EXISTS `student_custom_filters`');
        $this->execute('SET foreign_key_checks = 1;');
    }
}
