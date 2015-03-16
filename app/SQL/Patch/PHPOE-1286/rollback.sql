SELECT navigations.order INTO @sysconfigOrder FROM navigations WHERE title = 'System Configurations';

UPDATE navigations SET navigations.order = navigations.order-1 WHERE navigations.order> @sysconfigOrder;

DELETE FROM navigations WHERE title = 'Notices';

-- SELECT * FROM `navigations` WHERE `module` LIKE 'Administration' ORDER BY `navigations`.`order` ASC

DROP TABLE notices;

DELETE FROM navigations WHERE name = 'Notices';