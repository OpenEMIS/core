SELECT navigations.order INTO @sysconfigOrder FROM navigations WHERE title = 'System Configurations';

UPDATE navigations SET navigations.order = navigations.order-1 WHERE navigations.order> @sysconfigOrder;

DELETE FROM navigations WHERE title = 'Notices';

-- SELECT * FROM `navigations` WHERE `module` LIKE 'Administration' ORDER BY `navigations`.`order` ASC

DROP TABLE notices;

DELETE FROM security_functions WHERE name = 'Notices';

-- remove dashboard_notice rollback
INSERT INTO config_items SELECT * FROM z_1286_config_items WHERE name = 'dashboard_notice' AND NOT EXISTS (SELECT * FROM config_items WHERE name = 'dashboard_notice');