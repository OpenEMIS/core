-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3271', NOW());

-- `institution_genders`
RENAME TABLE `institution_genders` TO `z_3271_institution_genders`;

DROP TABLE IF EXISTS `institution_genders`;
CREATE TABLE IF NOT EXISTS `institution_genders` (
  `id` int(11) NOT NULL,
  `name` varchar(10) NOT NULL,
  `code` varchar(10) NOT NULL,
  `order` int(3) NOT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='This table contains the types of institution gender used by institution';

ALTER TABLE `institution_genders`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `institution_genders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;

INSERT INTO `institution_genders` (`id`, `name`, `code`, `order`, `created_user_id`, `created`) VALUES
(1, 'Mixed', 'X', 1, 1, '2017-04-13 00:00:00'),
(2, 'Male', 'M', 2, 1, '2017-04-13 00:00:00'),
(3, 'Female', 'F', 3, 1, '2017-04-13 00:00:00');

UPDATE z_3271_institution_genders SET name = 'Female' WHERE name = 'Girls';
UPDATE z_3271_institution_genders SET name = 'Male' WHERE name = 'Boys';

UPDATE z_3271_institution_genders
JOIN institution_genders ON institution_genders.name = z_3271_institution_genders.name
SET `national_code` = institution_genders.id;

UPDATE institutions
JOIN z_3271_institution_genders
    ON z_3271_institution_genders.id = institutions.institution_gender_id
SET institutions.institution_gender_id = z_3271_institution_genders.`national_code`;

UPDATE institutions
SET institution_gender_id = 1
WHERE NOT EXISTS (
    SELECT 1 FROM institution_genders WHERE id = institution_gender_id
);
