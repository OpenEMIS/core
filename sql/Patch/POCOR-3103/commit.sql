-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3103', NOW());


-- code here
ALTER TABLE `user_comments` ADD `comment_type_id` int(11) AFTER `comment_date`;