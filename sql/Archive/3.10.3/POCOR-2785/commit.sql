-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-2785', NOW());

-- translations
INSERT INTO `translations` (`en`, `editable`, `created_user_id`, `created`) VALUES ('%tree_no_of_item items selected', 1, 1, NOW());
