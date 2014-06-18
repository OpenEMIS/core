--
-- Institution Site with No Area Report
--

UPDATE `batch_reports` 
SET `query` = '$data = $this->InstitutionSite->find(''all'',
                array(
                    ''fields''=>array(''InstitutionSite.Code AS Code'',''InstitutionSite.name AS InstitutionName'',''InstitutionSite.address AS Address'',''InstitutionSite.postal_code AS PostalCode'',''InstitutionSite.contact_person AS ContactPerson'',''InstitutionSite.telephone AS Telephone'',''InstitutionSite.fax AS Fax'',''InstitutionSite.email AS Email'',''InstitutionSite.website AS Website'',''InstitutionSite.date_opened AS DateOpened'',''InstitutionSite.date_closed AS DateClosed'',''InstitutionSite.longitude AS Longitude'',''InstitutionSite.latitude AS Latitude'',''Area.name AS AreaName'',''InstitutionSiteLocality.name AS Locality'',''InstitutionSiteType.name AS SiteType'',''InstitutionSiteOwnership.name AS Ownership'',''InstitutionSiteStatus.name AS Status''),
                    ''conditions'' => array(''InstitutionSite.area_id = 0 OR InstitutionSite.area_id is null''),{cond}

                )
            );', 
`template` = 'Code,InstitutionName,Address,PostalCode,ContactPerson,Telephone,Fax,Email,Website,DateOpened,DateClosed,Longitude,Latitude,AreaName,Locality,SiteType,Ownership,Status' 
WHERE `name` LIKE 'Institution Site with No Area Report';

--
-- Missing Coordinates Report
--

UPDATE `batch_reports` 
SET `query` = '$data = $this->InstitutionSite->find(''all'',array(''fields''=>array(''InstitutionSite.name AS InstitutionName'',''InstitutionSite.longitude AS Longitude'',''InstitutionSite.latitude AS Latitude''),{cond}));foreach ($data as $k => $v) { if ((!is_null($data[$k][''InstitutionSite''][''Longitude'']) && $data[$k][''InstitutionSite''][''Longitude''] != 0) && (!is_null($data[$k][''InstitutionSite''][''Latitude'']) && $data[$k][''InstitutionSite''][''Latitude''] != 0)) { unset($data[$k]); }}',
`template` = 'InstitutionName,Longitude,Latitude' 
WHERE `name` LIKE 'Missing Coordinates Report';

--
-- Data Outliers
--


UPDATE `batch_reports` 
SET `query` = 'App::import(''model'',''ConfigItem'');$ci = new ConfigItem();
$report_outlier_max_age = $ci->find(''first'',array(''conditions'' => array(''Name''=>''report_outlier_max_age'',''Type'' => ''Data Outliers'')));
$report_outlier_max_age = $report_outlier_max_age[''ConfigItem''][''value''];
$report_outlier_min_age = $ci->find(''first'', array(''conditions'' => array(''Name''=>''report_outlier_min_age'',''Type'' => ''Data Outliers'')));
$report_outlier_min_age = $report_outlier_min_age[''ConfigItem''][''value''];
$report_outlier_max_student = $ci->find(''first'', array(''conditions'' => array(''Name''=>''report_outlier_max_student'',''Type'' => ''Data Outliers'')));
$report_outlier_max_student = $report_outlier_max_student[''ConfigItem''][''value''];
$report_outlier_min_student = $ci->find(''first'', array(''conditions'' => array(''Name''=>''report_outlier_min_student'',''Type'' => ''Data Outliers'')));
$report_outlier_min_student = $report_outlier_min_student[''ConfigItem''][''value''];
$data = $this->CensusStudent->find(''all'',array(''fields''=>array(''SchoolYear.name AS AcademicYear'',''InstitutionSite.name AS InstitutionName'',''EducationGrade.name AS EducationGradeName'',''StudentCategory.name AS Category'',''age'',''CensusStudent.male AS Male'',''CensusStudent.female AS Female''),''conditions''=>array(''OR''=>array(''(CensusStudent.Male+CensusStudent.Female) > ''.$report_outlier_max_student,''(CensusStudent.Male+CensusStudent.Female) < ''.$report_outlier_min_student,''CensusStudent.age > '' . $report_outlier_max_age,''CensusStudent.age < '' . $report_outlier_min_age)),{cond}));', 
`template` = 'AcademicYear,InstitutionName,EducationGradeName,Category,age,Male,Female' 
WHERE `name` LIKE 'Data Outliers';

--
-- Data Discrepancy Report
--

