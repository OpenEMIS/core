-- institutions
ALTER TABLE `institutions`
 DROP `photo_name`,
 DROP `photo_content`;

-- report_cards
DROP TABLE IF EXISTS `report_cards`;

-- report_card_subjects
DROP TABLE IF EXISTS `report_card_subjects`;

-- institution_students_report_cards
DROP TABLE IF EXISTS `institution_students_report_cards`;

-- institution_students_report_cards_comments
DROP TABLE IF EXISTS `institution_students_report_cards_comments`;

-- report_card_comment_codes
DROP TABLE IF EXISTS `report_card_comment_codes`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3533';
