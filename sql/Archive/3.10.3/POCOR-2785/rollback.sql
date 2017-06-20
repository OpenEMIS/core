-- translations
DELETE FROM `translations` WHERE `en` = '%tree_no_of_item items selected';

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-2785';
