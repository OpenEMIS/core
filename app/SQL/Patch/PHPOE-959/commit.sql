SET @programmesReportId := 0;

SELECT `id` INTO @programmesReportId FROM `reports` WHERE `module` LIKE 'Institutions' AND `category` LIKE 'Institution Details Reports' AND `name` LIKE 'Programmes';

UPDATE `batch_reports` SET `query` = 
"$this->InstitutionSiteProgramme->formatResult = true;$data = $this->InstitutionSiteProgramme->find('all',array('recursive'=>-1,'fields'=>array('InstitutionSite.name AS InstitutionName','SchoolYear.name AS SchoolYear','EducationProgramme.name AS EducationProgrammeName'),
'joins'=>array(array('table' => 'school_years','alias' => 'SchoolYear','conditions' => array('InstitutionSiteProgramme.school_year_id = SchoolYear.id')),array('table' => 'education_programmes','alias' => 'EducationProgramme','conditions' => array('InstitutionSiteProgramme.education_programme_id = EducationProgramme.id')),array('table' => 'institution_sites','alias' => 'InstitutionSite','conditions' => array('InstitutionSiteProgramme.institution_site_id = InstitutionSite.id'))),
{cond}));", 
`template` = "InstitutionName,SchoolYear,EducationProgrammeName" 
WHERE `name` LIKE "Institution Site Programmes" AND `report_id` = @programmesReportId;

-------

SET @customFieldsReportId := 0;

SELECT `id` INTO @customFieldsReportId FROM `reports` WHERE `module` LIKE 'Institutions' AND `category` LIKE 'Institution General Reports' AND `name` LIKE 'Custom Field';

UPDATE `batch_reports` SET `query` = "$this->InstitutionSiteCustomValue->formatResult=true;
$data = $this->InstitutionSiteCustomValue->find('all',array(
'recursive'=>-1,
'fields'=>array('InstitutionSite.name AS InstitutionName','InstitutionSiteCustomField.name AS Information','InstitutionSiteCustomField.type AS Type','InstitutionSiteCustomValue.value AS Value','InstitutionSiteCustomFieldOption.value AS Option'),
'joins'=>array(
	array(
		'table' => 'institution_site_custom_fields',
		'alias' => 'InstitutionSiteCustomField',
		'conditions' => array('InstitutionSiteCustomValue.institution_site_custom_field_id = InstitutionSiteCustomField.id')
	),
	array(
		'table' => 'institution_site_custom_field_options',
		'alias' => 'InstitutionSiteCustomFieldOption',
		'type' => 'LEFT',
		'conditions' => array('InstitutionSiteCustomValue.value = InstitutionSiteCustomFieldOption.id')
	),
	array(
		'table' => 'institution_sites',
		'alias' => 'InstitutionSite',
		'conditions' => array('InstitutionSiteCustomValue.institution_site_id = InstitutionSite.id')
	)
),
'order'=>array('InstitutionSite.id', 'InstitutionSiteCustomField.id'),
{cond}));

foreach($data as $key => &$value) { 
if ($value['Type'] != 2 && $value['Type'] != 5 && $value['Type'] != 1) { 
$value['Value'] = $value['Option']; }}" 
WHERE `report_id` = @customFieldsReportId;

