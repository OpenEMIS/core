<?php
namespace Report\Model\Table;

use ArrayObject;
use ZipArchive;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use PDOException;
use Cake\Http\ServerRequest;

class StudentsTable extends AppTable
{
    const NO_FILTER = 0;
    const NO_STUDENT = 1;
    const NO_STAFF = 2;
    private $_dynamicFieldName = 'custom_field_data';
    public function initialize(array $config): void
    {
        $this->setTable('security_users');
        parent::initialize($config);

        $this->belongsTo('Genders', ['className' => 'User.Genders']);
        $this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
        $this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);

        $this->belongsTo('MainNationalities', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);
        $this->belongsTo('MainIdentityTypes', ['className' => 'FieldOption.IdentityTypes', 'foreignKey' => 'identity_type_id']);
        $this->belongsTo('AreaLevels', ['className' => 'Area.AreaLevels']);

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


    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.downloadAll'] = 'downloadAll';
        return $events;
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        $validator
            ->notEmpty('academic_period_id')
            ->notEmpty('area_level_id')
            ->notEmpty('area_education_id');
            return $validator;
    }

    public function validationSubjectsBookLists(Validator $validator): Validator
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            //->notEmpty('institution_type_id')
            ->notEmpty('institution_id');
        return $validator;
    }

   public function validationStudentNotAssignedClass(Validator $validator): Validator
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->notEmpty('institution_type_id')
            ->notEmpty('institution_id');
        return $validator;
    }

    public function beforeAction(EventInterface $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('special_needs_feature', ['type' => 'hidden']);   //POCOR-7552
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
        // $this->ControllerAction->field('report_for', ['type' => 'hidden']);   //POCOR-7467
        $this->ControllerAction->field('area_level_id', ['type' => 'hidden']);
        $this->ControllerAction->field('area_education_id', ['type' => 'hidden', 'attr' => ['required' => true]]);
        $this->ControllerAction->field('institution_type_id', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_id', ['type' => 'hidden']);
        $this->ControllerAction->field('report_start_date',['type'=>'hidden']);
        $this->ControllerAction->field('report_end_date',['type'=>'hidden']);
        $this->ControllerAction->field('start_date',['type'=>'hidden']);
        $this->ControllerAction->field('end_date',['type'=>'hidden']);
        $this->ControllerAction->field('education_programme_id', ['type' => 'hidden']); //POCOR-8868
        $this->ControllerAction->field('education_grade_id', ['type' => 'hidden']);
        $this->ControllerAction->field('education_subject_id', ['type' => 'hidden']);
        $this->ControllerAction->field('risk_id', ['type' => 'hidden']);
        $this->ControllerAction->field('risk_type', ['type' => 'hidden']);
        $this->ControllerAction->field('health_report_type', ['type' => 'hidden']);
        $this->ControllerAction->field('format');
    }



    // START POCOR-7552
    public function onUpdateFieldSpecialNeedsFeature(EventInterface $event, array $attr, $action, ServerRequest $request){
        if (isset($request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];

            if ((in_array($feature, ['Report.SpecialNeeds']))
                ) {
                $featureOptions = [
                    'referral' => __('Referrals'),
                    'assessments' => __('Assessments'),
                    'services' => __('Services'),
                    'devices' => __('Devices'),
                    'plans' => __('Plans'),
                    'diagnostics' => __('Diagnostics'),
                ];
                $attr['options'] = $featureOptions;
                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['onChangeReload'] = true;

                return $attr;
            }
        }
    }
    // END POCOR-7552

    public function addBeforePatch(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if ($data[$this->getAlias()]['feature'] == 'Report.StudentsEnrollmentSummary') {
            $options['validate'] = 'StudentsEnrollmentSummary';
        }
        if ($data[$this->getAlias()]['feature'] == 'Report.BodyMassStatusReports') {
            $options['validate'] = 'BodyMassStatusReports';
        } else if ($data[$this->getAlias()]['feature'] == 'Report.HealthReports') {
            $options['validate'] = 'HealthReports';
        }else if ($data[$this->getAlias()]['feature'] == 'Report.StudentsRiskAssessment') {
            $options['validate'] = 'StudentsRiskAssessment';
        } else if ($data[$this->getAlias()]['feature'] == 'Report.SubjectsBookLists') {
            $options['validate'] = 'SubjectsBookLists';
        } else if ($data[$this->getAlias()]['feature'] == 'Report.StudentNotAssignedClass') {
            $options['validate'] = 'StudentNotAssignedClass';
        }else if ($data[$this->getAlias()]['feature'] == 'Report.Students') {
            $options['validate'] = 'Students';
        } //POCOR-8417

    }

    public function addBeforeAction(EventInterface $event)
    {
        $this->ControllerAction->field('institution_filter', ['type' => 'hidden']);
        $this->ControllerAction->field('position_filter', ['type' => 'hidden']);
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
        //pocor 5863 start
         $this->ControllerAction->field('area_education_id', ['type' => 'hidden', 'attr' => ['label'=>'Area Name','required' => true]]);//POCOR - 7408
        //pocor 5863 end
        $this->ControllerAction->field('institution_type_id', ['type' => 'hidden']);
        $this->ControllerAction->field('risk_type', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_id', ['type' => 'hidden']);
        $this->ControllerAction->field('health_report_type', ['type' => 'hidden']);
    }

    public function addAfterAction(EventInterface $event, Entity $entity)
    {
        $fieldsOrder = ['feature'];
        if ($entity->has('feature')) {
            $feature = $entity->feature;
            switch ($feature) {
                case 'Report.Students':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'area_level_id';
                    $fieldsOrder[] = 'area_education_id';
                    $fieldsOrder[] = 'institution_id';
                    $fieldsOrder[] = 'institution_dropdown';
                    $fieldsOrder[] = 'format';
                    //custom element field
                    $this->ControllerAction->field('institution_dropdown', [
                        'type'   => 'element',
                        'element'=> 'institutiondropdown',
                    ]);

                    break;
            }
            $this->ControllerAction->setFieldOrder($fieldsOrder);
        }
    }

    public function onUpdateFieldFeature(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $options = $this->controller->getFeatureOptions($this->getAlias());
        $attr['options'] = $this->controller->getFeatureOptions($this->getAlias());
        $attr['onChangeReload'] = true;
        /*POCOR-6176 starts*/
        if (!(isset($this->request->getData($this->getAlias())['feature']))) {
                $option = $attr['options'];
                reset($option);
                $defaultFeatureValue = key($options);
                $this->request = $this->request->withData($this->getAlias() . '.feature', $defaultFeatureValue);
            }
        /*POCORO-6176 ends*/
        return $attr;
    }

    //POCOR-8417
    public function validationStudents(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        $validator
            ->notEmpty('academic_period_id')
            ->notEmpty('area_level_id')
            ->notEmpty('area_education_id');
        $validator->add('institution_id', 'required', [
                'rule' => function ($value, $context) {
                    if (!empty($context['data']['reload'])) {
                        return true;
                    }
                    if (empty($value) || !isset($value['_ids'])) {
                        return false;
                    }
                    $ids = (array)$value['_ids'];
                    $ids = array_filter($ids, function($v) {
                        return $v !== '' && $v !== null;
                    });

                    return !empty($ids);
                },
                'message' => __('This field cannot be left empty')
            ]);

        return $validator;
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

    public function onUpdateFieldRiskType(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];

            if ((in_array($feature, ['Report.StudentsRiskAssessment']))
            ) {
                $AcademicPeriodTable = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
                $academicPeriodId = $AcademicPeriodTable->getCurrent();

                if (!empty($request->getData($this->getAlias())['academic_period_id'])) {
                $academicPeriodId = $request->getData($this->getAlias())['academic_period_id'];
                }

                $RiskTable = TableRegistry::getTableLocator()->get('Institution.Risks');
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

    // START POCOR-7467
    public function onUpdateFieldReportFor(EventInterface $event, array $attr, $action, ServerRequest $request){
        if (isset($request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];

            if ((in_array($feature, ['Report.SpecialNeeds']))
                ) {
                $healthReportTypeOptions = [
                    'referral' => __('All Student Special Needs Referrals'),
                    'assessments' => __('All Student Special Needs Assessments'),
                    'services' => __('All Student Special Needs Services'),
                    'devices' => __('All Student Special Needs Devices'),
                    'plans' => __('All Student Special Needs Plans'),
                    'diagnostics' => __('all Student Special Needs Diagnostics'),
                ];
                $attr['options'] = $healthReportTypeOptions;
                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['onChangeReload'] = true;

                return $attr;
            }
        }
    }
    // END POCOR-7467

    public function onUpdateFieldHealthReportType(EventInterface $event, array $attr, $action, ServerRequest $request){
        if (isset($request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];

            if ((in_array($feature, ['Report.HealthReports']))
                ) {
                //POCOR-5890 starts
                $healthReportTypeOptions = [
                    'Summary' => __('Summary'), //POCOR-6661
                    'Overview' => __('Overview'),
                    'Allergies' => __('Allergies'),
                    'Consultations' => __('Consultations'),
                    'Families' => __('Families'),
                    'Histories' => __('Histories'),
                    'Immunizations' => __('Vaccinations'),//POCOR-5890
                    'Medications' => __('Medications'),
                    'Tests' => __('Tests'),
                    'Insurance' => __('Insurance'),
                    'BodyMass' => __('Body Mass'),
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

    public function onUpdateFieldInstitutionId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $areaId = $request->getData($this->getAlias())['area_education_id'];
        $InstitutionsTable = TableRegistry::getTableLocator()->get('Institution.Institutions');
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];

            if (in_array($feature, ['Report.BodyMassStatusReports',
                                    'Report.HealthReports',
                                    'Report.StudentsRiskAssessment',
                                    'Report.InstitutionStudentReports', //POCOR-6970
                                    'Report.SubjectsBookLists',
                                    'Report.StudentNotAssignedClass',
                                    'Report.SpecialNeeds',
                                    'Report.StudentGuardians','Report.StudentsPhoto','Report.Students',
                'Report.StudentIdentities','Report.StudentContacts','Report.StudentsEnrollmentSummary',
                'Report.StudentsGraduationSummary' //POCOR-8868
                  ])) {


                $institutionList = [];
                if (array_key_exists('institution_type_id', (array)$request->getData($this->getAlias())) && !empty($request->getData($this->getAlias())['institution_type_id'])) {
                    $institutionTypeId = $request->getData($this->getAlias())['institution_type_id'];
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
                } elseif (!$institutionTypeId && array_key_exists('area_education_id', (array)$request->getData($this->getAlias())) && !empty($request->getData($this->getAlias())['area_education_id']) && $areaId != -1) {
                    //Start:POCOR-6818 Modified this for POCOR-6859
                    $AreaT = TableRegistry::getTableLocator()->get('Area.Areas');                    
                    //Level-1
                    $AreaData = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $areaId])->toArray();
                    $childArea =[];
                    $childAreaMain = [];
                    $childArea3 = [];
                    $childArea4 = [];
                    foreach($AreaData as $kkk =>$AreaData11 ){
                        $childArea[$kkk] = $AreaData11->id;
                    }
                    //level-2
                    foreach($childArea as $kyy =>$AreaDatal2 ){
                        $AreaDatas = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal2])->toArray();
                        foreach($AreaDatas as $ky =>$AreaDatal22 ){
                            $childAreaMain[$ky] = $AreaDatal22->id;
                        }
                    }
                    //level-3
                    if(!empty($childAreaMain)){
                        foreach($childAreaMain as $kyy =>$AreaDatal3 ){
                            $AreaDatass = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal3])->toArray();
                            foreach($AreaDatass as $ky =>$AreaDatal222 ){
                                $childArea3[$ky] = $AreaDatal222->id;
                            }
                        }
                    }
                    
                    //level-4
                    if(!empty($childAreaMain)){
                        foreach($childArea3 as $kyy =>$AreaDatal4 ){
                            $AreaDatasss = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal4])->toArray();
                            foreach($AreaDatasss as $ky =>$AreaDatal44 ){
                                $childArea4[$ky] = $AreaDatal44->id;
                            }
                        }
                    }
                    $mergeArr = array_merge($childAreaMain,$childArea,$childArea3,$childArea4);
                    array_push($mergeArr,$areaId);
                    $mergeArr = array_unique($mergeArr);
                    $finalIds = implode(',',$mergeArr);
                    $finalIds = explode(',',$finalIds);
                    //End:POCOR-6818 Modified this for POCOR-6859
                    $institutionQuery = $InstitutionsTable
                        ->find('list', [
                            'keyField' => 'id',
                            'valueField' => 'code_name'
                        ])
                        ->where([
                            $InstitutionsTable->aliasField('area_id').' IN' => $finalIds //POCOR-6818
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
                        'Report.InstitutionStudentReports', //POCOR-6970
                        'Report.SpecialNeeds',
                        'Report.StudentGuardians',
                        //'Report.Students',
                        'Report.StudentsPhoto',
                        'Report.StudentContacts',
                        'Report.StudentsEnrollmentSummary',
                        'Report.StudentIdentities',
                        'Report.HealthReports',
                        'Report.StudentsGraduationSummary' //POCOR-8868
                    ]) && count($institutionList) > 1) {
                        $institutionOptions = ['' => '-- ' . __('Select') . ' --', '0' => __('All Institutions')] + $institutionList;
                    } else {
                        $institutionOptions = ['' => '-- ' . __('Select') . ' --'] + $institutionList;
                    }
                    if($superAdmin){
                       $institutionOptions = ['' => '-- ' . __('Select') . ' --', '0' => __('All Institutions')] + $institutionList;
                    }

                    if(in_array($feature, ['Report.Students'])) { //POCOR-8417
                        $attr['attr']['multiple'] = true;
                    } else {
                        $attr['attr']['multiple'] = false;
                    }
                    $attr['type'] = 'chosenSelect';
                    $attr['onChangeReload'] = true;
                    $attr['options'] = $institutionOptions;
                    $attr['attr']['required'] = true;
                }
            }
            return $attr;
        }
    }


    public function onExcelBeforeQuery (EventInterface $event, ArrayObject $settings, Query $query) {

        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $areaId = $requestData->area_education_id;
        //$institutionId = $requestData->institution_id;
        $institutionIds = $requestData->institution_id->_ids ?? [];
        $StudentStatuses = TableRegistry::getTableLocator()->get('Student.StudentStatuses');
        $enrolled = $StudentStatuses->getIdByCode('CURRENT');
        $selectedArea = $requestData->area_education_id;//POCOR-8768
        //Start:POCOR-6818 Modified this for POCOR-6859
        $AreaT = TableRegistry::getTableLocator()->get('areas');                    
        $conditions = [];
        //POCOR-8598 starts
        if ($areaId != -1 && $areaId != '') {
            $areaIds = [];
            $allgetArea = $this->getChildren($selectedArea, $areaIds);
            $selectedArea1[]= $selectedArea;
            if(!empty($allgetArea)){
                $allselectedAreas = array_merge($selectedArea1, $allgetArea);
            }else{
                $allselectedAreas = $selectedArea1;
            }
            $conditions['Institution.area_id IN'] = $allselectedAreas;//POCOR-8768
        } 
       
        if (!empty($academicPeriodId)) {
            $conditions['InstitutionStudent.academic_period_id'] = $academicPeriodId;
        }
        // Institution Filter (_ids logic)
        if (!empty($institutionIds) && !in_array(0, $institutionIds)) {
            if (!$superAdmin) {
                $conditions['InstitutionStudent.institution_id IN'] = $institutionIds;
            } else {
                $conditions['InstitutionStudent.institution_id IN'] = $institutionIds;
            }
        }elseif (!empty($areaId) && $areaId != -1) {
            // "All Institutions" selected -> get institutions by area
            $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
            $areaInstitutionIds = $Institutions->find()
                ->select(['id'])
                ->where(['area_id' => $areaId])
                ->extract('id')
                ->toArray();
            if (!empty($areaInstitutionIds)) {
                $conditions['InstitutionStudent.institution_id IN'] = $areaInstitutionIds;
            }
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
            'InstitutionStudentProgrammes' => [ //POCOR-9328
                'type' => 'left',
                'table' => 'institution_student_programmes', 
                'conditions' => [
                    'InstitutionStudentProgrammes.student_id = '.$this->aliasField('id')
                ],
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
            'external_reference' => 'Students.external_reference',
            'registration_number' => 'InstitutionStudentProgrammes.registration_number', //POCOR-9328
        ])
        ->contain(['Genders', 'AddressAreas', 'BirthplaceAreas', 'MainNationalities', 'MainIdentityTypes'])
        ->where([$this->aliasField('is_student') => 1,$conditions])
        ->group([$this->aliasField('openemis_no')]);


         $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                 // POCOR-8934 start
                 if ($row['date_of_birth'] instanceof \Cake\I18n\FrozenDate) {
                    $row['date_of_birth'] = $row['date_of_birth']->format('Y-m-d'); // Change format as needed
                } 
                // POCOR-8934 end

                // POCOR-6338 starts
                
                $Users = TableRegistry::getTableLocator()->get('security_users');
                $institutionStudents = TableRegistry::getTableLocator()->get('institution_students');
               

                //$row['student_status'] = $user_data->student_status;
                // POCOR-6338 ends                
                // POCOR-6129 custome fields code
                    
                $Guardians = TableRegistry::getTableLocator()->get('student_custom_field_values');
                $studentCustomFieldOptions = TableRegistry::getTableLocator()->get('student_custom_field_options');
                $studentCustomFields = TableRegistry::getTableLocator()->get('student_custom_fields');

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

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields) {
        $IdentityType = TableRegistry::getTableLocator()->get('FieldOption.IdentityTypes');
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
            'type' => 'string',
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

         $extraField[] = [
            'key' => 'InstitutionStudentProgrammes.registration_number', //POCOR-9328
            'field' => 'registration_number',
            'type' => 'string',
            'label' => 'Registration Number',
        ];
        $InfrastructureCustomFields = TableRegistry::getTableLocator()->get('student_custom_fields');
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

    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];

            if ((in_array($feature, ['Report.BodyMassStatusReports',
                                      'Report.HealthReports',
                                      'Report.StudentsRiskAssessment',
                                      'Report.InstitutionStudentReports', //POCOR-6970
                                      'Report.SubjectsBookLists',
                                      'Report.StudentNotAssignedClass',
                                      'Report.StudentsEnrollmentSummary',
                                      'Report.SpecialNeeds',
                                      'Report.InstitutionStudentsOutOfSchool',
                                        'Report.StudentsPhoto',
                'Report.Students',
                'Report.StudentIdentities','Report.StudentContacts',
                'Report.StudentsGraduationSummary' //POCOR-8868
                                      ])
            )) {
                $AcademicPeriodTable = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
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
                                       'Report.StudentsGraduationSummary', //POCOR-8868
                                      'Report.SpecialNeeds'])
                ) {
                    $attr['onChangeReload'] = true;
                }

                if (empty($request->getData($this->getAlias())['academic_period_id'])) {
                    $request->getData($this->getAlias())['academic_period_id'] = $currentPeriod;
                }
                return $attr;
            }
        }
    }

    public function onUpdateFieldAreaLevelId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];

            if ((in_array($feature, ['Report.StudentsPhoto',
                'Report.Students',
                'Report.StudentIdentities',
                'Report.InstitutionStudentReports', //POCOR-6970
                'Report.StudentContacts',
                'Report.HealthReports',
                'Report.BodyMassStatusReports',
                'Report.StudentsRiskAssessment',
                'Report.SubjectsBookLists',
                'Report.StudentNotAssignedClass',
                'Report.StudentsEnrollmentSummary',
                'Report.SpecialNeeds',
                'Report.StudentsGraduationSummary' //POCOR-8868
            ]))) {
                $Areas = TableRegistry::getTableLocator()->get('Area.AreaLevels');
                $entity = $attr['entity'];

                if ($action == 'add') {
                    $areaOptions = $Areas
                        ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                        ->order(['level'])
                        ->enableHydration(false);
                    $attr['type'] = 'chosenSelect';
                    $attr['attr']['multiple'] = false;
                    $attr['select'] = true;
                    $attr['options'] = ['' => '-- ' . __('Select') . ' --', '-1' => __('All Areas Level')] + $areaOptions->toArray();
                    $attr['onChangeReload'] = true;
                } else {
                    $attr['type'] = 'hidden';
                }
            }
        }
        
        return $attr;
    }

    public function onUpdateFieldAreaEducationId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {

        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            $areaLevelId = $this->request->getData($this->getAlias())['area_level_id'];//POCOR-6333
            if (in_array($feature, ['Report.StudentsEnrollmentSummary',
                'Report.StudentsPhoto',
                'Report.Students',
                'Report.StudentIdentities',
                'Report.InstitutionStudentReports', //POCOR-6970
                'Report.StudentContacts',
                'Report.HealthReports',
                'Report.BodyMassStatusReports',
                'Report.StudentsRiskAssessment',
                'Report.SubjectsBookLists',
                'Report.StudentNotAssignedClass',
                'Report.StudentsGraduationSummary', //POCOR-8868
                'Report.StudentsEnrollmentSummary','Report.SpecialNeeds'])) {
                    $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
                    $entity = $attr['entity'];

                    if ($action == 'add') {
                        $where = [];
                        
                        if ($areaLevelId != -1 && !empty($areaLevelId)) {
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
                            $attr['options'] = ['' => '-- ' . __('Select') . ' --', '-1' => __('All Areas')] + $areaOptions;
                        } else {
                            $attr['options'] = ['' => '-- ' . __('Select') . ' --'] + $areaOptions;
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

    public function onUpdateFieldEducationProgrammeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['academic_period_id'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            $academicPeriodId = $this->request->getData($this->getAlias())['academic_period_id'];
            $institutionId = $this->request->getData($this->getAlias())['institution_id'];  //POCOR-5740
            if (in_array($feature,
                        [
                            'Report.StudentsGraduationSummary', //POCOR-8868
                            'Report.StudentsEnrollmentSummary' //POCOR-8867
                        ])
                ) {

                //POCOR-6727 starts

                $InstitutionGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades');
                $conditions = [];
                if ($institutionId != 0) {
                    $conditions[$InstitutionGrades->aliasField('institution_id')] = $institutionId;
                }
                $gradeOptions = $InstitutionGrades
                    ->find('list', [
                        'keyField' => 'id',
                        'valueField' => 'name'
                    ])
                    ->select([
                        'id' => 'EducationProgrammes.id',
                        'name' => 'EducationProgrammes.name',
                        // 'education_programme_name' => 'EducationProgrammes.name'
                    ])
                    //->contain(['EducationProgrammes'])
                    ->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
                    ->where([
                        // $conditions,
                        'EducationSystems.academic_period_id' => $academicPeriodId,
                    ])
                    ->order([
                        'EducationProgrammes.order' => 'ASC',
                        'EducationGrades.name' => 'ASC'
                    ])
                    ->toArray();
                //POCOR-6727 End
                // print_r($gradeOptions);die;
                //POCOR-5740 starts
                if (in_array($feature, ['Report.SubjectsBookLists'])) {
                    $attr['onChangeReload'] = true;
                } //POCOR-5740 ends
                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['options'] = ['-1' => __('All Programmes')] + $gradeOptions;
            } 
            else {
                $attr['value'] = self::NO_FILTER;
            }
            return $attr;
        }
    }

    public function onUpdateFieldEducationGradeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['academic_period_id'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            $academicPeriodId = $this->request->getData($this->getAlias())['academic_period_id'];
            $institutionId = $this->request->getData($this->getAlias())['institution_id'];  //POCOR-5740
            if (in_array($feature,
                        [
                            'Report.ClassAttendanceNotMarkedRecords',
                            'Report.SubjectsBookLists',
                        ])
                ) {

                //POCOR-6727 starts

                $InstitutionGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades');
                $conditions = [];
                if ($institutionId != 0) {
                    $conditions[$InstitutionGrades->aliasField('institution_id')] = $institutionId;
                }
                $gradeOptions = $InstitutionGrades
                    ->find('list', [
                        'keyField' => 'id',
                        'valueField' => 'name'
                    ])
                    ->select([
                        'id' => 'EducationGrades.id',
                        'name' => 'EducationGrades.name',
                        'education_programme_name' => 'EducationProgrammes.name'
                    ])
                    //->contain(['EducationProgrammes'])
                    ->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
                    ->where([
                        $conditions,
                        'EducationSystems.academic_period_id' => $academicPeriodId,
                    ])
                    ->order([
                        'EducationProgrammes.order' => 'ASC',
                        'EducationGrades.name' => 'ASC'
                    ])
                    ->toArray();
                //POCOR-6727 End

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
                if (array_key_exists('institution_id', $request->getData($this->getAlias())) && !empty($request->getData($this->getAlias())['institution_id']) && array_key_exists('academic_period_id', $request->getData($this->getAlias())) && !empty($request->getData($this->getAlias())['academic_period_id'])) {
                    $institutionId = $request->getData($this->getAlias())['institution_id'];
                    $academicPeriodId = $request->getData($this->getAlias())['academic_period_id'];

                    $InstitutionGradesTable = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades');
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


    public function onUpdateFieldInstitutionTypeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];

            if (in_array($feature, [
              'Report.StudentNotAssignedClass'
            ])) {

                $TypesTable = TableRegistry::getTableLocator()->get('Institution.Types');
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

   public function onUpdateFieldEducationSubjectId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {

        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            if (in_array($feature,
                        [
                            'Report.InstitutionSubjects'
                            //POCOR-5740 starts
                            //'Report.SubjectsBookLists'
                            //POCOR-5740 ends
                        ])
                ) {

                $EducationSubjects = TableRegistry::getTableLocator()->get('Education.EducationSubjects');
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

                $EducationGradesSubjects = TableRegistry::getTableLocator()->get('Education.EducationGradesSubjects');
                $EducationSubjects = TableRegistry::getTableLocator()->get('Education.EducationSubjects');
                $subjectOptions = $EducationGradesSubjects
                                    ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                                    ->select([
                                        'education_subject_id' => $EducationGradesSubjects->aliasField('education_subject_id'),
                                        'education_grade_id' => $EducationGradesSubjects->aliasField('education_grade_id'),
                                        'id' => $EducationSubjects->aliasField('id'),
                                        'name' => $EducationSubjects->aliasField('name')
                                    ])
                                    ->leftJoin(
                                        [$EducationSubjects->getAlias() => $EducationSubjects->getTable()],
                                        [
                                            $EducationSubjects->aliasField('id = ') . $EducationGradesSubjects->aliasField('education_subject_id')
                                        ]
                                    )
                                    ->where([
                                        $EducationGradesSubjects->aliasField('education_grade_id') => $this->request->getData($this->getAlias())['education_grade_id']
                                    ])
                                    ->order([
                                        $EducationSubjects->aliasField('order') => 'ASC'
                                    ])->toArray();
                $attr['type'] = 'select';
                $attr['select'] = false;

                if($this->request->getData($this->getAlias())['education_grade_id'] == -1){ //for all grades
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

    // public function onUpdateFieldRiskId(EventInterface $event, array $attr, $action, Request $request)
    // {

    //     if (isset($this->request->getData($this->getAlias())['feature'])) {
    //         $feature = $this->request->getData($this->getAlias())['feature'];

    //         if (in_array($feature, ['Report.SpecialNeeds'])) {
    //             $InstitutionStudentRisks = TableRegistry::getTableLocator()->get('Institution.InstitutionStudentRisks');
    //             $Risks = TableRegistry::getTableLocator()->get('Risk.Risks');
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
    //                 [$Risks->getAlias() => $Risks->table()],
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

    public function onUpdateFieldStartDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            if ((in_array($feature, ['Report.BodyMassStatusReports']))) {
                $attr['type'] = 'date';
                return $attr;
            }
        }
    }

    public function onUpdateFieldEndDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];

            if ((in_array($feature, ['Report.BodyMassStatusReports']))) {
                $attr['type'] = 'date';
                return $attr;
            }
        }
    }


    // Start POCOR-7552

    public function onUpdateFieldReportStartDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];

            if ((in_array($feature, ['Report.SpecialNeeds']))) {
                $special_needs_feature = $request->getData($this->getAlias())['special_needs_feature'];
                if(in_array($special_needs_feature, ['assessments','devices','diagnostics'])){
                    $attr['type'] = 'date';
                    return $attr;

                }
            }
        }
    }

    public function onUpdateFieldReportEndDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];

            if ((in_array($feature, ['Report.SpecialNeeds']))) {
                $special_needs_feature = $request->getData($this->getAlias())['special_needs_feature'];
                if(in_array($special_needs_feature, ['assessments','devices','diagnostics'])){
                    $attr['type'] = 'date';
                    return $attr;

                }
            }
        }
    }
    // End POCOR-7552

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'feature':
                return __('Feature');
            case 'format':
                return __('Format');
            case 'academic_period_id':
                return __('Academic Period');
            case 'area_level_id':
                return __('Area Level');
            case 'institution_id':
                return __('Institution');
            case 'start_date':
                return __('Start Date');
            case 'end_date':
                return __('End Date');
            case 'health_report_type':
                return __('Health Report Type');
            case 'risk_type':
                return __('Risk Type');
            case 'education_grade_id':
                return __('Education Grade');
            case 'education_subject_id':
                return __('Education Subject');
            case 'institution_type_id':
                return __('Institution Type');
            case 'special_needs_feature':
                return __('Special Needs Feature');
            case 'education_programme_id': // POCOR-8868
                    return __('Education Programme'); //POCOR-8868
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    /**
     * POCOR-8598
     * Recursively retrieves all child area IDs for a given parent area ID.
     *
     * @param int $id The parent area ID to find children for.
     * @param array $idArray An array to collect all child IDs (including nested children).
     * @return array The array of child area IDs.
     */
    public function getChildren($id, $idArray) {
        $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
        $result = $Areas->find()
                           ->where([
                               $Areas->aliasField('parent_id') => $id
                            ]) 
                             ->toArray();
       foreach ($result as $key => $value) {
            $idArray[] = $value['id'];
           $idArray = $this->getChildren($value['id'], $idArray);
        }
        return $idArray;
    }
}
