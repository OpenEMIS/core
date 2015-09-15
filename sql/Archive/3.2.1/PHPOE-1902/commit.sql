INSERT INTO `db_patches` VALUES ('PHPOE-1902');

CREATE TABLE `education_programmes_next_programmes` (
  `id` char(36) NOT NULL,
  `education_programme_id` int(11) NOT NULL,
  `next_programme_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `education_programmes_next_programmes` ADD INDEX(`education_programme_id`);
ALTER TABLE `education_programmes_next_programmes` ADD INDEX(`next_programme_id`);
