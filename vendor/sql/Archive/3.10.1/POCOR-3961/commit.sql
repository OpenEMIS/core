-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3961', NOW());

CREATE TABLE `z_3961_assessment_periods` LIKE `assessment_periods`;

INSERT INTO `z_3961_assessment_periods`
SELECT * FROM `assessment_periods`;

CREATE TABLE IF NOT EXISTS `z_3961_tmp` (
  `assessment_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `z_3961_tmp` ADD KEY `assessment_id` (`assessment_id`);

INSERT INTO z_3961_tmp
SELECT assessment_id
FROM (
    SELECT b.assessment_id, b.academic_term
    FROM assessment_periods b
    GROUP BY b.assessment_id, b.academic_term
) a
GROUP BY assessment_id
HAVING COUNT(1) > 1;

UPDATE `assessment_periods`
SET `academic_term` = 'Others'
WHERE `assessment_id` IN (
    SELECT assessment_id
    FROM z_3961_tmp
)
AND (`academic_term` IS NULL OR `academic_term` = '');

DROP TABLE z_3961_tmp;

UPDATE `security_functions` SET `_edit`='AssessmentPeriods.edit|AssessmentPeriods.editAcademicTerm' WHERE `id` = 5058;
