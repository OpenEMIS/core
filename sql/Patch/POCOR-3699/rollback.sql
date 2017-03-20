--`qualification_titles`
DROP TABLE IF EXISTS `qualification_titles`;

--`qualification_specialisations`
RENAME TABLE `z_3699_qualification_specialisations` TO `qualification_specialisations`;

--`staff_qualifications`
DROP TABLE IF EXISTS `staff_qualifications`;

RENAME TABLE `z_3699_staff_qualifications` TO `staff_qualifications`;

--`staff_qualifications_subjects`
DROP TABLE IF EXISTS `staff_qualifications_subjects`;

--`qualification_specialisation_subjects`
RENAME TABLE `z_3699_qualification_specialisation_subjects` TO `qualification_specialisation_subjects`;

--`labels`
DELETE FROM `labels` WHERE `id` = '5c3ddc98-0aec-11e7-b9c5-525400b263eb';

--`system_patches`
DELETE FROM `system_patches` WHERE `issue`='POCOR-3699';
