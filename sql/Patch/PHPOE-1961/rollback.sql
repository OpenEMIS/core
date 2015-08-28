SET @fieldOptionId := 0;
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'InstitutionSiteNetworkConnectivities';

DELETE FROM field_option_values WHERE field_option_id = @fieldOptionId;

DELETE FROM field_options WHERE code = 'InstitutionSiteNetworkConnectivities';

ALTER TABLE `institution_sites` DROP `institution_site_network_connectivity_id;

delete from labels where field = 'institution_site_network_connectivity_id';