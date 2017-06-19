DROP TABLE `system_authentications`;

DROP TABLE `authentication_types`;

DROP TABLE `authentication_type_attributes`;

DROP TABLE `system_authentication_type_attributes`;

ALTER TABLE `z_3931_authentication_type_attributes`
RENAME TO  `authentication_type_attributes` ;

DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3931';
