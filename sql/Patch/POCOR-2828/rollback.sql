-- security_functions
UPDATE `security_functions` SET `_add`='StaffUser.add' WHERE `id`='1044';
UPDATE `security_functions` SET `_add`='Staff.add' WHERE `id`='1016';
UPDATE `security_functions` SET `_view`='index|view', `_edit`='edit', `_add`='add', `_delete`='remove' WHERE `id`='7000';

DELETE FROM `external_data_source_type` WHERE `attribute_field` = 'address_mapping';

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-2828';
