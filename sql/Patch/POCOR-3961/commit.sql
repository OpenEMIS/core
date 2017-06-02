-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3961', NOW());

CREATE TABLE `z_3961_assessment_periods` LIKE `assessment_periods`;

INSERT INTO `z_3961_assessment_periods`
SELECT * FROM `assessment_periods`;

UPDATE `assessment_periods`
SET `academic_term` = 'Others'
WHERE `assessment_id` IN (
    SELECT `assessment_id` FROM (SELECT distinct(assessment_id) FROM assessment_periods
    group by assessment_id, academic_term
    HAVING count(*) > 1) as tmp)
    AND `academic_term` IS NULL;

UPDATE `security_functions` SET `_edit`='AssessmentPeriods.edit|AssessmentPeriods.editAcademicTerm' WHERE `id`=5058;
