SET @programmesReportId := 0;

SELECT `id` INTO @programmesReportId FROM `reports` WHERE `module` LIKE 'Institutions' AND `category` LIKE 'Institution Details Reports' AND `name` LIKE 'Programmes';

UPDATE `batch_reports` SET `query` = "$data = $this->InstitutionSiteProgramme->find('all',array('fields'=>array('InstitutionSite.name AS InstitutionName','EducationProgramme.name AS EducationProgrammeName'),{cond}));", 
`template` = "InstitutionName,EducationProgrammeName" 
WHERE `report_id` = @programmesReportId;


SET @customFieldsReportId := 0;

SELECT `id` INTO @customFieldsReportId FROM `reports` WHERE `module` LIKE 'Institutions' AND `category` LIKE 'Institution General Reports' AND `name` LIKE 'Custom Field';

UPDATE `batch_reports` SET `query` = "$this->InstitutionSiteCustomField->unbindModel(array('hasMany' => array('InstitutionSiteCustomFieldOption')));
$this->InstitutionSiteCustomValue->bindModel(array(
           'belongsTo'=> array(
            	'InstitutionSiteCustomFieldOption' => array(
            		'joinTable'  => 'institution_custom_field_options',
                    'foreignKey' => false,
                    'conditions' => array(' InstitutionSiteCustomFieldOption.id = InstitutionSiteCustomValue.value ')
                ),
            )
));
$data = $this->InstitutionSiteCustomValue->find('all',array('fields'=>array('InstitutionSite.name AS InstitutionName','InstitutionSiteCustomField.name AS Information','InstitutionSiteCustomField.type AS Type','InstitutionSiteCustomValue.value AS Value','InstitutionSiteCustomFieldOption.value AS Option'),{cond}));foreach($data as $key => &$value) { if ($value['InstitutionSiteCustomField']['Type'] != 2 && $value['InstitutionSiteCustomField']['Type'] != 5) { $value['InstitutionSiteCustomValue']['Value'] = $value['InstitutionSiteCustomFieldOption']['Option']; }}" 
WHERE `report_id` = @customFieldsReportId;


SET @studentReportId := 0;

SELECT `id` INTO @studentReportId FROM `reports` WHERE `module` LIKE 'Institution Totals' AND `category` LIKE 'Institution Totals Reports' AND `name` LIKE 'Student';

UPDATE `batch_reports` SET `query` = "$data = $this->CensusStudent->find('all',array('fields'=>array('SchoolYear.name AS AcademicYear','InstitutionSite.name AS InstitutionName','EducationGrade.name AS EducationGradeName','StudentCategory.name AS Category','CensusStudent.male AS Male','CensusStudent.female AS Female'),{cond}));",
`template` = "AcademicYear,InstitutionName,EducationGradeName,Category,Male,Female" 
WHERE `report_id` = @studentReportId;
