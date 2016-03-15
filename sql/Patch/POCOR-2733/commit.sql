-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2733', NOW());

-- Backup table
CREATE TABLE `z_2733_staff_leaves` LIKE  `staff_leaves`;
INSERT INTO `z_2733_staff_leaves` SELECT * FROM `staff_leaves` WHERE 1;

CREATE TABLE `z_2733_institution_surveys` LIKE  `institution_surveys`;
INSERT INTO `z_2733_institution_surveys` SELECT * FROM `institution_surveys` WHERE 1;

CREATE TABLE `z_2733_workflow_records` LIKE  `workflow_records`;
INSERT INTO `z_2733_workflow_records` SELECT * FROM `workflow_records` WHERE 1;

-- Staff Leaves
SELECT `WorkflowsFilters`.`workflow_id`
FROM `workflows_filters` AS `WorkflowsFilters`
INNER JOIN `workflows` AS `Workflows` ON `Workflows`.`id` = `WorkflowsFilters`.`workflow_id`
INNER JOIN `workflow_models` AS `WorkflowModels` ON `WorkflowModels`.`id` = `Workflows`.`workflow_model_id`
WHERE `WorkflowsFilters`.`filter_id` <> 0
AND `WorkflowModels`.`model` = 'Staff.Leaves'
GROUP BY `WorkflowsFilters`.`workflow_id`;

-- patch Institution Surveys
DROP PROCEDURE IF EXISTS patchInstitutionSurveys;
DELIMITER $$

CREATE PROCEDURE patchInstitutionSurveys()
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE workflowId, workflowModelId, openStepId INT(11);
  DECLARE surveys CURSOR FOR
    SELECT `WorkflowsFilters`.`workflow_id`, `WorkflowModels`.`id`
    FROM `workflows_filters` AS `WorkflowsFilters`
    INNER JOIN `workflows` AS `Workflows` ON `Workflows`.`id` = `WorkflowsFilters`.`workflow_id`
    INNER JOIN `workflow_models` AS `WorkflowModels` ON `WorkflowModels`.`id` = `Workflows`.`workflow_model_id`
    WHERE `WorkflowsFilters`.`filter_id` <> 0
    AND `WorkflowModels`.`model` = 'Institution.InstitutionSurveys'
    GROUP BY `WorkflowsFilters`.`workflow_id`;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN surveys;

  read_loop: LOOP
    FETCH surveys INTO workflowId, workflowModelId;
    IF done THEN
      LEAVE read_loop;
    END IF;

    SELECT `id` INTO openStepId FROM `workflow_steps` WHERE `workflow_id` = workflowId AND `stage` = 0;
    UPDATE `institution_surveys` SET `status_id` = openStepId WHERE `survey_form_id` IN (SELECT `filter_id` FROM `workflows_filters` WHERE `workflow_id` = workflowId);	
	UPDATE `workflow_records` SET `workflow_step_id` = openStepId WHERE `workflow_model_id` = workflowModelId AND `model_reference` IN (
		SELECT `id` FROM `institution_surveys` WHERE `survey_form_id` IN (SELECT `filter_id` FROM `workflows_filters` WHERE `workflow_id` = workflowId)
	);

  END LOOP read_loop;

  CLOSE surveys;
END
$$

DELIMITER ;

CALL patchInstitutionSurveys;

DROP PROCEDURE IF EXISTS patchInstitutionSurveys;

-- patch Staff Leaves
DROP PROCEDURE IF EXISTS patchStaffLeaves;
DELIMITER $$

CREATE PROCEDURE patchStaffLeaves()
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE workflowId, workflowModelId, openStepId INT(11);
  DECLARE leaves CURSOR FOR
    SELECT `WorkflowsFilters`.`workflow_id`, `WorkflowModels`.`id`
    FROM `workflows_filters` AS `WorkflowsFilters`
    INNER JOIN `workflows` AS `Workflows` ON `Workflows`.`id` = `WorkflowsFilters`.`workflow_id`
    INNER JOIN `workflow_models` AS `WorkflowModels` ON `WorkflowModels`.`id` = `Workflows`.`workflow_model_id`
    WHERE `WorkflowsFilters`.`filter_id` <> 0
    AND `WorkflowModels`.`model` = 'Staff.Leaves'
    GROUP BY `WorkflowsFilters`.`workflow_id`;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN leaves;

  read_loop: LOOP
    FETCH leaves INTO workflowId, workflowModelId;
    IF done THEN
      LEAVE read_loop;
    END IF;

    SELECT `id` INTO openStepId FROM `workflow_steps` WHERE `workflow_id` = workflowId AND `stage` = 0;
    UPDATE `staff_leaves` SET `status_id` = openStepId WHERE `staff_leave_type_id` IN (SELECT `filter_id` FROM `workflows_filters` WHERE `workflow_id` = workflowId); 
  UPDATE `workflow_records` SET `workflow_step_id` = openStepId WHERE `workflow_model_id` = workflowModelId AND `model_reference` IN (
    SELECT `id` FROM `staff_leaves` WHERE `staff_leave_type_id` IN (SELECT `filter_id` FROM `workflows_filters` WHERE `workflow_id` = workflowId)
  );

  END LOOP read_loop;

  CLOSE leaves;
END
$$

DELIMITER ;

CALL patchStaffLeaves;

DROP PROCEDURE IF EXISTS patchStaffLeaves;
