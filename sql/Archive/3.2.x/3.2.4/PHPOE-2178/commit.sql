-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2178', NOW());

-- backup institution site data
CREATE TABLE `z_2178_Institution_sites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date_opened` date NOT NULL,
  `year_opened` int(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=208 DEFAULT CHARSET=utf8;

INSERT INTO `z_2178_Institution_sites` (`id`, `date_opened`, `year_opened`)
SELECT `id`, `date_opened`, `year_opened`
FROM `institution_sites`;

-- institution_sites
UPDATE `institution_sites`
SET `institution_sites`.`date_opened` = 
	(
		SELECT MIN(`academic_periods`.`start_date`)
    FROM `academic_periods`
    WHERE NOT `academic_periods`.`start_date` = '0000-00-00'
	)
, `institution_sites`.`year_opened` =
	(
		SELECT `academic_periods`.`start_year`
    FROM `academic_periods` 
    WHERE NOT `academic_periods`.`start_year` = 0
    HAVING MIN(`academic_periods`.`start_date`)
  )
WHERE `institution_sites`.`date_opened` = '0000-00-00';