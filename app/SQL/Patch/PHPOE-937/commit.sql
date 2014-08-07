UPDATE `field_options` SET `order` = `order` + 1 WHERE `order` >= 12;

INSERT INTO `field_options` (`id`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(NULL, 'CensusGrid', 'Custom Grids', 'Institution Totals', '{"model":"CensusGrid"}', 12, 1, NULL, NULL, 1, '0000-00-00 00:00:00');

