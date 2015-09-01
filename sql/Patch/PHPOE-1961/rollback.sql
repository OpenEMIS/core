SET @fieldOptionId := 0;
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'NetworkConnectivities';

DELETE FROM field_option_values WHERE field_option_id = @fieldOptionId;

DELETE FROM field_options WHERE code = 'NetworkConnectivities';

ALTER TABLE `institution_sites` DROP `network_connectivity_id`;

DELETE FROM labels WHERE field = 'network_connectivity_id';