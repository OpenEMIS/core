<?php
namespace Report\Model\Table;

use ArrayObject;
use ZipArchive;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;;

class StudentsTable extends AppTable
{
	const NO_FILTER = 0;
    const NO_STUDENT = 1;
    const NO_STAFF = 2;
	
    public function initialize(array $config)
    {
        $this->table('security_users');
        parent::initialize($config);

        $this->belongsTo('Genders', ['className' => 'User.Genders']);
        $this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
        $this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);

        $this->belongsTo('MainNationalities', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);
        $this->belongsTo('MainIdentityTypes', ['className' => 'FieldOption.IdentityTypes', 'foreignKey' => 'identity_type_id']);

        $this->addBehavior('Excel', [
            'excludes' => ['is_student', 'photo_name', 'is_staff', 'is_guardian',  'super_admin', 'status'],
            'pages' => false,
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.CustomFieldList', [
            'model' => 'Student.Students',
            'formFilterClass' => null,
            'fieldValueClass' => ['className' => 'StudentCustomField.StudentCustomFieldValues', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true],
            'tableCellClass' => ['className' => 'StudentCustomField.StudentCustomTableCells', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']
        ]);
    }


    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.downloadAll'] = 'downloadAll';
        return $events;
    }

    public function validationSubjectsBookLists(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->notEmpty('institution_type_id')
            ->notEmpty('institution_id');
        return $validator;
    }
	
   public function validationStudentNotAssignedClass(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->notEmpty('institution_type_id')
            ->notEmpty('institution_id');
        return $validator;
    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('start_date',['type'=>'hidden']);
        $this->ControllerAction->field('end_date',['type'=>'hidden']);
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_type_id', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_id', ['type' => 'hidden']);
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
        //pocor 5863 start
        $this->ControllerAction->field('area_education_id', ['type' => 'hidden', 'attr' => ['required' => true]]);
        //pocor 5863 end
        $this->ControllerAction->field('format');
        $this->ControllerAction->field('institution_type_id', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_id', ['type' => 'hidden']);
        $this->ControllerAction->field('education_grade_id', ['type' => 'hidden']);
        $this->ControllerAction->field('education_subject_id', ['type' => 'hidden']);
        $this->ControllerAction->field('risk_id', ['type' => 'hidden']);
    }

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {   
        if ($data[$this->alias()]['feature'] == 'Report.StudentsEnrollmentSummary') {
            $options['validate'] = 'StudentsEnrollmentSummary';
        }
        if ($data[$this->alias()]['feature'] == 'Report.BodyMassStatusReports') {
            $options['validate'] = 'BodyMassStatusReports';
        } else if ($data[$this->alias()]['feature'] == 'Report.HealthReports') {
            $options['validate'] = 'HealthReports';
        }else if ($data[$this->alias()]['feature'] == 'Report.StudentsRiskAssessment') {
			$options['validate'] = 'StudentsRiskAssessment';
        } else if ($data[$this->alias()]['feature'] == 'Report.SubjectsBookLists') {
            $options['validate'] = 'SubjectsBookLists';
        } else if ($data[$this->alias()]['feature'] == 'Report.StudentNotAssignedClass') {
            $options['validate'] = 'StudentNotAssignedClass';
        }

    }

    public function addBeforeAction(Event $event)
    {
        $this->ControllerAction->field('institution_filter', ['type' => 'hidden']);
        $this->ControllerAction->field('position_filter', ['type' => 'hidden']);       
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
        //pocor 5863 start
         $this->ControllerAction->field('area_education_id', ['type' => 'hidden', 'attr' => ['required' => true]]);
        //pocor 5863 end
        $this->ControllerAction->field('institution_type_id', ['type' => 'hidden']);
		$this->ControllerAction->field('risk_type', ['type' => 'hidden']); 
        $this->ControllerAction->field('institution_id', ['type' => 'hidden']); 
        $this->ControllerAction->field('health_report_type', ['type' => 'hidden']); 
    }

   

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        $attr['options'] = $this->controller->getFeatureOptions($this->alias());
        $attr['onChangeReload'] = true;
		
		return $attr;
    }

     public function validationStudentsEnrollmentSummary(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->notEmpty('academic_period_id')
            ->notEmpty('area_education_id');
        return $validator;
    }
    
    public function validationHealthReports(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->notEmpty('institution_id');
        return $validator;
    }
	public function validationStudentsRiskAssessment(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
        ->notEmpty('institution_id');
        return $validator;
    }
    
    public function validationBodyMassStatusReports(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->notEmpty('institution_id');
        return $validator;
    }
	
	public function onUpdateFieldRiskType(Event $event, array $attr, $action, Request $request)
	{
		if (isset($request->data[$this->alias()]['feature'])) {
			$feature = $this->request->data[$this->alias()]['feature'];

			if ((in_array($feature, ['Report.StudentsRiskAssessment']))
			) {
				$AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
				$academicPeriodId = $AcademicPeriodTable->getCurrent();

				if (!empty($request->data[$this->alias()]['academic_period_id'])) {
				$academicPeriodId = $request->data[$this->alias()]['academic_period_id'];
				}

				$RiskTable = TableRegistry::get('Institution.Risks');
				$riskOptions = [];
				$riskOptions = $RiskTable->find('list', [
				'keyField' => 'id',
				'valueField' => 'name'
				])->where(['academic_period_id' => $academicPeriodId])->toArray();
				
				$attr['options'] = $riskOptions;
				$attr['type'] = 'select';
				$attr['select'] = false;
				$attr['onChangeReload'] = true;

				return $attr;
			}
		}
	}
    
    public function onUpdateFieldHealthReportType(Event $event, array $attr, $action, Request $request){
        if (isset($request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
			
            if ((in_array($feature, ['Report.HealthReports']))
                ) {
                //POCOR-5890 starts
                $healthReportTypeOptions = [
                    'Overview' => __('Overview'),
                    'Allergies' => __('Allergies'),
                    'Consultations' => __('Consultations'),
                    'Families' => __('Families'),
                    'Histories' => __('Histories'),
                    'Immunizations' => __('Vaccinations'),//POCOR-5890
                    'Medications' => __('Medications'),
                    'Tests' => __('Tests'),
                    'Insurance' => __('Insurance'),
                ];
                //POCOR-5890 ends
                $attr['options'] = $healthReportTypeOptions;
                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['onChangeReload'] = true;
                
                return $attr;
            }
        }
    }

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];

            if (in_array($feature, ['Report.BodyMassStatusReports',
                                    'Report.HealthReports',
									'Report.StudentsRiskAssessment',
									'Report.SubjectsBookLists',
									'Report.StudentNotAssignedClass',
                                    'Report.SpecialNeeds',
                                    'Report.StudentGuardians'
				  ])) {

 
                $institutionList = [];
                if (array_key_exists('institution_type_id', $request->data[$this->alias()]) && !empty($request->data[$this->alias()]['institution_type_id'])) {
                    $institutionTypeId = $request->data[$this->alias()]['institution_type_id'];

                    $InstitutionsTable = TableRegistry::get('Institution.Institutions');
                    $institutionQuery = $InstitutionsTable
                        ->find('list', [
                            'keyField' => 'id',
                            'valueField' => 'code_name'
                        ])
                        ->where([
                            $InstitutionsTable->aliasField('institution_type_id') => $institutionTypeId
                        ])
                        ->order([
                            $InstitutionsTable->aliasField('code') => 'ASC',
                            $InstitutionsTable->aliasField('name') => 'ASC'
                        ]);

                    $superAdmin = $this->Auth->user('super_admin');
                    if (!$superAdmin) { // if user is not super admin, the list will be filtered
                        $userId = $this->Auth->user('id');
                        $institutionQuery->find('byAccess', ['userId' => $userId]);
                    }

                    $institutionList = $institutionQuery->toArray();
                } else {
					
                   $InstitutionsTable = TableRegistry::get('Institution.Institutions');
                    $institutionQuery = $InstitutionsTable
                        ->find('list', [
                           'keyField' => 'id',
                            'valueField' => 'code_name'
                        ])
                        ->order([
                           $InstitutionsTable->aliasField('code') => 'ASC',
                            $InstitutionsTable->aliasField('name') => 'ASC'
                        ]);

                    $superAdmin = $this->Auth->user('super_admin');
                    if (!$superAdmin) { // if user is not super admin, the list will be filtered
                        $userId = $this->Auth->user('id');
                        $institutionQuery->find('byAccess', ['userId' => $userId]);
                    }

                    $institutionList = $institutionQuery->toArray();
                }

                if (empty($institutionList)) {
                    $institutionOptions = ['' => $this->getMessage('general.select.noOptions')];
                    $attr['type'] = 'select';
                    $attr['options'] = $institutionOptions;
                    $attr['attr']['required'] = true;
                } else {
					
                    if (in_array($feature, [
						'Report.BodyMassStatusReports',
						'Report.StudentsRiskAssessment',
						'Report.SubjectsBookLists',
						'Report.StudentNotAssignedClass',
                        'Report.SpecialNeeds',
                        'Report.StudentGuardians'
					])) {
                        $institutionOptions = ['' => '-- ' . __('Select') . ' --', '0' => __('All Institutions')] + $institutionList;
                    }elseif (in_array($feature, ['Report.HealthReports'])) {
                        $institutionOptions = ['' => '-- ' . __('Select') . ' --', '0' => __('All Institutions'),'-1' => __('No Institutions')] + $institutionList;
                    } else {
                        $institutionOptions = ['' => '-- ' . __('Select') . ' --'] + $institutionList;
                    }

                    $attr['type'] = 'chosenSelect';
                    $attr['onChangeReload'] = true;
                    $attr['attr']['multiple'] = false;
                    $attr['options'] = $institutionOptions;
                    $attr['attr']['required'] = true;
                }
            }
            return $attr;
        }
    }

   
    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
      
        $query
            ->select([
                'username' => 'Students.username',
                'openemis_no' => 'Students.openemis_no',
                'first_name' => 'Students.first_name',
                'middle_name' => 'Students.middle_name',
                'third_name' => 'Students.third_name',
                'last_name' => 'Students.last_name',
                'preferred_name' => 'Students.preferred_name',
                'email' => 'Students.email',
                'address' => 'Students.address',
                'postal_code' => 'Students.postal_code',
                'address_area' => 'AddressAreas.name',
                'birthplace_area' => 'BirthplaceAreas.name',
                'gender' => 'Genders.name',
                'date_of_birth' => 'Students.date_of_birth',
                'date_of_death' => 'Students.date_of_death',
                'nationality_name' => 'MainNationalities.name',
                'identity_type' => 'MainIdentityTypes.name',
                'identity_number' => 'Students.identity_number',
                'external_reference' => 'Students.external_reference',
                'last_login' => 'Students.last_login',
                'preferred_language' => 'Students.preferred_language',
             ])
            ->contain(['Genders', 'AddressAreas', 'BirthplaceAreas', 'MainNationalities', 'MainIdentityTypes'])
            ->where([$this->aliasField('is_student') => 1]);
            
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) 
    {
        
        foreach ($fields as $key => $field) { 
            if ($field['field'] == 'identity_type_id') { 
                $fields[$key] = [
                    'key' => 'MainIdentityTypes.name',
                    'field' => 'identity_type',
                    'type' => 'string',
                    'label' => __('Main Identity Type')
                ];
            }

            if ($field['field'] == 'nationality_id') { 
                $fields[$key] = [
                    'key' => 'MainNationalities.name',
                    'field' => 'nationality_name',
                    'type' => 'string',
                    'label' => __('Main Nationality')
                ];
            }

            if ($field['field'] == 'address_area_id') { 
                $fields[$key] = [
                    'key' => 'AddressAreas.name',
                    'field' => 'address_area',
                    'type' => 'string',
                    'label' => __('Address Area')
                ];
            }

            if ($field['field'] == 'birthplace_area_id') { 
                $fields[$key] = [
                    'key' => 'BirthplaceAreas.name',
                    'field' => 'birthplace_area',
                    'type' => 'string',
                    'label' => __('Birthplace Area')
                ];
            }

            if ($field['field'] == 'gender_id') { 
                $fields[$key] = [
                    'key' => 'Genders.name',
                    'field' => 'gender',
                    'type' => 'string',
                    'label' => __('Gender')
                ];
            }
        }
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
			
            if ((in_array($feature, ['Report.BodyMassStatusReports',
			                          'Report.HealthReports', 
									  'Report.StudentsRiskAssessment',
									  'Report.SubjectsBookLists',
									  'Report.StudentNotAssignedClass',
                                      'Report.StudentsEnrollmentSummary',
                                      'Report.SpecialNeeds'
									  
									  ])
			)) {
                $AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
                $academicPeriodOptions = $AcademicPeriodTable->getYearList();
                $currentPeriod = $AcademicPeriodTable->getCurrent();

                $attr['options'] = $academicPeriodOptions;
                $attr['type'] = 'select';
                $attr['select'] = false;
				
                if (in_array($feature, ['Report.StudentsRiskAssessment',
									   'Report.ClassAttendanceNotMarkedRecords', 
									   'Report.InstitutionCases',
									   'Report.StudentAttendanceSummary',
									   'Report.StaffAttendances',
                                       'Report.StudentsEnrollmentSummary',
                                      'Report.SpecialNeeds'])
				) {
                    $attr['onChangeReload'] = true;
                }

                if (empty($request->data[$this->alias()]['academic_period_id'])) {
                    $request->data[$this->alias()]['academic_period_id'] = $currentPeriod;
                }
                return $attr;
            }
        }
    }

    public function onUpdateFieldAreaEducationId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];

            if (in_array($feature, ['Report.StudentsEnrollmentSummary'])) {
                    $Areas = TableRegistry::get('Area.Areas');
                    $entity = $attr['entity'];

                    if ($action == 'add') {
                        $areaOptions = $Areas
                            ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                            ->order([$Areas->aliasField('order')]);

                        $attr['type'] = 'chosenSelect';
                        $attr['attr']['multiple'] = false;
                        $attr['select'] = true;
                        $attr['options'] = ['' => '-- ' . _('Select') . ' --', '-1' => _('All Areas')] + $areaOptions->toArray();
                        $attr['onChangeReload'] = true;
                    } else {
                        $attr['type'] = 'hidden';
                    }
            }
        }
        return $attr;
    }
    
    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['academic_period_id'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            $academicPeriodId = $this->request->data[$this->alias()]['academic_period_id'];
            if (in_array($feature, 
                        [
                            'Report.ClassAttendanceNotMarkedRecords',
                            'Report.SubjectsBookLists'
                        ])
                ) {
                
                $EducationGrades = TableRegistry::get('Education.EducationGrades');
                $gradeOptions = $EducationGrades
                    ->find('list', [
                        'keyField' => 'id',
                        'valueField' => 'name'
                    ])
                    ->select([
                        'id' => $EducationGrades->aliasField('id'),
                        'name' => $EducationGrades->aliasField('name'),
                        'education_programme_name' => 'EducationProgrammes.name'
                    ])
                    ->contain(['EducationProgrammes'])
                    ->order([
                        'EducationProgrammes.order' => 'ASC',
                        $EducationGrades->aliasField('name') => 'ASC'
                    ])
                    ->toArray();
                //POCOR-5740 starts
                if (in_array($feature, ['Report.SubjectsBookLists'])) {
                    $attr['onChangeReload'] = true;
                } //POCOR-5740 ends   
                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['options'] = ['-1' => __('All Grades')] + $gradeOptions;
            } elseif (in_array($feature,
                               [
                                   'Report.StudentAttendanceSummary'
                               ])
                      ) {
                $gradeList = [];
                if (array_key_exists('institution_id', $request->data[$this->alias()]) && !empty($request->data[$this->alias()]['institution_id']) && array_key_exists('academic_period_id', $request->data[$this->alias()]) && !empty($request->data[$this->alias()]['academic_period_id'])) {
                    $institutionId = $request->data[$this->alias()]['institution_id'];
                    $academicPeriodId = $request->data[$this->alias()]['academic_period_id'];

                    $InstitutionGradesTable = TableRegistry::get('Institution.InstitutionGrades');
                    $gradeList = $InstitutionGradesTable->getGradeOptions($institutionId, $academicPeriodId);
                }

                if (empty($gradeList)) {
                    $gradeOptions = ['' => $this->getMessage('general.select.noOptions')];
                } else {
                    $gradeOptions = ['-1' => __('All Grades')] + $gradeList;
                }

                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['options'] = $gradeOptions;
                $attr['attr']['required'] = true;
            } else {
                $attr['value'] = self::NO_FILTER;
            }
            return $attr;
        }
    }

    
    public function onUpdateFieldInstitutionTypeId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
			
            if (in_array($feature, ['Report.SubjectsBookLists',
			  'Report.StudentNotAssignedClass'
			])) {
                
                $TypesTable = TableRegistry::get('Institution.Types');
                $typeOptions = $TypesTable
                    ->find('list')
                    ->find('visible')
                    ->find('order')
                    ->toArray();

                $attr['type'] = 'select';
                $attr['onChangeReload'] = true;

                if($feature == 'Report.StudentNotAssignedClass') {
                    $attr['options'] = ['0' => __('All Types')] +  $typeOptions;
                } else {
                    $attr['options'] = $typeOptions;
                }

                $attr['attr']['required'] = true;
            }
            return $attr;
        }
    }

   public function onUpdateFieldEducationSubjectId(Event $event, array $attr, $action, Request $request)
    {

        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, 
                        [
                            'Report.InstitutionSubjects'
                            //POCOR-5740 starts
                            //'Report.SubjectsBookLists'
                            //POCOR-5740 ends
                        ])
                ) {

                $EducationSubjects = TableRegistry::get('Education.EducationSubjects');
                $subjectOptions = $EducationSubjects
                    ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                    ->find('visible')
                    ->order([
                        $EducationSubjects->aliasField('order') => 'ASC'
                    ])
                    ->toArray();

                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['options'] = ['' => __('All Subjects')] + $subjectOptions;
            } elseif(in_array($feature, ['Report.SubjectsBookLists'])){ //POCOR-5740 starts
                
                $EducationGradesSubjects = TableRegistry::get('education_grades_subjects');
                $EducationSubjects = TableRegistry::get('Education.EducationSubjects');
                $subjectOptions = $EducationGradesSubjects
                                    ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                                    ->select([
                                        'education_subject_id' => $EducationGradesSubjects->aliasField('education_subject_id'),
                                        'education_grade_id' => $EducationGradesSubjects->aliasField('education_grade_id'),
                                        'id' => $EducationSubjects->aliasField('id'),
                                        'name' => $EducationSubjects->aliasField('name')
                                    ])
                                    ->leftJoin(
                                        [$EducationSubjects->alias() => $EducationSubjects->table()],
                                        [
                                            $EducationSubjects->aliasField('id = ') . $EducationGradesSubjects->aliasField('education_subject_id')
                                        ]
                                    )
                                    ->where([
                                        $EducationGradesSubjects->aliasField('education_grade_id') => $this->request->data[$this->alias()]['education_grade_id']
                                    ])
                                    ->order([
                                        $EducationSubjects->aliasField('order') => 'ASC'
                                    ])->toArray();
                $attr['type'] = 'select';
                $attr['select'] = false;

                if($this->request->data[$this->alias()]['education_grade_id'] == -1){ //for all grades
                    $attr['options'] = ['' => __('All Subjects')];
                }else{
                    $attr['options'] = $subjectOptions;
                }
                //POCOR-5740 ends
            } else {
                $attr['value'] = self::NO_FILTER;
            }
            return $attr;
        }
    }

    // public function onUpdateFieldRiskId(Event $event, array $attr, $action, Request $request)
    // {
        
    //     if (isset($this->request->data[$this->alias()]['feature'])) {
    //         $feature = $this->request->data[$this->alias()]['feature'];

    //         if (in_array($feature, ['Report.SpecialNeeds'])) {
    //             $InstitutionStudentRisks = TableRegistry::get('Institution.InstitutionStudentRisks');
    //             $Risks = TableRegistry::get('Risk.Risks');
    //             $academic_period_id = $request->data['Students']['academic_period_id'];
    //             $institution_id = $request->data['Students']['institution_id'];
    //             if ($institution_id != 0) {
    //                 $where = [$InstitutionStudentRisks->aliasField('institution_id') => $institution_id];
    //             } else {
    //                 $where = [];
    //             }
                
    //             $InstitutionStudentRisksData = $InstitutionStudentRisks
    //             ->find('list', [
    //                         'keyField' => $Risks->aliasField('id'),
    //                         'valueField' => $Risks->aliasField('name')
    //                     ])
    //             ->select([$Risks->aliasField('id'),
    //                 $Risks->aliasField('name')])
    //             ->leftJoin(
    //                 [$Risks->alias() => $Risks->table()],
    //                 [
    //                     $Risks->aliasField('id = ') . $InstitutionStudentRisks->aliasField('risk_id')
    //                 ]
    //             )
    //             ->where([$InstitutionStudentRisks->aliasField('academic_period_id') => $academic_period_id,
    //                 $where
    //                     ])
    //             ->toArray();
    //             if (empty($InstitutionStudentRisksData)) {
    //                 $noOptions = ['' => $this->getMessage('general.select.noOptions')];
    //                 $attr['type'] = 'select';
    //                 $attr['options'] = $noOptions;
    //             } else {
    //             $attr['options'] = $InstitutionStudentRisksData;
    //             $attr['type'] = 'select';
    //             $attr['select'] = false;                
    //             }
    //             return $attr;
    //         }
    //     }
    // }


    public function startStudentsPhotoDownload() {
            
        $cmd  = ROOT . DS . 'bin' . DS . 'cake StudentsPhotoDownload';
        $logs = ROOT . DS . 'logs' . DS . 'StudentsPhotoDownload.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
    }

    public function onUpdateFieldStartDate(Event $event, array $attr, $action, Request $request)
    {
        if (isset($request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
			
            if ((in_array($feature, ['Report.BodyMassStatusReports']))) {   
                $attr['type'] = 'date';
                return $attr;
            }
        }
    }

    public function onUpdateFieldEndDate(Event $event, array $attr, $action, Request $request)
    {
        if (isset($request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
			
            if ((in_array($feature, ['Report.BodyMassStatusReports']))) {
                $attr['type'] = 'date';
                return $attr;
            }
        }
    }
}
