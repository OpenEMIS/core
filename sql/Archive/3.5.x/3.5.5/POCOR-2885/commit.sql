-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-2885', NOW());

-- new table

CREATE TABLE IF NOT EXISTS `special_need_difficulties` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `editable` int(1) NOT NULL DEFAULT '1',
  `default` int(1) NOT NULL DEFAULT '0',
  `international_code` varchar(50) DEFAULT NULL,
  `national_code` varchar(50) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 COLLATE utf8mb4_unicode_ci;

INSERT INTO `special_need_difficulties` (`id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'None', 1, 1, 1, 1, '', '', NULL, NULL, 1, NOW()),
(2, 'Some', 2, 1, 1, 0, '', '', NULL, NULL, 1, NOW()),
(3, 'A Lot', 3, 1, 1, 0, '', '', NULL, NULL, 1, NOW()),
(4, 'Unable', 4, 1, 1, 0, '', '', NULL, NULL, 1, NOW());

--
ALTER TABLE `special_need_difficulties`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `special_need_difficulties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;


-- backup the old table

ALTER TABLE `user_special_needs` 
RENAME TO  `z_2885_user_special_needs` ;

-- create new table and apply the changess

CREATE TABLE IF NOT EXISTS `user_special_needs` (
  `id` int(11) NOT NULL,
  `special_need_date` date NOT NULL,
  `comment` text,
  `security_user_id` int(11) NOT NULL,
  `special_need_type_id` int(11) NOT NULL,
  `special_need_difficulty_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
)ENGINE=InnoDB COLLATE utf8mb4_unicode_ci;

ALTER TABLE `user_special_needs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `special_need_type_id` (`special_need_type_id`),
  ADD KEY `special_need_difficulty_id` (`special_need_difficulty_id`),
  ADD KEY `security_user_id` (`security_user_id`);

ALTER TABLE `user_special_needs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- reinsert from backup table with value changes

INSERT INTO user_special_needs
SELECT `z_2885_user_special_needs`.`id`, 
  `z_2885_user_special_needs`.`special_need_date`, 
  `z_2885_user_special_needs`.`comment`, 
  `z_2885_user_special_needs`.`security_user_id`, 
  `z_2885_user_special_needs`.`special_need_type_id`,
  1,  
  `z_2885_user_special_needs`.`modified_user_id`, 
  `z_2885_user_special_needs`.`modified`, 
  `z_2885_user_special_needs`.`created_user_id`, 
  `z_2885_user_special_needs`.`created` 
FROM `z_2885_user_special_needs`;

-- update the order column and insert new record with desired order

UPDATE field_options
SET `order` = `order`+1
WHERE `order` >= 49;

INSERT INTO `field_options` (`id`, `plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
VALUES (NULL, 'FieldOption', 'SpecialNeedDifficulties', 'Special Need Difficulties', 'Others', '{"model":"FieldOption.SpecialNeedDifficulties"}', '49', '1', NULL, NULL, '1', NOW());

-- add label
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `created_user_id`, `created`) VALUES
(uuid(), 'SpecialNeeds', 'special_need_difficulty_id', 'Special Needs', 'Difficulty', NULL, NULL, 1, 0, NOW());