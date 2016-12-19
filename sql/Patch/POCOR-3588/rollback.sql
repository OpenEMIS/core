-- examination_items
ALTER TABLE `examination_items`
DROP PRIMARY KEY,
MODIFY `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
ADD PRIMARY KEY (`examination_id`,`education_subject_id`),
MODIFY `education_subject_id` int(11) NOT NULL COMMENT 'links to education_subjects.id',
DROP COLUMN `name`,
DROP COLUMN `code`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3588';
