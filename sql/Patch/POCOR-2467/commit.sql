DROP TABLE IF EXISTS `guardian_relations`;
CREATE TABLE `guardian_relations` LIKE `institution_network_connectivities`;
INSERT INTO `guardian_relations`
SELECT
    `fov`.`id` as `id`,
    `fov`.`name` as `name`,
    `fov`.`order` as `order`,
    `fov`.`visible` as `visible`,
    `fov`.`editable` as `editable`,
    `fov`.`default` as `default`,
    `fov`.`international_code` as `international_code`,
    `fov`.`national_code` as `national_code`,
    `fov`.`modified_user_id` as `modified_user_id`,
    `fov`.`modified` as `modified`,
    `fov`.`created_user_id` as `created_user_id`,
    `fov`.`created` as `created`
FROM `field_option_values` as `fov`
WHERE `fov`.`field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'GuardianRelations');

UPDATE `field_option_values` set `visible`=0 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'GuardianRelations');

UPDATE `field_options` SET `plugin` = 'Student' WHERE `code` = 'GuardianRelations';