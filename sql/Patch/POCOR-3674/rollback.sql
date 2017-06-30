-- locales
DROP TABLE IF EXISTS `locales`;

-- locale_content_translations
DROP TABLE IF EXISTS `locale_content_translations`;

-- locale_contents
DROP TABLE IF EXISTS `locale_contents`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3674';
