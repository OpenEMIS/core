<?php

use Phinx\Migration\AbstractMigration;

class POCOR4387 extends AbstractMigration
{
    public function up()
    {
        // workflow_transitions
        $this->execute('CREATE TABLE `z_4387_workflow_transitions` LIKE `workflow_transitions`');
        $this->execute('INSERT INTO `z_4387_workflow_transitions` SELECT * FROM `workflow_transitions`');

        // model list
        $workflowModelsList = [
            1 => 'institution_staff_leave',
            2 => 'institution_surveys',
            3 => 'training_courses',
            4 => 'training_sessions',
            5 => 'training_session_results',
            6 => 'staff_training_needs',
            7 => 'institution_positions',
            8 => 'institution_staff_position_profiles',
            9 => 'institution_visit_requests',
            10 => 'staff_training_applications',
            11 => 'staff_licenses',
            12 => 'institution_cases',
            15 => 'institution_student_withdraw',
            16 => 'institution_student_admission',
            19 => 'institution_staff_appraisals'
        ];

        foreach ($workflowModelsList as $modelId => $modelTable) {
            $this->execute('
                INSERT INTO `workflow_transitions` (
                    prev_workflow_step_name, 
                    workflow_step_name, 
                    workflow_action_name, 
                    workflow_model_id, 
                    model_reference, 
                    created_user_id, 
                    created
                )
                SELECT 
                    "New", 
                    "Open", 
                    "Administration - Record Created", 
                    ' . $modelId . ', 
                    `' . $modelTable . '`.`id`, 
                    `' . $modelTable . '`.`created_user_id`, 
                    `' . $modelTable . '`.`created` 
                FROM `' . $modelTable . '`
            ');
        }

        // transfer in/out list
        $workflowModelsTransferList = [
            'institution_staff_transfers',
            'institution_student_transfers'
        ];

        foreach ($workflowModelsTransferList as $modelTable) {
            $this->execute('
                INSERT INTO `workflow_transitions` (
                    prev_workflow_step_name, 
                    workflow_step_name, 
                    workflow_action_name, 
                    workflow_model_id, 
                    model_reference, 
                    created_user_id, 
                    created
                )
                SELECT 
                    "New", 
                    "Open", 
                    "Administration - Record Created", 
                    `workflows`.`workflow_model_id`, 
                    `' . $modelTable . '`.`id`, 
                    `' . $modelTable . '`.`created_user_id`, 
                    `' . $modelTable . '`.`created`
                FROM `' . $modelTable .'`
                INNER JOIN `workflow_steps`
                ON `' . $modelTable . '`.`status_id` = `workflow_steps`.`id`
                INNER JOIN `workflows`
                ON `workflow_steps`.`workflow_id` = `workflows`.`id`
            ');
        }

        // translations for workflow_transition workflow_action_name
        $data = [
            [
                'en' => 'Administration - Reject Record',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Administration - Close Record',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Administration - Approve Record',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Administration - Change Assignee',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Administration - Record Created',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];

        $this->insert('locale_contents', $data);
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `workflow_transitions`');
        $this->execute('RENAME TABLE `z_4387_workflow_transitions` TO `workflow_transitions`');

        // translations for workflow_transition workflow_action_name
        $this->execute('DELETE FROM `locale_contents` WHERE `en` = "Administration - Reject Record"');
        $this->execute('DELETE FROM `locale_contents` WHERE `en` = "Administration - Close Record"');
        $this->execute('DELETE FROM `locale_contents` WHERE `en` = "Administration - Approve Record"');
        $this->execute('DELETE FROM `locale_contents` WHERE `en` = "Administration - Change Assignee"');
        $this->execute('DELETE FROM `locale_contents` WHERE `en` = "Administration - Record Created"');

    }
}
