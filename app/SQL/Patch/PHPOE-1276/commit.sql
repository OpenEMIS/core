--
-- 1. data patch
--

TRUNCATE TABLE `institution_site_grades`;

INSERT INTO `institution_site_grades` (`status`, `education_grade_id`, `institution_site_programme_id`, `institution_site_id`, `created_user_id`)
SELECT 1, `EducationGrade`.`id`, `InstitutionSiteProgramme`.`id`, `InstitutionSiteProgramme`.`institution_site_id`, 1
FROM `institution_site_programmes` AS `InstitutionSiteProgramme`
JOIN `education_grades` AS `EducationGrade` ON `EducationGrade`.`education_programme_id` = `InstitutionSiteProgramme`.`education_programme_id`;
