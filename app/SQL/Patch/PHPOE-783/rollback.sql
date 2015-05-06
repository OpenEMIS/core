-- 
-- PHPOE-783 rollback.sql
-- 

DELETE FROM `contact_types` WHERE `contact_option_id`=(SELECT `id` FROM `contact_options` where `name`='Emergency');
DELETE FROM `contact_options` WHERE `contact_options`.`name` = 'Emergency';
UPDATE `contact_options` SET `contact_options`.`order`=`contact_options`.`order`-1 WHERE `contact_options`.`name` = 'Other';
