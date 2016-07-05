-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3103', NOW());


-- code here
-- user_comment table
ALTER TABLE `user_comments` ADD `comment_type_id` int(11) NOT NULL AFTER `comment_date`;

-- field_options table
INSERT INTO `field_options` (`id`, `plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (101, 'FieldOption', 'CommentTypes', 'Comment Types', 'Others', '{"model":"FieldOption.CommentTypes"}', '60', '1', NULL, NULL, '1', NOW());