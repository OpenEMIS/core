DROP TABLE IF EXISTS `report_progress`;

CREATE TABLE IF NOT EXISTS `report_progress` (
  `id` char(36) NOT NULL,
  `name` varchar(100) NOT NULL,
  `module` varchar(100) NULL,
  `params` text NOT NULL,
  `expiry_date` datetime DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `current_records` int(11) NOT NULL DEFAULT '0',
  `total_records` int(11) NOT NULL DEFAULT '0',
  `pid` int(11) NOT NULL DEFAULT '0',
  `status` int(1) NOT NULL DEFAULT '1',
  `error_message` text NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE `navigations` SET `plugin` = NULL, `controller` = 'InstitutionReports', `title` = 'List of Reports', `action` = 'index', `pattern` = 'index' WHERE `parent` = -1 AND `controller` = 'Reports' AND `title` = 'General' AND `action` = 'InstitutionGeneral';
UPDATE `navigations` SET `plugin` = NULL, `controller` = 'InstitutionReports', `title` = 'Generate', `action` = 'generate', `pattern` = 'generate' WHERE `controller` = 'Reports' AND `title` = 'Details' AND `action` = 'InstitutionDetails';

UPDATE `navigations` SET `plugin` = 'Students', `controller` = 'StudentReports', `title` = 'List of Reports', `action` = 'index', `pattern` = 'index' WHERE `controller` = 'Reports' AND `title` = 'General' AND `action` = 'StudentGeneral';
UPDATE `navigations` SET `plugin` = 'Students', `controller` = 'StudentReports', `title` = 'Generate', `action` = 'generate', `pattern` = 'generate' WHERE `controller` = 'Reports' AND `title` = 'Details' AND `action` = 'StudentDetails';

-- hide other report links
UPDATE `navigations` SET `visible` = 0 WHERE `controller` = 'Reports' 
AND `action` IN (
  'InstitutionAttendance', 'InstitutionAssessment', 'InstitutionBehaviors', 'InstitutionFinance', 'InstitutionTotals', 'InstitutionQuality',
  'StudentFinance', 'StudentHealth'
);