UPDATE `batch_reports` 
SET `query` = 'App::import(''model'',''ConfigItem'');$ci = new ConfigItem();App::import(''model'',''SchoolYear'');$sy = new SchoolYear();$variation=$ci->find(''first'',array(''conditions'' => array(''Name''=>''report_discrepancy_variationpercent'',''Type'' => ''Data Discrepancy'')));$variation=$variation[''ConfigItem''][''value''];$currentschoolyear = $sy->findByCurrent(''1'');$currentschoolyear = $currentschoolyear[''SchoolYear''][''id''];$previousyear = $sy->find(''neighbors'', array(''field'' => ''id'', ''value'' => $currentschoolyear));$previousyear=$previousyear[''prev''][''SchoolYear''][''id''];$data = $this->CensusStudent->find(''all'',array(''fields''=>array(''SchoolYear.name as AcademicYear'',''EducationGrade.name as EducationGradeName'',''StudentCategory.name as Category'',''InstitutionSite.name as InstitutionName'',''CensusStudent.male as Male'',''CensusStudent.female as Female'',''sc.name as PreviousYear'',''c2.male as PreviousYearMale'',''c2.female as PreviousYearFemale''),''joins''=>array(array(''table'' => ''census_students'',''alias'' =>'' c2'',''type'' => ''LEFT OUTER'',''conditions'' => array(''CensusStudent.institution_site_id = c2.institution_site_id'',''CensusStudent.education_grade_id = c2.education_grade_id'',''CensusStudent.student_category_id = c2.student_category_id'',''CensusStudent.age = c2.age'', ''CensusStudent.school_year_id'' => $currentschoolyear,''c2.school_year_id'' => $previousyear)),array(''table'' => ''school_years'',''alias'' =>'' sc'',''type'' => ''LEFT'',''conditions'' => array(''c2.school_year_id = sc.id'')) ),''conditions''=>array(''(CensusStudent.male+CensusStudent.female) > ((c2.male+c2.female)*((''.$variation.''+100)/100))''), ''order''=>''CensusStudent.id desc'',{cond}));', 
`template` = 'AcademicYear,InstitutionName,EducationGradeName,Category,Male,Female,PreviousYear,PreviousYearMale,PreviousYearFemale' 
WHERE `name` LIKE 'Data Discrepancy Report';

--
-- Non-Responsive Schools Report
--

