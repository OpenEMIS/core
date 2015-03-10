--
-- New table structure for table `census_staff`
--

RENAME TABLE `census_staff` TO `census_staff_bak_bf_position` ;

CREATE TABLE `census_staff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gender_id` int(11) NOT NULL,
  `value` int(11) NOT NULL DEFAULT '0',
  `staff_position_title_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `school_year_id` int(11) NOT NULL,
  `source` int(1) DEFAULT '0' COMMENT '0-dataentry,1-external,2-estimate',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `institution_site_id` (`institution_site_id`),
  KEY `school_year_id` (`school_year_id`),
  KEY `staff_position_title_id` (`staff_position_title_id`),
  KEY `source` (`source`),
  KEY `gender_id` (`gender_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Update `navigtions` and `security_functions`
--

UPDATE `navigations` SET `action` = 'CensusStaff', `pattern` = 'CensusStaff' 
WHERE `module` LIKE 'Institution' AND `controller` LIKE 'Census' AND `title` LIKE 'Staff';


UPDATE `security_functions` SET `_view` = 'CensusStaff.index', `_edit` = '_view:CensusStaff.edit' 
WHERE `name` LIKE 'Staff' AND `controller` LIKE 'Census' AND `module` LIKE 'Institutions';

--
-- Update reports record
--

SET @staffReportId := 0;
SELECT `id` INTO @staffReportId FROM `reports` WHERE `reports`.`name` LIKE 'Staff' AND `reports`.`category` LIKE 'Institution Totals Reports';


UPDATE `batch_reports` SET `query` = "
$this->CensusStaff->formatResult = true;
$data = $this->CensusStaff->find('all',array(
'recursive'=>-1,
'fields'=>array('SchoolYear.name AS AcademicYear','InstitutionSite.name AS InstitutionName','StaffPositionTitle.name AS Position','Gender.name AS Gender','CensusStaff.value AS Staff'),
'joins' => array(
array('table' => 'staff_position_titles','alias' => 'StaffPositionTitle','conditions' => array('CensusStaff.staff_position_title_id = StaffPositionTitle.id')),
array('table' => 'field_option_values','alias' => 'Gender','conditions' => array('CensusStaff.gender_id = Gender.id')),
array('table' => 'institution_sites','alias' => 'InstitutionSite','conditions' => array('CensusStaff.institution_site_id = InstitutionSite.id')),
array('table' => 'school_years','alias' => 'SchoolYear','conditions' => array('CensusStaff.school_year_id = SchoolYear.id'))
),
'order' => array('SchoolYear.name', 'InstitutionSite.id', 'StaffPositionTitle.id', 'Gender.id'), {cond} ));",
`template` = 'AcademicYear,InstitutionName,Position,Gender,Staff' WHERE `batch_reports`.`report_id` = @staffReportId;