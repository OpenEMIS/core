
CREATE TABLE `academic_periods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(60) NOT NULL,
  `name` varchar(100) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `lft` int(11) DEFAULT NULL,
  `rght` int(11) DEFAULT NULL,
  `academic_period_level_id` int(11) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `academic_period_level_id` (`academic_period_level_id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE `academic_period_levels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `level` int(3) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- default values

INSERT INTO `navigations` (`module`, `plugin`, `controller`, `header`, `title`, `action`, `pattern`, `attributes`, `parent`, `is_wizard`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('Administration', NULL, 'AcademicPeriods', 'System Setup', 'Academic Periods', 'index', 'AcademicPeriod', NULL, -1, 0, 47, 1, NULL, NULL, 1, '0000-00-00 00:00:00');
SELECT id INTO @academicBoundriesId FROM navigations WHERE header = 'System Setup' AND title = 'Administrative Boundaries'; 
SELECT id INTO @academicPeriodId FROM navigations WHERE header = 'System Setup' AND title = 'Academic Periods'; 
UPDATE navigations SET parent = @academicPeriodId WHERE header = 'System Setup' AND title = 'Administrative Boundaries'; 
UPDATE navigations SET parent = @academicPeriodId WHERE parent = @academicBoundriesId; 



INSERT INTO academic_period_levels (id, name, level) VALUES 
('1', 'Year', '1'),
('2', 'Semester', '2'),
('3', 'Term', '3'),
('4', 'Month', '4'),
('5', 'Week', '5');

