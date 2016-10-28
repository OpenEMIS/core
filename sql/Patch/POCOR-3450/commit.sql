-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3450', NOW());

-- code here
CREATE TABLE IF NOT EXISTS `staff_appraisals` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `staff_appraisal_type_id` int(11) NOT NULL COMMENT '2 = Self, 3 = Supervisor, 4 = Peer',
  `title` VARCHAR(100) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `from` date NOT NULL COMMENT 'From',
  `to` date NOT NULL COMMENT 'To',
  `competency_set_id` int(11) NOT NULL,
  `final_rating` DECIMAL(4,2) NOT NULL, -- 4 is the max digits, 2 is the max digits after the decimal point
  `comment` text DEFAULT NULL,
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `staff_appraisal_types` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `code` VARCHAR(100) NOT NULL,
  `name` VARCHAR(250) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

INSERT INTO `staff_appraisal_types` (`code`, `name`)
VALUES  ('SELF', 'Self'),
        ('SUPERVISOR', 'Supervisor'),
        ('PEER', 'Peer');

CREATE TABLE IF NOT EXISTS `competencies` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(55) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `editable` int(1) NOT NULL DEFAULT '1',
  `default` int(1) NOT NULL DEFAULT '0',
  `min` DECIMAL(4,2) NOT NULL DEFAULT '0',
  `max` DECIMAL(4,2) NOT NULL DEFAULT '10',
  `international_code` VARCHAR(50) DEFAULT NULL,
  `national_code` VARCHAR(50) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `competency_sets` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `editable` int(1) NOT NULL DEFAULT '1',
  `default` int(1) NOT NULL DEFAULT '0',
  `international_code` VARCHAR(50) DEFAULT NULL,
  `national_code` VARCHAR(50) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `competency_sets_competencies` (
  `id` CHAR(36) NOT NULL PRIMARY KEY,
  `competency_set_id` int(11) NOT NULL,
  `competency_id` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `staff_appraisals_competencies` (
  `id` CHAR(36) NOT NULL PRIMARY KEY,
  `staff_appraisal_id` int(11) NOT NULL,
  `competency_id` int(11) NOT NULL,
  `rating` DECIMAL(4,2) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;


-- field_options
UPDATE `field_options` SET `order` = `order` + 2 WHERE `order` >= 19;

INSERT INTO `field_options` (`id`, `plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES  ('25', 'Staff', 'Competencies', 'Competencies', 'Staff', NULL, 19, '1', NULL, NULL, 0, NOW()),
        ('26', 'Staff', 'CompetencySets', 'Competency Sets', 'Staff', NULL, 20, '1', NULL, NULL, 0, NOW());

-- security_function (permission)
UPDATE `security_functions` SET `order` = `order` + 1 WHERE `id` BETWEEN 3000 AND 4000 AND `order` >= 3025;
UPDATE `security_functions` SET `order` = `order` + 1 WHERE `id` BETWEEN 7000 AND 8000 AND `order` >= 7033;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES  ('3037', 'Appraisals', 'Staff', 'Institutions', 'Staff - Professional Development', '3000', 'Appraisals.index|Appraisals.view', 'Appraisals.edit', 'Appraisals.add', 'Appraisals.remove', NULL, '3025', '1', NULL, NULL, NULL, '1', NOW()),
        ('7049', 'Appraisals', 'Directories', 'Directory', 'Staff - Professional Development', '7000', 'StaffAppraisals.index|StaffAppraisals.view', 'StaffAppraisals.edit', 'StaffAppraisals.add', 'StaffAppraisals.remove', NULL, '7033', '1', NULL, NULL, NULL, '1', NOW());


