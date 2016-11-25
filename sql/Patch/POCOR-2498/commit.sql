-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-2498', NOW());

-- code here
-- Table structure for table `indexes`
CREATE TABLE IF NOT EXISTS `indexes` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `generated_on` datetime DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- Table structure for table `indexes_criteria`
CREATE TABLE IF NOT EXISTS `indexes_criteria` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `criteria` int(3) NOT NULL,
  `operator` int(3) NOT NULL,
  `threshold` int(3) NOT NULL,
  `index_value` int(2) NOT NULL,
  `index_id` int(3) NOT NULL COMMENT 'links to indexes.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `indexes_criteria`
  ADD KEY `index_id` (`index_id`);























