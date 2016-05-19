-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3006', NOW());

-- create new backup table
CREATE TABLE IF NOT EXISTS `z_3006_institution_positions` (
  `id` int(11) NOT NULL,
  `position_no` varchar(30) NOT NULL
)ENGINE=InnoDB COLLATE utf8mb4_unicode_ci;

-- Indexes for table `z_3006_institution_positions`
--
ALTER TABLE `z_3006_institution_positions`
  ADD PRIMARY KEY (`id`);

-- insert backup from main table

INSERT INTO `z_3006_institution_positions`
SELECT 
  `institution_positions`.`id`,  
  `institution_positions`.`position_no`
FROM `institution_positions`;

-- patch to remove blank space

UPDATE `institution_positions`
SET `position_no` = REPLACE(`position_no`, ' ', '');