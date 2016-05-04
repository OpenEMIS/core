-- db_patches
INSERT IGNORE INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-2885', NOW());

-- new table

CREATE TABLE IF NOT EXISTS `special_need_type_difficulties` (
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

INSERT INTO `special_need_type_difficulties` (`id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'None', 1, 1, 1, 1, '', '', NULL, NULL, 1, NOW()),
(2, 'Some', 2, 1, 1, 0, '', '', NULL, NULL, 1, NOW()),
(3, 'A Lot', 3, 1, 1, 0, '', '', NULL, NULL, 1, NOW()),
(4, 'Unable', 4, 1, 1, 0, '', '', NULL, NULL, 1, NOW());

--
ALTER TABLE `special_need_type_difficulties`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `special_need_type_difficulties`
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
  `special_need_type_difficulty_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
)ENGINE=InnoDB COLLATE utf8mb4_unicode_ci;

ALTER TABLE `user_special_needs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `special_need_type_id` (`special_need_type_id`),
  ADD KEY `special_need_type_difficulty_id` (`special_need_type_difficulty_id`),
  ADD KEY `security_user_id` (`security_user_id`);

ALTER TABLE `user_special_needs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- reinsert from backup table with value changes

INSERT IGNORE INTO user_special_needs
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

-- backup existing table

ALTER TABLE `field_options` 
RENAME TO  `z_2885_field_options` ;

-- create the new table with changes

CREATE TABLE IF NOT EXISTS `field_options` (
  `id` int(5) NOT NULL,
  `plugin` varchar(50) DEFAULT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(50) NOT NULL,
  `parent` varchar(50) DEFAULT NULL,
  `params` text,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB COLLATE utf8mb4_unicode_ci;

ALTER TABLE `field_options`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

ALTER TABLE `field_options`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT;

-- repopulate value from the backup table

INSERT IGNORE INTO field_options
SELECT `z_2885_field_options`.`id`, 
  `z_2885_field_options`.`plugin`, 
  `z_2885_field_options`.`code`, 
  `z_2885_field_options`.`name`, 
  `z_2885_field_options`.`parent`,
  `z_2885_field_options`.`params`,
  `z_2885_field_options`.`order`,
  `z_2885_field_options`.`visible`,
  `z_2885_field_options`.`modified_user_id`, 
  `z_2885_field_options`.`modified`, 
  `z_2885_field_options`.`created_user_id`, 
  `z_2885_field_options`.`created` 
FROM `z_2885_field_options`
WHERE `z_2885_field_options`.`order` <= 48;

-- insert new record in the middle
INSERT INTO `field_options` (`id`, `plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
VALUES (NULL, 'FieldOption', 'SpecialNeedTypeDifficulties', 'Special Need Type Difficulties', 'Others', '{"model":"FieldOption.SpecialNeedTypeDifficulties"}', '49', '1', NULL, NULL, '1', NOW());

-- need to rearrange the order
INSERT IGNORE INTO field_options
SELECT NULL, 
  `z_2885_field_options`.`plugin`, 
  `z_2885_field_options`.`code`, 
  `z_2885_field_options`.`name`, 
  `z_2885_field_options`.`parent`,
  `z_2885_field_options`.`params`,
  `z_2885_field_options`.`order` + 1,
  `z_2885_field_options`.`visible`,
  `z_2885_field_options`.`modified_user_id`, 
  `z_2885_field_options`.`modified`, 
  `z_2885_field_options`.`created_user_id`, 
  `z_2885_field_options`.`created` 
FROM `z_2885_field_options`
WHERE `z_2885_field_options`.`order` >= 49;

