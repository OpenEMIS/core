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
use App\Model\Table\AppTable;
use PDOException;

class StudentsTable extends AppTable
{
    const NO_FILTER = 0;
    const NO_STUDENT = 1;
    const NO_STAFF = 2;
    private $_dynamicFieldName = 'custom_field_data';
    public function initialize(array $config)
    {
        $this->table('security_users');
        parent::initialize($config);

        $this->belongsTo('Genders', ['className' => 'User.Genders']);
        $this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
        $this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);

        $this->belongsTo('MainNationalities', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);
        $this->belongsTo('MainIdentityTypes', ['className' => 'FieldOption.IdentityTypes', 'foreignKey' => 'identity_type_id']);
        $this->belongsTo('AreaLevels', ['className' => 'AreaLevel.AreaLevels']);

        $this->belongsTo('Areas', ['className' => 'Area.Areas']);
        $this->belongsTo('AreaAdministratives', ['className' => 'Area.AreaAdministratives']);

        $this->addBehavior('Excel', [
            'excludes' => ['is_student', 'photo_name', 'is_staff', 'is_guardian',  'super_admin', 'status'],
            'pages' => false,
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
        /*$this->addBehavior('Report.CustomFieldList', [
            'model' => 'Student.Students',
            'formFilterClass' => null,
            'fieldValueClass' => ['className' => 'StudentCustomField.StudentCustomFieldValues', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true],
            'tableCellClass' => ['className' => 'StudentCustomField.StudentCustomTableCells', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']
        ]);*/
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
            //->notEmpty('institution_type_id')
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
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
        $this->ControllerAction->field('area_level_id', ['type' => 'hidden']);
        $this->ControllerAction->field('area_education_id', ['type' => 'hidden', 'attr' => ['required' => true]]);
        $this->ControllerAction->field('institution_type_id', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_id', ['type' => 'hidden']);
        $this->ControllerAction->field('start_date',['type'=>'hidden']);
        $this->ControllerAction->field('end_date',['type'=>'hidden']);
        $this->ControllerAction->field('education_grade_id', ['type' => 'hidden']);
        $this->ControllerAction->field('education_subject_id', ['type' => 'hidden']);
        $this->ControllerAction->field('risk_id', ['type' => 'hidden']);
        $this->ControllerAction->field('risk_type', ['type' => 'hidden']);
        $this->ControllerAction->field('health_report_type', ['type' => 'hidden']);
        $this->ControllerAction->field('format');
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
        /*POCOR-6176 starts*/
        if (!(isset($this->request->data[$this->alias()]['feature']))) {
                $option = $attr['options'];
                reset($option);
                $this->request->data[$this->alias()]['feature'] = key($option);
        }
        /*POCORO-6176 ends*/
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
                    'Summary' => __('Summary'),
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

    function array_flatten($array) {
        if (!is_array($array)) {
          return false;
        }
        $result = array();
        foreach ($array as $key => $value) {
          if (is_array($value)) {
            $result = array_merge($result, $this->array_flatten($value));
          } else {
            $result = array_merge($result, array($key => $value));
          }
        }
        return $result;
      }

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        $areaId = $request->data[$this->alias()]['area_education_id'];
        $InstitutionsTable = TableRegistry::get('Institution.Institutions');
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];

            if (in_array($feature, ['Report.BodyMassStatusReports',
                                    'Report.HealthReports',
                                    'Report.StudentsRiskAssessment',
                                    'Report.SubjectsBookLists',
                                    'Report.StudentNotAssignedClass',
                                    'Report.SpecialNeeds',
                                    'Report.StudentGuardians','Report.StudentsPhoto','Report.Students',
                'Report.StudentIdentities','Report.StudentContacts','Report.StudentsEnrollmentSummary'
                  ])) {


                $institutionList = [];
                if (array_key_exists('institution_type_id', $request->data[$this->alias()]) && !empty($request->data[$this->alias()]['institution_type_id'])) {
                    $institutionTypeId = $request->data[$this->alias()]['institution_type_id'];
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
                } elseif (!$institutionTypeId && array_key_exists('area_education_id', $request->data[$this->alias()]) && !empty($request->data[$this->alias()]['area_education_id']) && $areaId != -1) {
                    $institutionQuery = $InstitutionsTable
                        ->find('list', [
                            'keyField' => 'id',
                            'valueField' => 'code_name'
                        ])
                        ->where([
                            $InstitutionsTable->aliasField('area_id') => $areaId
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
                        'Report.StudentGuardians',
                        'Report.Students',
                        'Report.StudentsPhoto',
                        'Report.StudentContacts',
                        'Report.StudentsEnrollmentSummary',
                        'Report.StudentIdentities',
                        'Report.HealthReports'
                    ]) && count($institutionList) > 1) {
                        $institutionOptions = ['' => '-- ' . __('Select') . ' --', '0' => __('All Institutions')] + $institutionList;
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


    public function onExcelBeforeQuery (Event $event, ArrayObject $settings, Query $query) {

        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $areaId = $requestData->area_education_id;
        $institutionId = $requestData->institution_id;
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $enrolled = $StudentStatuses->getIdByCode('CURRENT');
        $conditions = [];
        if ($areaId != -1) {
            $conditions['Institution.area_id'] = $areaId;
        }
        if (!empty($academicPeriodId)) {
            $conditions['InstitutionStudent.academic_period_id'] = $academicPeriodId;
        }
        if (!empty($institutionId) && $institutionId > 0) {
            $conditions['InstitutionStudent.institution_id'] = $institutionId;
        }
        if (!empty($enrolled)) {
            $conditions['InstitutionStudent.student_status_id'] = $enrolled;
        }
        $query->join([
            'InstitutionStudent' => [
                'type' => 'inner',
                'table' => 'institution_students', 
                'conditions' => [
                    'InstitutionStudent.student_id = '.$this->aliasField('id')
                ],
            ],
            'Institution' => [
                'type' => 'inner',
                'table' => 'institutions',
                'conditions' => [
                    'Institution.id = InstitutionStudent.institution_id'
                ]
            ],
            'InstitutionTypes' => [
                'type' => 'inner',
                'table' => 'institution_types',
                'conditions' => [
                    'InstitutionTypes.id = Institution.institution_type_id'
                ]
            ],
            'Localities' => [
                'type' => 'inner',
                'table' => 'institution_localities',
                'conditions' => [
                    'Localities.id = Institution.institution_locality_id'
                ]
            ],
            'Areas' => [
                'type' => 'inner',
                'table' => 'areas',
                'conditions' => [
                    'Areas.id = Institution.area_id'
                ]
            ],
            'AreaAdministratives' => [
                'type' => 'inner',
                'table' => 'area_administratives',
                'conditions' => [
                    'AreaAdministratives.id = Institution.area_administrative_id'
                ]
            ],
        ]);
        $query->select([
            'student_id' => 'Students.id',
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
            'EndDate' => 'InstitutionStudent.end_date',
            'institution_name' => 'Institution.name',
            'institution_type' => 'InstitutionTypes.name',
            'institution_id' => 'InstitutionTypes.id',
            'institution_localities' => 'Localities.name',
            'area_administratives'=> 'AreaAdministratives.name',
            'area_education'=> 'Areas.name',
            'external_reference' => 'Students.external_reference'
        ])
        ->contain(['Genders', 'AddressAreas', 'BirthplaceAreas', 'MainNationalities', 'MainIdentityTypes'])
        ->where([$this->aliasField('is_student') => 1, $conditions])
        ->group([$this->aliasField('openemis_no')]);


         $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                // POCOR-6338 starts
                
                $Users = TableRegistry::get('security_users');
                $institutionStudents = TableRegistry::get('institution_students');
               

                //$row['student_status'] = $user_data->student_status;
                // POCOR-6338 ends                
                // POCOR-6129 custome fields code
                    
                $Guardians = TableRegistry::get('student_custom_field_values');
                $studentCustomFieldOptions = TableRegistry::get('student_custom_field_options');
                $studentCustomFields = TableRegistry::get('student_custom_fields');

                $guardianData = $Guardians->find()
                ->select([
                    'id'                             => $Guardians->aliasField('id'),
                    'student_id'                     => $Guardians->aliasField('student_id'),
                    'student_custom_field_id'        => $Guardians->aliasField('student_custom_field_id'),
                    'text_value'                     => $Guardians->aliasField('text_value'),
                    'number_value'                   => $Guardians->aliasField('number_value'),
                    'decimal_value'                  => $Guardians->aliasField('decimal_value'),
                    'textarea_value'                 => $Guardians->aliasField('textarea_value'),
                    'date_value'                     => $Guardians->aliasField('date_value'),
                    'time_value'                     => $Guardians->aliasField('time_value'),
                    'checkbox_value_text'            => 'studentCustomFieldOptions.name',
                    'question_name'                  => 'studentCustomField.name',
                    'field_type'                     => 'studentCustomField.field_type',
                    'field_description'              => 'studentCustomField.description',
                    'question_field_type'            => 'studentCustomField.field_type',
                ])->leftJoin(
                    ['studentCustomField' => 'student_custom_fields'],
                    [
                        'studentCustomField.id = '.$Guardians->aliasField('student_custom_field_id')
                    ]
                )->leftJoin(
                    ['studentCustomFieldOptions' => 'student_custom_field_options'],
                    [
                        'studentCustomFieldOptions.id = '.$Guardians->aliasField('number_value')
                    ]
                )
                ->where([
                    $Guardians->aliasField('student_id') => $row['student_id'],
                ])->toArray();

                $existingCheckboxValue = '';
                foreach ($guardianData as $guadionRow) {
                    $fieldType = $guadionRow->field_type;
                    if ($fieldType == 'TEXT') {
                        $row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id] = $guadionRow->text_value;
                    } else if ($fieldType == 'CHECKBOX') {
                        $existingCheckboxValue = trim($row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id], ',') .','. $guadionRow->checkbox_value_text;
                        $row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id] = trim($existingCheckboxValue, ',');
                    } else if ($fieldType == 'NUMBER') {
                        $row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id] = $guadionRow->number_value;
                    } else if ($fieldType == 'DECIMAL') {
                        $row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id] = $guadionRow->decimal_value;
                    } else if ($fieldType == 'TEXTAREA') {
                        $row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id] = $guadionRow->textarea_value;
                    } else if ($fieldType == 'DROPDOWN') {
                        $row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id] = $guadionRow->checkbox_value_text;
                    } else if ($fieldType == 'DATE') {
                        $row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id] = date('Y-m-d', strtotime($guadionRow->date_value));
                    } else if ($fieldType == 'TIME') {
                        $row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id] = date('h:i A', strtotime($guadionRow->time_value));
                    } else if ($fieldType == 'COORDINATES') {
                        $row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id] = $guadionRow->text_value;
                    } else if ($fieldType == 'NOTE') {
                        $row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id] = $guadionRow->field_description;
                    }
                }
                // POCOR-6129 custome fields code

                return $row;
            });
        });
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) {
        $IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
        $identity = $IdentityType->getDefaultEntity();

        $settings['identity'] = $identity;

        $extraField[] = [
            'key' => 'Students.username',
            'field' => 'username',
            'type' => 'string',
            'label' => 'Username',
        ];

        $extraField[] = [
            'key' => 'Students.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => 'OpenEMIS ID',
        ];

        $extraField[] = [
            'key' => 'Students.first_name',
            'field' => 'first_name',
            'type' => 'string',
            'label' => 'First Name',
        ];

        $extraField[] = [
            'key' => 'Students.middle_name',
            'field' => 'middle_name',
            'type' => 'string',
            'label' => 'Middle Name',
        ];

        $extraField[] = [
            'key' => 'Students.third_name',
            'field' => 'third_name',
            'type' => 'string',
            'label' => 'Third Name',
        ];

        $extraField[] = [
            'key' => 'Students.last_name',
            'field' => 'last_name',
            'type' => 'string',
            'label' => 'Last Name',
        ];

        $extraField[] = [
            'key' => 'Students.preferred_name',
            'field' => 'preferred_name',
            'type' => 'string',
            'label' => 'Preferred Name',
        ];

        $extraField[] = [
            'key' => 'Students.email',
            'field' => 'email',
            'type' => 'string',
            'label' => 'Email',
        ];

        $extraField[] = [
            'key' => 'Students.address',
            'field' => 'address',
            'type' => 'string',
            'label' => 'Address',
        ];

        $extraField[] = [
            'key' => 'Students.postal_code',
            'field' => 'postal_code',
            'type' => 'string',
            'label' => 'Postal Code',
        ];

        $extraField[] = [
            'key' => 'AddressAreas.name',
            'field' => 'address_area',
            'type' => 'string',
            'label' => 'Address Area',
        ];


        $extraField[] = [
            'key' => 'BirthplaceAreas.name',
            'field' => 'birthplace_area',
            'type' => 'string',
            'label' => 'Birthplace Area',
        ];

        $extraField[] = [
            'key' => 'Genders.name',
            'field' => 'gender',
            'type' => 'string',
            'label' => 'Gender',
        ];

        $extraField[] = [
            'key' => 'Students.date_of_birth',
            'field' => 'date_of_birth',
            'type' => 'date',
            'label' => 'Date Of Birth',
        ];

        $extraField[] = [
            'key' => 'Students.date_of_death',
            'field' => 'date_of_death',
            'type' => 'string',
            'label' => 'Date Of Death',
        ];

        $extraField[] = [
            'key' => 'MainNationalities.name',
            'field' => 'nationality_name',
            'type' => 'string',
            'label' => 'Main Nationality',
        ];

        $extraField[] = [
            'key' => 'MainIdentityTypes.name',
            'field' => 'identity_type',
            'type' => 'string',
            'label' => 'Main Identity Type',
        ];

        $extraField[] = [
            'key' => 'Students.identity_number',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => 'Identity Number',
        ];

        $extraField[] = [
            'key' => 'Institution.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => 'Institution Name',
        ];

        $extraField[] = [
            'key' => 'InstitutionTypes.name',
            'field' => 'institution_type',
            'type' => 'string',
            'label' => 'Institution Type',
        ];

        $extraField[] = [
            'key' => 'Localities.name',
            'field' => 'institution_localities',
            'type' => 'string',
            'label' => 'Institution Locality',
        ];

        $extraField[] = [
            'key' => 'Areas.name',
            'field' => 'area_education',
            'type' => 'string',
            'label' => 'Area Education',
        ];

        $extraField[] = [
            'key' => 'AreaAdministratives.name',
            'field' => 'area_administratives',
            'type' => 'string',
            'label' => 'Area Administration',
        ];


        $extraField[] = [
            'key' => 'Students.external_reference',
            'field' => 'external_reference',
            'type' => 'string',
            'label' => 'External Reference',
        ];

        $extraField[] = [
            'key' => 'Students.last_login',
            'field' => 'external_reference',
            'type' => 'string',
            'label' => 'Last Login',
        ];

        $extraField[] = [
            'key' => 'Students.preferred_language',
            'field' => 'preferred_language',
            'type' => 'string',
            'label' => 'Preferred Language',
        ];
        $InfrastructureCustomFields = TableRegistry::get('student_custom_fields');
        $customFieldData = $InfrastructureCustomFields->find()->select([
            'custom_field_id' => $InfrastructureCustomFields->aliasfield('id'),
            'custom_field' => $InfrastructureCustomFields->aliasfield('name')
        ])->group($InfrastructureCustomFields->aliasfield('id'))->toArray();


        if(!empty($customFieldData)) {
            foreach($customFieldData as $data) {
                $custom_field_id = $data->custom_field_id;
                $custom_field = $data->custom_field;
                $extraField[] = [
                    'key' => '',
                    'field' => $this->_dynamicFieldName.'_'.$custom_field_id,
                    'type' => 'string',
                    'label' => __($custom_field)
                ];
            }
        }
        // POCOR-6129 custome fields code
        //print_r($extraField); exit;
        $fields->exchangeArray($extraField);
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
                                      'Report.SpecialNeeds',
                                      'Report.InstitutionStudentsOutOfSchool',
                                        'Report.StudentsPhoto',
                'Report.Students',
                'Report.StudentIdentities','Report.StudentContacts'

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
                                       'Report.SubjectsBookLists',
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

    public function onUpdateFieldAreaLevelId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];

            if ((in_array($feature, ['Report.StudentsPhoto',
                'Report.Students',
                'Report.StudentIdentities',
                'Report.StudentContacts',
                'Report.HealthReports',
                'Report.BodyMassStatusReports',
                'Report.StudentsRiskAssessment',
                'Report.SubjectsBookLists',
                'Report.StudentNotAssignedClass',
                'Report.StudentsEnrollmentSummary',
                'Report.SpecialNeeds'
            ]))) {
                $Areas = TableRegistry::get('AreaLevel.AreaLevels');
                $entity = $attr['entity'];

                if ($action == 'add') {
                    $areaOptions = $Areas
                        ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                        ->order([$Areas->aliasField('level')]);

                    $attr['type'] = 'chosenSelect';
                    $attr['attr']['multiple'] = false;
                    $attr['select'] = true;
                    $attr['options'] = ['' => '-- ' . _('Select') . ' --', '-1' => _('All Areas Level')] + $areaOptions->toArray();
                    $attr['onChangeReload'] = true;
                } else {
                    $attr['type'] = 'hidden';
                }
            }
        }
        return $attr;
    }

    public function onUpdateFieldAreaEducationId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            $areaLevelId = $this->request->data[$this->alias()]['area_level_id'];//POCOR-6333
            if (in_array($feature, ['Report.StudentsEnrollmentSummary',
                'Report.StudentsPhoto',
                'Report.Students',
                'Report.StudentIdentities',
                'Report.StudentContacts',
                'Report.HealthReports',
                'Report.BodyMassStatusReports',
                'Report.StudentsRiskAssessment',
                'Report.SubjectsBookLists',
                'Report.StudentNotAssignedClass',
                'Report.StudentsEnrollmentSummary','Report.SpecialNeeds'])) {
                    $Areas = TableRegistry::get('Area.Areas');
                    $entity = $attr['entity'];

                    if ($action == 'add') {
                        $where = [];
                        if ($areaLevelId != -1) {
                            $where[$Areas->aliasField('area_level_id')] = $areaLevelId;
                        }
                        $areas = $Areas
                            ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                            ->where([$where])
                            ->order([$Areas->aliasField('order')]);
                        $areaOptions = $areas->toArray();
                        $attr['type'] = 'chosenSelect';
                        $attr['attr']['multiple'] = false;
                        $attr['select'] = true;
                        /*POCOR-6333 starts*/
                        if (count($areaOptions) > 1) {
                            $attr['options'] = ['' => '-- ' . _('Select') . ' --', '-1' => _('All Areas')] + $areaOptions;
                        } else {
                            $attr['options'] = ['' => '-- ' . _('Select') . ' --'] + $areaOptions;
                        }
                        /*POCOR-6333 ends*/
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
                    ->contain(['EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
                    ->where([
                        'EducationSystems.academic_period_id' => $academicPeriodId,
                    ])
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

            if (in_array($feature, [
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
