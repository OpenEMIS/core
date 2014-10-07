DROP TABLE IF EXISTS `census_staff`;

RENAME TABLE `census_staff_bak_bf_position` TO `census_staff` ;

--
-- Rollback the changes to `navigtions` and `security_functions`
--

UPDATE `_openemis_`.`navigations` SET `action` = 'staff', `pattern` = 'staff' 
WHERE `module` LIKE 'Institution' AND `controller` LIKE 'Census' AND `title` LIKE 'Staff';

UPDATE `_openemis_`.`security_functions` SET `_view` = 'staff', `_edit` = '_view:staffEdit' 
WHERE `name` LIKE 'Staff' AND `controller` LIKE 'Census' AND `module` LIKE 'Institutions';

--
-- Update reports record
--

SET @staffReportId := 0;
SELECT `id` INTO @staffReportId FROM `reports` WHERE `reports`.`name` LIKE 'Staff' AND `reports`.`category` LIKE 'Institution Totals Reports';


UPDATE `_openemis_`.`batch_reports` SET `query` = "$this->CensusStaff->bindModel(array( 'belongsTo'=> array( 'StaffCategory'=>array('foreignKey' => 'staff_category_id'), 'InstitutionSite'=>array('foreignKey' => 'institution_site_id') ) )); $data = $this->CensusStaff->find('all',array('fields'=>array('SchoolYear.name AS AcademicYear','InstitutionSite.name AS InstitutionName','StaffCategory.name AS Category','CensusStaff.male AS Male','CensusStaff.female AS Female'),{cond}));",
`template` = "AcademicYear,InstitutionName,Category,Male,Female" WHERE `batch_reports`.`report_id` = @staffReportId;