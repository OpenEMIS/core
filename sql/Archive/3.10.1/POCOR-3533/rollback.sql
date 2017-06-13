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

-- security_functions
DELETE FROM `security_functions` WHERE `id` IN (1057, 1058, 1059, 1060, 2034, 5072, 7053);

UPDATE `security_functions`
SET `order` = `order` - 4
WHERE `order` >= 1054 AND `order` <= 1060;

UPDATE `security_functions`
SET `order` = `order` - 1
WHERE `order` >= 2019 AND `order` <= 2032;

UPDATE `security_functions`
SET `order` = `order` - 1
WHERE `order` >= 5068 AND `order` <= 5072;

UPDATE `security_functions`
SET `order` = `order` - 1
WHERE `order` >= 7018 AND `order` <= 7052;

-- labels
DELETE FROM `labels` WHERE `id` = '1ef9db3d-3f7f-11e7-9c23-525400b263eb';

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3533';
