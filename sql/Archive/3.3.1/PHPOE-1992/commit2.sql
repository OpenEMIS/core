CREATE TABLE `z_1992_staff_training_needs` LIKE `staff_training_needs`;
DROP TABLE IF EXISTS `staff_training_needs`;

-- --------------------------------------------------------

--
-- Table structure for table `training_needs`
--

DROP TABLE IF EXISTS `training_needs`;
CREATE TABLE `training_needs` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `training_need_category_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL COMMENT 'links to training_courses.id',
  `course_code` varchar(10) DEFAULT NULL,
  `course_title` varchar(100) DEFAULT NULL,
  `course_description` text,
  `training_requirement_id` int(11) NOT NULL,
  `training_priority_id` int(11) NOT NULL,
  `status_id` int(11) NOT NULL COMMENT 'links to workflow',
  `comments` text,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `training_needs`
--
ALTER TABLE `training_needs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `status_id` (`status_id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `training_requirement_id` (`training_requirement_id`),
  ADD KEY `training_priority_id` (`training_priority_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `training_needs`
--
ALTER TABLE `training_needs` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
