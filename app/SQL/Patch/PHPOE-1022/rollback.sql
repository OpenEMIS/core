DROP TABLE IF EXISTS `census_staff`;

RENAME TABLE `census_staff_bak_bf_position` TO `census_staff` ;

--
-- Rollback the changes to `navigtions` and `security_functions`
--

UPDATE `_openemis_`.`navigations` SET `action` = 'staff', `pattern` = 'staff' 
WHERE `module` LIKE 'Institution' AND `controller` LIKE 'Census' AND `title` LIKE 'Staff';

UPDATE `_openemis_`.`security_functions` SET `_view` = 'staff', `_edit` = '_view:staffEdit' 
WHERE `name` LIKE 'Staff' AND `controller` LIKE 'Census' AND `module` LIKE 'Institutions';