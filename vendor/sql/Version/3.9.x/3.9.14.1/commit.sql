-- POCOR-3961
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


-- POCOR-4013
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-4013', NOW());

-- security_functions
UPDATE `security_functions` SET `_execute` = 'Assessments.excel|resultsExport' WHERE `id` = 1015;


-- 3.9.14.1
UPDATE config_items SET value = '3.9.14.1' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
