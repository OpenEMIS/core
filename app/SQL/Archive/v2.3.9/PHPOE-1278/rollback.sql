ALTER TABLE `institution_site_staff`
  RENAME `1278_institution_site_staff`;

CREATE TABLE IF NOT EXISTS `institution_site_staff` (
  `id` int(11) NOT NULL,
  `FTE` decimal(5,2) DEFAULT NULL,
  `staff_status_id` int(3) NOT NULL,
  `staff_type_id` int(5) NOT NULL,
  `start_date` date NOT NULL,
  `start_year` int(4) NOT NULL,
  `end_date` date DEFAULT NULL,
  `end_year` int(4) DEFAULT NULL,
  `staff_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `institution_site_position_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

ALTER TABLE `institution_site_staff`
  ADD PRIMARY KEY (`id`), ADD KEY `staff_id` (`staff_id`), ADD KEY `staff_type_id` (`staff_type_id`), ADD KEY `staff_status_id` (`staff_status_id`), ADD KEY `institution_site_id` (`institution_site_id`), ADD KEY `institution_site_position_id` (`institution_site_position_id`);

ALTER TABLE `institution_site_staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

INSERT INTO `institution_site_staff` (`id`, `FTE`, `staff_status_id`, `staff_type_id`, `start_date`, `start_year`, `end_date`, `end_year`, `staff_id`, `institution_site_id`, `institution_site_position_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
  SELECT `id`, `FTE`, `staff_status_id`, `staff_type_id`, `start_date`, `start_year`, `end_date`, `end_year`, `staff_id`, `institution_site_id`, `institution_site_position_id`, `modified_user_id`, `modified`, `created_user_id`, `created` from `institution_site_staff_bak` where 1;

DROP TABLE `1278_institution_site_staff`;