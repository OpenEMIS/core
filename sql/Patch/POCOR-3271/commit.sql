-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3271', NOW());

-- `institution_genders`
RENAME TABLE `institution_genders` TO `z_3271_institution_genders`;

DROP TABLE IF EXISTS `institution_genders`;
CREATE TABLE IF NOT EXISTS `institution_genders` (
  `id` int(11) NOT NULL,
  `name` varchar(10) NOT NULL,
  `code` varchar(10) NOT NULL,
  `order` int(11) NOT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='This table contains the types of institution gender used by institution';

ALTER TABLE `institution_genders`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `institution_genders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;

INSERT INTO `institution_genders` (`id`, `name`, `code`, `order`, `created_user_id`, `created`) VALUES
(1, 'Mixed', 'X', 1, 1, '2017-04-013 00:00:00'),
(2, 'Male', 'M', 2, 1, '2017-04-013 00:00:00'),
(3, 'Female', 'F', 3, 1, '2017-04-013 00:00:00');
