--
-- PHPOE-1762 commit.sql
--

ALTER TABLE `institution_site_sections` CHANGE `section_number` `section_number` INT(11) NULL DEFAULT NULL COMMENT 'This column is being used to determine whether this section is a multi-grade or single-grade.';