-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-2828', NOW());

UPDATE `security_functions` SET `_add`='StaffUser.add|getUniqueOpenemisId' WHERE `id`='1044';
UPDATE `security_functions` SET `_add`='Staff.add|getInstitutionPositions' WHERE `id`='1016';
UPDATE `security_functions` SET `_view`='Directories.index|Directories.view', `_edit`='Directories.edit|Directories.pull', `_add`='Directories.add', `_delete`='Directories.remove' WHERE `id`='7000';

INSERT INTO `external_data_source_attributes` (`id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id`)
SELECT * FROM (SELECT '653f3552-d32e-11e6-9166-525400b263eb', 'OpenEMIS Identity', 'address_mapping' as field, 'address_mapping', 'address', NOW(), 1) as tmp
WHERE NOT EXISTS (
    SELECT `id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id` FROM `external_data_source_attributes` WHERE `external_data_source_type` = 'OpenEMIS Identity' AND `attribute_field` = 'address_mapping'
);

INSERT INTO `external_data_source_attributes` (`id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id`)
SELECT * FROM (SELECT '858682fb-d583-11e6-87d6-525400b263eb', 'OpenEMIS Identity', 'postal_mapping' as field, 'postal_mapping', 'postal_code', NOW(), 1) as tmp
WHERE NOT EXISTS (
    SELECT `id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id` FROM `external_data_source_attributes` WHERE `external_data_source_type` = 'OpenEMIS Identity' AND `attribute_field` = 'postal_mapping'
);

INSERT INTO `external_data_source_attributes` (`id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id`)
SELECT * FROM (SELECT '51528c40-d331-11e6-9166-525400b263eb', 'Custom', 'address_mapping' as field, 'address_mapping', '', NOW(), 1) as tmp
WHERE NOT EXISTS (
    SELECT `id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id` FROM `external_data_source_attributes` WHERE `external_data_source_type` = 'Custom' AND `attribute_field` = 'address_mapping'
);

INSERT INTO `external_data_source_attributes` (`id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id`)
SELECT * FROM (SELECT '615a0770-d584-11e6-87d6-525400b263eb', 'Custom', 'postal_mapping' as field, 'postal_mapping', '', NOW(), 1) as tmp
WHERE NOT EXISTS (
    SELECT `id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id` FROM `external_data_source_attributes` WHERE `external_data_source_type` = 'Custom' AND `attribute_field` = 'postal_mapping'
);
