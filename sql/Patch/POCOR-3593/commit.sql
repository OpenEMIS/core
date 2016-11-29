
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3593', NOW());

-- security_users
ALTER TABLE `security_users`
ADD COLUMN `nationality_id` INT NULL AFTER `date_of_death`,
ADD COLUMN `identity_type_id` INT NULL AFTER `nationality_id`,
ADD COLUMN `external_reference` VARCHAR(50) NULL AFTER `identity_number`;

INSERT INTO `external_data_source_attributes` (`id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id`) VALUES (uuid(), 'OpenEMIS Identity', 'first_name_mapping', 'First Name Mapping', 'first_name', NOW(), '2');
INSERT INTO `external_data_source_attributes` (`id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id`) VALUES (uuid(), 'OpenEMIS Identity', 'middle_name_mapping', 'Middle Name Mapping', 'middle_name', NOW(), '2');
INSERT INTO `external_data_source_attributes` (`id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id`) VALUES (uuid(), 'OpenEMIS Identity', 'third_name_mapping', 'Third Name Mapping', 'third_name', NOW(), '2');
INSERT INTO `external_data_source_attributes` (`id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id`) VALUES (uuid(), 'OpenEMIS Identity', 'last_name_mapping', 'Last Name Mapping', 'last_name', NOW(), '2');
INSERT INTO `external_data_source_attributes` (`id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id`) VALUES (uuid(), 'OpenEMIS Identity', 'date_of_birth_mapping', 'Date of Birth Mapping', 'date_of_birth', NOW(), '2');
INSERT INTO `external_data_source_attributes` (`id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id`) VALUES (uuid(), 'OpenEMIS Identity', 'external_reference_mapping', 'External Reference Mapping', 'id', NOW(), '2');
INSERT INTO `external_data_source_attributes` (`id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id`) VALUES (uuid(), 'OpenEMIS Identity', 'gender_mapping', 'Gender Mapping', 'gender_name', NOW(), '2');
INSERT INTO `external_data_source_attributes` (`id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id`) VALUES (uuid(), 'OpenEMIS Identity', 'identity_type_mapping', 'Identity Type Mapping', 'identity_type_name', NOW(), '2');
INSERT INTO `external_data_source_attributes` (`id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id`) VALUES (uuid(), 'OpenEMIS Identity', 'identity_number_mapping', 'Identity Number Mapping', 'identity_number', NOW(), '2');
INSERT INTO `external_data_source_attributes` (`id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id`) VALUES (uuid(), 'OpenEMIS Identity', 'nationality_mapping', 'Nationality Mapping', 'nationality_name', NOW(), '2');
