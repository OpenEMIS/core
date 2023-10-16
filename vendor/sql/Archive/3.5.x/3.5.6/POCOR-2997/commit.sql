-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-2997', NOW());

-- backup the current table

ALTER TABLE `staff_training_needs` 
RENAME TO `z_2997_staff_training_needs`;

-- create new table and apply the changes

CREATE TABLE IF NOT EXISTS `staff_training_needs` (
  `id` int(11) NOT NULL,
  `comments` text,
  `course_code` varchar(60) NULL,
  `course_name` varchar(250) NULL,
  `course_description` text,
  `course_id` int(11) NOT NULL COMMENT 'links to training_courses.id',
  `training_need_category_id` int(11) NOT NULL,
  `training_requirement_id` int(11) NOT NULL,
  `training_priority_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `staff_training_needs`
--
ALTER TABLE `staff_training_needs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `training_need_category_id` (`training_need_category_id`),
  ADD KEY `training_requirement_id` (`training_requirement_id`),
  ADD KEY `training_priority_id` (`training_priority_id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `status_id` (`status_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `staff_training_needs`
--
ALTER TABLE `staff_training_needs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- reinsert from backup table

INSERT INTO `staff_training_needs`
SELECT `z_2997_staff_training_needs`.`id`, 
  `z_2997_staff_training_needs`.`comments`, 
  `z_2997_staff_training_needs`.`course_code`, 
  `z_2997_staff_training_needs`.`course_name`, 
  `z_2997_staff_training_needs`.`course_description`,
  `z_2997_staff_training_needs`.`course_id`, 
  `z_2997_staff_training_needs`.`training_need_category_id`, 
  `z_2997_staff_training_needs`.`training_requirement_id`,
  `z_2997_staff_training_needs`.`training_priority_id`, 
  `z_2997_staff_training_needs`.`staff_id`, 
  `z_2997_staff_training_needs`.`status_id`,
  `z_2997_staff_training_needs`.`modified_user_id`, 
  `z_2997_staff_training_needs`.`modified`, 
  `z_2997_staff_training_needs`.`created_user_id`, 
  `z_2997_staff_training_needs`.`created` 
FROM `z_2997_staff_training_needs`;