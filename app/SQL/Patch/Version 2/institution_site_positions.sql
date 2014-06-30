
DROP TABLE IF EXISTS `institution_site_positions`;
CREATE TABLE `institution_site_positions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `position_no` varchar(30) NOT NULL,
  `status` int(1) NOT NULL,
  `type` int(1) NOT NULL COMMENT '0-Non-Teaching / 1-Teacher ',
  `staff_position_title_id` int(11) NOT NULL,
  `staff_position_grade_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;
