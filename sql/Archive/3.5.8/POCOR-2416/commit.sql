-- db_patches
INSERT INTO db_patches (`issue`, `created`) VALUES ('POCOR-2416', NOW());

CREATE TABLE IF NOT EXISTS `deleted_records` (
  `id` int(11) NOT NULL,
  `reference_table` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_key` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data`  mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `deleted_records`
--
ALTER TABLE `deleted_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reference_key` (`reference_key`),
  ADD KEY `created_user_id` (`created_user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `deleted_records`
--
ALTER TABLE `deleted_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;