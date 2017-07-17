-- POCOR-3536
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3536', NOW());

-- report_progress
ALTER TABLE `report_progress`
 MODIFY COLUMN `name` varchar(200) COLLATE utf8_general_ci NOT NULL;


-- POCOR-4089
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-4089', NOW());

-- education_grades
CREATE TABLE `z_4089_education_grades` LIKE `education_grades`;
INSERT INTO `z_4089_education_grades` SELECT * FROM `education_grades`;

SET @index := 1;
SET @prev := '';

UPDATE `education_grades` edu_grades
INNER JOIN (
    SELECT
        g.`id`,
        @current := g.`code`,
        (@index := IF(@current = @prev, @index + 1, 1)) AS current_index,
        @prev := g.`code`
    FROM `education_grades` g
    INNER JOIN (
        SELECT `code`,
        COUNT(*) AS `count`
        FROM `education_grades`
        GROUP BY `code`
        HAVING `count` > 1
    ) c
    ON g.`code` = c.`code`
    ORDER BY g.`code`, g.`id`
) ind
ON ind.`id` = edu_grades.`id`
SET `code` = CONCAT(edu_grades.`code`, CONCAT('-', ind.`current_index`));


-- 3.10.8
UPDATE config_items SET value = '3.10.8' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
