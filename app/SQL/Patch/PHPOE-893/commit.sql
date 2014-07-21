INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(NULL, 'Visualizer', 'Visualizer', 'Visualizer', -1, 'index|indicator|unit|dimension|area|time|source|visualization|review', NULL, NULL, NULL, 'genCSV', 0, 1, NULL, NULL, 1, '0000-00-00 00:00:00');

INSERT INTO `navigations` (`id`, `module`, `plugin`, `controller`, `header`, `title`, `action`, `pattern`, `attributes`, `parent`, `is_wizard`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(NULL, 'Visualizer', 'Visualizer', 'Visualizer', NULL, 'Visualizer', 'index', 'index|indicator|unit|dimension|area|time|source|visualization|review', NULL, -1, 0, 0, 1, NULL, NULL, 1, '2014-04-01 00:00:00');

ALTER TABLE `ut_area_en` ADD `id` INT( 11 ) NULL COMMENT 'For Tree Behaviour' AFTER `Area_NId` ,
ADD INDEX ( `id` ) ;
UPDATE `ut_area_en` SET `id`=`Area_NId`;