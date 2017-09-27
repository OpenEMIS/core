-- locales
DROP TABLE IF EXISTS `locales`;

-- locale_content_translations
DROP TABLE IF EXISTS `locale_content_translations`;

-- locale_contents
DROP TABLE IF EXISTS `locale_contents`;

-- translations_backup
DROP TABLE IF EXISTS `translations`;
DROP TABLE IF EXISTS `z_3674_translations`;
RENAME TABLE `z_3674_translations_backup` TO `translations`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3674';