UPDATE `batch_reports` 
SET `query` = '$data = $this->InstitutionSite->find(''all'',array(''fields''=>array(''sc.name as Year'', ''InstitutionSite.Code AS Code'',''InstitutionSite.name AS InstitutionName'',''InstitutionSite.address AS Address'',''InstitutionSite.postal_code AS PostalCode'',''InstitutionSite.contact_person AS ContactPerson'',''InstitutionSite.telephone AS Telephone'',''InstitutionSite.fax AS Fax'',''InstitutionSite.email AS Email'',''InstitutionSite.website AS Website'',''InstitutionSite.date_opened AS DateOpened'',''InstitutionSite.date_closed AS DateClosed'',''InstitutionSite.longitude AS Longitude'',''InstitutionSite.latitude AS Latitude'',''Area.name AS AreaName'',''InstitutionSiteLocality.name AS Locality'',''InstitutionSiteType.name AS SiteType'',''InstitutionSiteOwnership.name AS Ownership'',''InstitutionSiteStatus.name AS Status''),
                ''joins''=>array(
    array(''table'' => ''school_years'',
            ''alias'' =>'' sc'',
          ''type'' => ''CROSS'',
            ''conditions'' => array(
          '''')
         )),''order''=>''sc.name desc'',{cond}));
 $exist = $this->CensusStudent->find(''all'',array(''fields''=>array(''InstitutionSite.Code as code'',''SchoolYear.Name as name''),''group'' => ''InstitutionSite.Code,SchoolYear.Name''));
 foreach ($exist as $k => $v) { $exist[$exist[$k][''SchoolYear''][''name'']."-".$exist[$k][''InstitutionSite''][''code'']] = 1;unset($exist[$k]); } 
foreach($data as $k => &$v){ if (isset($exist[$data[$k][''sc''][''Year'']."-".$data[$k][''InstitutionSite''][''Code'']])) { unset($data[$k]); } }', 
`template` = 'Year,Code,InstitutionName,Address,PostalCode,ContactPerson,Telephone,Fax,Email,Website,DateOpened,DateClosed,Longitude,Latitude,AreaName,Locality,SiteType,Ownership,Status' 
WHERE `name` LIKE 'Non-Responsive Schools Report';


--
-- Year Book Report
--

UPDATE `batch_reports` 
SET `query` = '$InstitutionSite = ClassRegistry::init(''InstitutionSite'');
$EducationCycle = ClassRegistry::init(''EducationCycle'');
$Provider = $InstitutionSite->InstitutionSiteProvider;
$Area = ClassRegistry::init(''Area'');
$ConfigItem = ClassRegistry::init(''ConfigItem'');
$yearId = $ConfigItem->getValue(''yearbook_school_year'');
$header = __(''Institution Sites'');
$sectionHead_1 = __(''Summary by Level and Provider'');
$cycleList = $EducationCycle->findList(true);
$providerList = $Provider->findList(true);
$tableHead_1 = '''';
$tableData_1 = '''';
$col = ''<td>%s</td>'';
foreach($cycleList as $item) {
	$tableHead_1 .= sprintf($col, $item);
}
foreach($providerList as $providerId => $provider) {
	$tableData_1 .= ''<tr>'';
	$tableData_1 .= sprintf(''<td class="textLeft">%s</td>'', $provider);
	foreach($cycleList as $cycleId => $cycle) {
		$tableData_1 .= sprintf($col, $InstitutionSite->getCountByCycleId($yearId, $cycleId, array(''providerId'' => $providerId)));
	}
	$tableData_1 .= ''</tr>'';
}

$areas = $Area->getAreasByLevel(2);
$sectionHead_2 = __(''Summary by Level and Area'');
foreach($areas as $area) {
	$tableData_2 .= ''<tr>'';
	$tableData_2 .= sprintf(''<td class="textLeft">%s</td>'', $area[''name'']);
	foreach($cycleList as $cycleId => $cycle) {
		$tableData_2 .= sprintf($col, $InstitutionSite->getCountByCycleId($yearId, $cycleId, array(''areaId'' => $area[''id''])));
	}
	$tableData_2 .= ''</tr>'';
}
$vars = compact(''header'', ''sectionHead_1'', ''tableHead_1'', ''tableData_1'', ''sectionHead_2'', ''tableData_2'');' 
WHERE `name` LIKE 'Institution Sites'
AND `report_id` =112;


UPDATE `batch_reports` 
SET `query` = '$CensusStudent = ClassRegistry::init(''CensusStudent'');
$EducationCycle = ClassRegistry::init(''EducationCycle'');
$InstitutionSite = ClassRegistry::init(''InstitutionSite'');
$Provider = $InstitutionSite->InstitutionSiteProvider;
$Area = ClassRegistry::init(''Area'');
$ConfigItem = ClassRegistry::init(''ConfigItem'');
$gender = array(''M'', ''F'', ''T'');
$legend = __(''M = Male, F = Female, T = Total'');
$genderRow = ''<tr><td></td>'';
$yearId = $ConfigItem->getValue(''yearbook_school_year'');
$header = __(''Students'');
$sectionHead_1 = __(''Summary by Level and Provider'');
$cycleList = $EducationCycle->findList(true);
$providerList = $Provider->findList(true);
$tableHead_1 = '''';
$tableData_1 = '''';
$col = ''<td>%s</td>'';
foreach($cycleList as $item) {
	$tableHead_1 .= sprintf(''<td colspan="3">%s</td>'', $item);
	foreach($gender as $g) {
		$genderRow .= ''<td>'' . $g . ''</td>'';
	}
}
$genderRow .= ''</tr>'';
$tableHead_1 .= $genderRow;

foreach($providerList as $providerId => $provider) {
	$tableData_1 .= ''<tr>'';
	$tableData_1 .= sprintf(''<td class="textLeft">%s</td>'', $provider);
	foreach($cycleList as $cycleId => $cycle) {
		$data = $CensusStudent->getCountByCycleId($yearId, $cycleId, array(''providerId'' => $providerId));
		$data[''T''] = $data[''M''] + $data[''F''];
		foreach($gender as $g) {
			$value = empty($data[$g]) ? 0 : $data[$g];
			$tableData_1 .= sprintf($col, $value);
		}
	}
	$tableData_1 .= ''</tr>'';
}

$areas = $Area->getAreasByLevel(2);
$sectionHead_2 = __(''Summary by Level and Area'');
foreach($areas as $area) {
	$tableData_2 .= ''<tr>'';
	$tableData_2 .= sprintf(''<td class="textLeft">%s</td>'', $area[''name'']);
	foreach($cycleList as $cycleId => $cycle) {
		$data = $CensusStudent->getCountByCycleId($yearId, $cycleId, array(''areaId'' => $area[''id'']));
		$data[''T''] = $data[''M''] + $data[''F''];
		foreach($gender as $g) {
			$value = empty($data[$g]) ? 0 : $data[$g];
			$tableData_2 .= sprintf($col, $value);
		}
	}
	$tableData_2 .= ''</tr>'';
}
$vars = compact(''header'', ''legend'', ''sectionHead_1'', ''tableHead_1'', ''tableData_1'', ''sectionHead_2'', ''tableData_2'');' 
WHERE `name` LIKE 'Students'
AND `report_id` =112;


UPDATE `batch_reports` 
SET `query` = '$CensusTeacher = ClassRegistry::init(''CensusTeacher'');
$EducationCycle = ClassRegistry::init(''EducationCycle'');
$InstitutionSite = ClassRegistry::init(''InstitutionSite'');
$Provider = $InstitutionSite->InstitutionSiteProvider;
$Area = ClassRegistry::init(''Area'');
$ConfigItem = ClassRegistry::init(''ConfigItem'');
$gender = array(''M'', ''F'', ''T'');
$legend = ''M = Male, F = Female, T = Total'';
$genderRow = ''<tr><td></td>'';
$yearId = $ConfigItem->getValue(''yearbook_school_year'');
$header = __(''Teachers'');
$sectionHead_1 = __(''Summary by Level and Provider'');
$cycleList = $EducationCycle->findList(true);
$providerList = $Provider->findList(true);
$tableHead_1 = '''';
$tableData_1 = '''';
$col = ''<td>%s</td>'';
foreach($cycleList as $item) {
	$tableHead_1 .= sprintf(''<td colspan="3">%s</td>'', $item);
	foreach($gender as $g) {
		$genderRow .= ''<td>'' . $g . ''</td>'';
	}
}
$genderRow .= ''</tr>'';
$tableHead_1 .= $genderRow;

foreach($providerList as $providerId => $provider) {
	$tableData_1 .= ''<tr>'';
	$tableData_1 .= sprintf(''<td class="textLeft">%s</td>'', $provider);
	foreach($cycleList as $cycleId => $cycle) {
		$data = $CensusTeacher->getCountByCycleId($yearId, $cycleId, array(''providerId'' => $providerId));
		$data[''T''] = $data[''M''] + $data[''F''];
		foreach($gender as $g) {
			$value = empty($data[$g]) ? 0 : $data[$g];
			$tableData_1 .= sprintf($col, $value);
		}
	}
	$tableData_1 .= ''</tr>'';
}

$areas = $Area->getAreasByLevel(2);
$sectionHead_2 = __(''Summary by Level and Area'');
foreach($areas as $area) {
	$tableData_2 .= ''<tr>'';
	$tableData_2 .= sprintf(''<td class="textLeft">%s</td>'', $area[''name'']);
	foreach($cycleList as $cycleId => $cycle) {
		$data = $CensusTeacher->getCountByCycleId($yearId, $cycleId, array(''areaId'' => $area[''id'']));
		$data[''T''] = $data[''M''] + $data[''F''];
		foreach($gender as $g) {
			$value = empty($data[$g]) ? 0 : $data[$g];
			$tableData_2 .= sprintf($col, $value);
		}
	}
	$tableData_2 .= ''</tr>'';
}
$vars = compact(''header'', ''legend'', ''sectionHead_1'', ''tableHead_1'', ''tableData_1'', ''sectionHead_2'', ''tableData_2'');' 
WHERE `name` LIKE 'Teachers'
AND `report_id` =112;

--
-- Student Assessment Report
--

UPDATE `batch_reports` SET `query` = '$Result = ClassRegistry::init(''AssessmentItemResult'');
$Result->formatResult = true;
$data = $Result->find(''all'', array(
	''recursive'' => -1,
	''fields'' => array(
		''InstitutionSite.name AS InstitutionName'', ''Student.first_name AS StudentFirstName'', 
		''Student.last_name AS StudentLastName'', ''SchoolYear.name AS SchoolYear'',
		''AssessmentItemType.name AS Assessment'', ''EducationSubject.code AS SubjectCode'', 
		''EducationSubject.name AS Subject'', ''AssessmentItemResult.marks AS Marks''
	),
	''joins'' => array(
		array(
			''table'' => ''assessment_items'',
			''alias'' => ''AssessmentItem'',
			''conditions'' => array(''AssessmentItem.id = AssessmentItemResult.assessment_item_id'')
		),
		array(
			''table'' => ''assessment_item_types'',
			''alias'' => ''AssessmentItemType'',
			''conditions'' => array(''AssessmentItemType.id = AssessmentItem.assessment_item_type_id'')
		),
		array(
			''table'' => ''assessment_result_types'',
			''alias'' => ''AssessmentResultType'',
			''type'' => ''LEFT'',
			''conditions'' => array(''AssessmentResultType.id = AssessmentItemResult.assessment_result_type_id'')
		),
		array(
			''table'' => ''education_grades_subjects'',
			''alias'' => ''EducationGradeSubject'',
			''conditions'' => array(''EducationGradeSubject.id = AssessmentItem.education_grade_subject_id'')
		),
		array(
			''table'' => ''education_subjects'',
			''alias'' => ''EducationSubject'',
			''conditions'' => array(''EducationSubject.id = EducationGradeSubject.education_subject_id'')
		),
		array(
			''table'' => ''students'',
			''alias'' => ''Student'',
			''conditions'' => array(''Student.id = AssessmentItemResult.student_id'')
		),
		array(
			''table'' => ''institution_sites'',
			''alias'' => ''InstitutionSite'',
			''conditions'' => array(''InstitutionSite.id = AssessmentItemResult.institution_site_id'')
		),
		array(
			''table'' => ''school_years'',
			''alias'' => ''SchoolYear'',
			''conditions'' => array(''SchoolYear.id = AssessmentItemResult.school_year_id'')
		)
	),
	''order'' => array(''InstitutionSite.name'', ''Student.first_name'', ''SchoolYear.start_year DESC'', ''AssessmentItemType.order'', ''EducationSubject.code''),
	{cond}
));', 
`template` = 'InstitutionName,StudentFirstName,StudentLastName,SchoolYear,Assessment,SubjectCode,Subject,Marks' 
WHERE `name` LIKE 'Student Assessment Report';

--
-- Expenditure Report
--

UPDATE `batch_reports` SET `query` = '$this->CensusFinance->bindModel(array(
    ''belongsTo''=> array(
        ''InstitutionSite'' => array(
            ''foreignKey'' => ''institution_site_id''
        ),
        ''FinanceType'' => array(
            ''joinTable''  => ''finance_types'',
            ''foreignKey'' => false,
            ''conditions'' => array('' FinanceType.id = FinanceCategory.finance_type_id ''),
        ),
        ''FinanceNature'' => array(
            ''joinTable''  => ''finance_natures'',
            ''foreignKey'' => false,
            ''conditions'' => array('' FinanceNature.id = FinanceType.finance_nature_id ''),
        ),
    )
));
$data = $this->CensusFinance->find(''all'',array(''fields''=>array(''SchoolYear.name AS AcademicYear'',''InstitutionSite.name AS InstitutionName'',''FinanceCategory.name AS Category'',''FinanceSource.name AS Source'',''CensusFinance.description AS Description'',''CensusFinance.amount AS Amount''), array(''conditions'' => array(''FinanceNature.id'' => array(2, 4))),{cond}));', 
`template` = 'AcademicYear,InstitutionName,Category,Source,Description,Amount' 
WHERE `name` LIKE 'Expenditure Report' AND `report_id` = 72;


--
-- Income Report
--

UPDATE `batch_reports` SET `query` = '$this->CensusFinance->bindModel(array(
    ''belongsTo''=> array(
        ''InstitutionSite'' => array(
            ''foreignKey'' => ''institution_site_id''
        ),
        ''FinanceType'' => array(
            ''joinTable''  => ''finance_types'',
            ''foreignKey'' => false,
            ''conditions'' => array('' FinanceType.id = FinanceCategory.finance_type_id ''),
        ),
        ''FinanceNature'' => array(
            ''joinTable''  => ''finance_natures'',
            ''foreignKey'' => false,
            ''conditions'' => array('' FinanceNature.id = FinanceType.finance_nature_id ''),
        ),
    )
));
$data = $this->CensusFinance->find(''all'',array(''fields''=>array(''SchoolYear.name AS AcademicYear'',''InstitutionSite.name AS InstitutionName'',''FinanceCategory.name AS Category'',''FinanceSource.name AS Source'',''CensusFinance.description AS Description'',''CensusFinance.amount AS Amount''), array(''conditions'' => array(''FinanceNature.id'' => array(1, 3))),{cond}));', 
`template` = 'AcademicYear,InstitutionName,Category,Source,Description,Amount' 
WHERE `name` LIKE 'Income Report' AND `report_id` = 71;

--
-- Water Report
--

UPDATE `batch_reports` 
SET `query` = '$this->CensusWater->bindModel(array( ''belongsTo''=> array( ''SchoolYear'' => array(''foreignKey'' => ''school_year_id''), ''InfrastructureWater'' => array(''foreignKey'' => ''infrastructure_Water_id''), ''InfrastructureStatus'' => array(''foreignKey'' => ''infrastructure_status_id''), ''InstitutionSite''=>array(''foreignKey'' => ''institution_site_id'') ) )); $data = $this->CensusWater->find(''all'',array(''fields''=>array(''SchoolYear.name AS AcademicYear'',''InstitutionSite.name AS InstitutionName'',''InfrastructureWater.name AS Water'',''InfrastructureStatus.name AS Status'',''CensusWater.value AS Value''),{cond}));',
`template` = 'AcademicYear,InstitutionName,Water,Status,Value' 
WHERE `name` LIKE 'Water Report' AND `report_id` = 57;

--
-- Energy Report
--

UPDATE `batch_reports` 
SET `query` = '$this->CensusEnergy->bindModel(array( ''belongsTo''=> array( ''SchoolYear'' => array(''foreignKey'' => ''school_year_id''), ''InfrastructureEnergy'' => array(''foreignKey'' => ''infrastructure_energy_id''), ''InfrastructureStatus'' => array(''foreignKey'' => ''infrastructure_status_id''), ''InstitutionSite''=>array(''foreignKey'' => ''institution_site_id'') ) )); $data = $this-> CensusEnergy->find(''all'',array(''fields''=>array(''SchoolYear.name AS AcademicYear'',''InstitutionSite.name AS InstitutionName'',''InfrastructureEnergy.name AS Energy'',''InfrastructureStatus.name AS Status'',''CensusEnergy.value AS Value''),{cond}));',
`template` = 'AcademicYear,InstitutionName,Energy,Status,Value' 
WHERE `name` LIKE 'Energy Report' AND `report_id` = 56;

--
-- Resource Report
--

UPDATE `batch_reports` 
SET `query` = '$this->CensusResource->bindModel(array( ''belongsTo''=> array( ''SchoolYear'' => array(''foreignKey'' => ''school_year_id''), ''InfrastructureResource'' => array(''foreignKey'' => ''infrastructure_resource_id''), ''InfrastructureStatus'' => array(''foreignKey'' => ''infrastructure_status_id''), ''InstitutionSite''=>array(''foreignKey'' => ''institution_site_id'') ) )); $data = $this->CensusResource->find(''all'',array(''fields''=>array(''SchoolYear.name AS AcademicYear'',''InstitutionSite.name AS InstitutionName'',''InfrastructureResource.name AS Resource'',''InfrastructureStatus.name AS Status'',''CensusResource.value AS Value''),{cond}));',
`template` = 'AcademicYear,InstitutionName,Resource,Status,Value' 
WHERE `name` LIKE 'Resource Report' AND `report_id` = 55;

--
-- Furniture Report
--

UPDATE `batch_reports` 
SET `query` = '$this->CensusFurniture->bindModel(array( ''belongsTo''=> array( ''SchoolYear'' => array(''foreignKey'' => ''school_year_id''), ''InfrastructureFurniture'' => array(''foreignKey'' => ''infrastructure_furniture_id''), ''InfrastructureStatus'' => array(''foreignKey'' => ''infrastructure_status_id''), ''InstitutionSite''=>array(''foreignKey'' => ''institution_site_id'') ) )); $data = $this->CensusFurniture->find(''all'',array(''fields''=>array(''SchoolYear.name AS AcademicYear'',''InstitutionSite.name AS InstitutionName'',''InfrastructureFurniture.name AS Furniture'',''InfrastructureStatus.name AS Status'',''CensusFurniture.value AS Value''),{cond}));',
`template` = 'AcademicYear,InstitutionName,Furniture,Status,Value' 
WHERE `name` LIKE 'Furniture Report' AND `report_id` = 54;

--
-- Sanitation Report
--

UPDATE `batch_reports` 
SET `query` = '$this->CensusSanitation->bindModel(array( ''belongsTo''=> array( ''SchoolYear'' => array(''foreignKey'' => ''school_year_id''), ''InfrastructureStatus'' => array(''foreignKey'' => ''infrastructure_status_id''), ''InstitutionSite''=>array(''foreignKey'' => ''institution_site_id'') ) )); $data = $this->CensusSanitation->find(''all'',array(''fields''=>array(''SchoolYear.name AS AcademicYear'',''InstitutionSite.name AS InstitutionName'',''InfrastructureSanitation.name AS Sanitation'',''InfrastructureStatus.name AS Status'',''CensusSanitation.unisex AS Unisex'',''CensusSanitation.male AS Male'',''CensusSanitation.female AS Female''),{cond}));',
`template` = 'AcademicYear,InstitutionName,Sanitation,Status,Unisex,Male,Female' 
WHERE `name` LIKE 'Sanitation Report' AND `report_id` = 53;

--
-- Room Report
--

UPDATE `batch_reports` 
SET `query` = '$this->CensusRoom->bindModel(array( ''belongsTo''=> array( ''SchoolYear'' => array(''foreignKey'' => ''school_year_id''), ''InfrastructureStatus'' => array(''foreignKey'' => ''infrastructure_status_id''), ''InstitutionSite''=>array(''foreignKey'' => ''institution_site_id'') ) )); $data = $this->CensusRoom->find(''all'',array(''fields''=>array(''SchoolYear.name AS AcademicYear'',''InstitutionSite.name AS InstitutionName'',''InfrastructureRoom.name AS Room'',''InfrastructureStatus.name AS Status'',''CensusRoom.value AS Value''),{cond}));',
`template` = 'AcademicYear,InstitutionName,Room,Status,Value' 
WHERE `name` LIKE 'Room Report' AND `report_id` = 52;

--
-- Building Report
--

UPDATE `batch_reports` 
SET `query` = '$this->CensusBuilding->bindModel(array( ''belongsTo''=> array( ''SchoolYear'' => array(''foreignKey'' => ''school_year_id''), ''InfrastructureStatus'' => array(''foreignKey'' => ''infrastructure_status_id''), ''InstitutionSite''=>array(''foreignKey'' => ''institution_site_id'') ) )); $data = $this->CensusBuilding->find(''all'',array(''fields''=>array(''SchoolYear.name AS AcademicYear'',''InstitutionSite.name AS InstitutionName'',''InfrastructureBuilding.name AS Building'',''InfrastructureMaterial.name AS Material'',''InfrastructureStatus.name AS Status'',''CensusBuilding.value AS Value''),{cond}));',
`template` = 'AcademicYear,InstitutionName,Building,Material,Status,Value' 
WHERE `name` LIKE 'Building Report' AND `report_id` = 51;

--
-- Custom Table Report
--

UPDATE `batch_reports` 
SET `query` = '$this->CensusGridValue->bindModel(array( ''belongsTo''=> array( ''SchoolYear'' => array(''foreignKey'' => ''school_year_id''), ''CensusGrid'', ''CensusGridXCategory'', ''CensusGridYCategory'', ''InstitutionSite''=>array(''foreignKey'' => ''institution_site_id'') ) )); $data = $this->CensusGridValue->find(''all'',array(''fields''=>array(''SchoolYear.name AS AcademicYear'',''InstitutionSite.name AS InstitutionName'',''CensusGridXCategory.name AS GridXCategory'',''CensusGridYCategory.name AS GridYCategory'',''CensusGridValue.value AS Value''),{cond}));',
`template` = 'AcademicYear,InstitutionName,GridXCategory,GridYCategory,Value' 
WHERE `name` LIKE 'Custom Table Report' AND `report_id` = 33;

--
-- Custom Field Report
--

UPDATE `batch_reports` 
SET `query` = '$this->CensusCustomField->unbindModel(array(''hasMany'' => array(''CensusCustomFieldOption'')));
$this->CensusCustomValue->bindModel(array(
           ''belongsTo''=> array(
                ''SchoolYear'' => array(''foreignKey'' => ''school_year_id''),
            	''CensusCustomFieldOption'' => array(
            		''joinTable''  => ''census_custom_field_options'',
                    ''foreignKey'' => false,
                    ''conditions'' => array('' CensusCustomFieldOption.id = CensusCustomValue.value '')
                ),
            )
));
$data = $this->CensusCustomValue->find(''all'',array(''fields''=>array(''SchoolYear.name AS AcademicYear'',''InstitutionSite.name AS InstitutionName'',''CensusCustomField.name AS Information'',''CensusCustomField.type AS Type'',''CensusCustomValue.value AS Value'',''CensusCustomFieldOption.value AS Option''),{cond}));foreach($data as $key => &$value) { if ($value[''CensusCustomField''][''Type''] != 2 && $value[''CensusCustomField''][''Type''] != 5) { $value[''CensusCustomValue''][''Value''] = $value[''CensusCustomFieldOption''][''Option'']; }}', 
`template` = 'AcademicYear,InstitutionName,Information,Value' 
WHERE `name` LIKE 'Custom Field Report' AND `report_id` = 32;

--
-- Textbook Report
--

UPDATE `batch_reports` 
SET `query` = '$data = $this->CensusTextbook->find(''all'', array(
			''recursive'' => -1,
			''fields'' => array(
				''SchoolYear.name AS AcademicYear'',
				''InstitutionSite.name AS InstitutionName'',
				''EducationSubject.name AS EducationSubjectName'',
				''CensusTextbook.value AS NoOfTextbooks''
			),
			''joins'' => array(
				array(
					''table'' => ''institution_sites'',
					''alias'' => ''InstitutionSite'',
					''conditions'' => array(''InstitutionSite.id = CensusTextbook.institution_site_id'')
				),

				array(
					''table'' => ''education_grades_subjects'',
					''alias'' => ''EducationGradeSubject'',
					''conditions'' => array(''EducationGradeSubject.id = CensusTextbook.education_grade_subject_id'')
				),
				array(
					''table'' => ''education_subjects'',
					''alias'' => ''EducationSubject'',
					''conditions'' => array(''EducationSubject.id = EducationGradeSubject.education_subject_id'')
				),
				array(
					''table'' => ''school_years'',
					''alias'' => ''SchoolYear'',
					''conditions'' => array(''SchoolYear.id = CensusTextbook.school_year_id'')
				)
			),{cond}
		));', 
`template` = 'AcademicYear,InstitutionName,EducationSubjectName,NoOfTextbooks' 
WHERE `name` LIKE 'Textbook Report' AND `report_id` = 31;

--
-- Behaviour Report
--

UPDATE `batch_reports` 
SET `query` = '$CensusBehaviour = ClassRegistry::init(''CensusBehaviour'');
		$CensusBehaviour->formatResult = true;
		$data = $CensusBehaviour->find(''all'', array(
			''recursive'' => -1,
			''fields'' => array(
				''SchoolYear.name AS AcademicYear'',
				''InstitutionSite.name AS InstitutionName'',
				''StudentBehaviourCategory.name AS Category'',
				''CensusBehaviour.male AS Male'',
				''CensusBehaviour.female AS Female''
			),
			''joins'' => array(
				array(
					''table'' => ''institution_sites'',
					''alias'' => ''InstitutionSite'',
					''conditions'' => array(''InstitutionSite.id = CensusBehaviour.institution_site_id'')
				),
				array(
					''table'' => ''school_years'',
					''alias'' => ''SchoolYear'',
					''conditions'' => array(''SchoolYear.id = CensusBehaviour.school_year_id'')
				),
				array(
					''table'' => ''student_behaviour_categories'',
					''alias'' => ''StudentBehaviourCategory'',
					''conditions'' => array(''StudentBehaviourCategory.id = CensusBehaviour.student_behaviour_category_id'')
				)
			),{cond}
		));', 
`template` = 'AcademicYear,InstitutionName,Category,Male,Female' 
WHERE `name` LIKE 'Behaviour Report' AND `report_id` = 30;

--
-- Assessment Report
--

UPDATE 
`batch_reports` SET `query` = '$CensusAssessment = ClassRegistry::init(''CensusAssessment'');
		$CensusAssessment->formatResult = true;
		$data = $CensusAssessment->find(''all'', array(
			''recursive'' => -1,
			''fields'' => array(
				''SchoolYear.name AS AcademicYear'',
				''InstitutionSite.name AS InstitutionName'',
				''EducationProgramme.name AS EducationProgramme'',
				''EducationGrade.name AS EducationGrade'',
				''EducationSubject.name AS EducationSubject'',
				''CensusAssessment.value AS Score''
			),
			''joins'' => array(
				array(
					''table'' => ''institution_sites'',
					''alias'' => ''InstitutionSite'',
					''conditions'' => array(''InstitutionSite.id = CensusAssessment.institution_site_id'')
				),
				array(
					''table'' => ''school_years'',
					''alias'' => ''SchoolYear'',
					''conditions'' => array(''SchoolYear.id = CensusAssessment.school_year_id'')
				),
				array(
					''table'' => ''education_grades_subjects'',
					''alias'' => ''EducationGradeSubject'',
					''conditions'' => array(''EducationGradeSubject.id = CensusAssessment.education_grade_subject_id'')
				),
				array(
					''table'' => ''education_grades'',
					''alias'' => ''EducationGrade'',
					''conditions'' => array(''EducationGrade.id = EducationGradeSubject.education_grade_id'')
				),
				array(
					''table'' => ''education_subjects'',
					''alias'' => ''EducationSubject'',
					''conditions'' => array(''EducationSubject.id = EducationGradeSubject.education_subject_id'')
				),
				array(
					''table'' => ''education_programmes'',
					''alias'' => ''EducationProgramme'',
					''conditions'' => array(''EducationProgramme.id = EducationGrade.education_programme_id'')
				)
			),{cond}
		));', 
`template` = 'AcademicYear,InstitutionName,EducationProgramme,EducationGrade,EducationSubject,Score' 
WHERE `name` LIKE 'Assessment Report' AND `report_id` = 29;

--
-- Attendance Report
--

