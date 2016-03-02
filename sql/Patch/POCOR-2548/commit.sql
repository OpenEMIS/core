-- db_patches
INSERT INTO `db_patches` VALUES('POCOR-2548', NOW());

-- institutions
CREATE TABLE `z_2548_institutions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date_opened` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=225 DEFAULT CHARSET=utf8;

INSERT INTO `z_2548_institutions`
SELECT `id`, `date_opened` 
FROM `institutions` 
WHERE NOT YEAR(`date_opened`) = `year_opened`;

UPDATE `institutions` 
SET `date_opened` = CONCAT(`year_opened`, '-01-01') 
WHERE NOT YEAR(`date_opened`) = `year_opened`;