ALTER TABLE `institution_sites` DROP `institution_id`;

ALTER TABLE `config_items` DROP INDEX name_2;

RENAME TABLE institution_custom_fields TO z_1461_institution_custom_fields;
RENAME TABLE institution_custom_field_options TO z_1461_institution_custom_field_options;
RENAME TABLE institution_custom_values TO z_1461_institution_custom_values;
RENAME TABLE institution_custom_value_history TO z_1461_institution_custom_value_history;
