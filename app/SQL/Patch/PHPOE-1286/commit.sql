SELECT id INTO @adminBoundaryId FROM navigations WHERE title = 'Administrative Boundaries';
SELECT navigations.order INTO @sysconfigOrder FROM navigations WHERE title = 'System Configurations';

UPDATE navigations SET navigations.order = navigations.order+1 WHERE navigations.order> @sysconfigOrder;

INSERT INTO `navigations` (`module`, `plugin`, `controller`, `header`, `title`, `action`, `pattern`, `attributes`, `parent`, `is_wizard`, `order`, `visible`, `created_user_id`) VALUES ('Administration', NULL, 'Notices', 'System Setup', 'Notices', 'index', 'index|view|edit|add', NULL, @adminBoundaryId, 0, @sysconfigOrder+1, 1, 1);

-- SELECT * FROM `navigations` WHERE `module` LIKE 'Administration' ORDER BY `navigations`.`order` ASC



CREATE TABLE `notices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message` text NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;


SELECT MAX(security_functions.order) INTO @lastOrder from security_functions WHERE visible = 1;
INSERT INTO `security_functions` (`name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ('Notices', 'Notices', 'Administration', 'Notices', -1, 'index|view', '_view:edit', '_view:add', '_view:delete', NULL, @lastOrder + 1, 1, NULL, NULL, 1, '0000-00-00 00:00:00');


-- remove dashboard_notice
CREATE TABLE IF NOT EXISTS z_1286_config_items LIKE config_items;
INSERT INTO z_1286_config_items SELECT * FROM config_items WHERE name = 'dashboard_notice' AND NOT EXISTS (SELECT * FROM z_1286_config_items);

DELETE from config_items WHERE name = 'dashboard_notice';