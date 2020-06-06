<?php
namespace Report\Model\Table;

use ArrayObject;
use ZipArchive;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StudentsTable extends AppTable
{
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

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('format');
    }
    

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if ($data[$this->alias()]['feature'] == 'Report.BodyMassStatusReports') {
            $options['validate'] = 'BodyMassStatusReports';
        } elseif ($data[$this->alias()]['feature'] == 'Report.HealthReports') {
            $options['validate'] = 'HealthReports';
        }

    }

    public function addBeforeAction(Event $event)
    {
        $this->ControllerAction->field('institution_filter', ['type' => 'hidden']);
        $this->ControllerAction->field('position_filter', ['type' => 'hidden']);       
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_type_id', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_id', ['type' => 'hidden']); 
        $this->ControllerAction->field('health_report_type', ['type' => 'hidden']); 
    }

    public function validationStudentsRiskAssessment(Validator $validator)
   {
        $validator = $this->validationDefault($validator);
        $validator = $validator
        //->notEmpty('institution_type_id')
        ->notEmpty('institution_id');
        return $validator;
   }

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
   {
        if ($data[$this->alias()]['feature'] == 'Report.StudentsRiskAssessment') {
        $options['validate'] = 'StudentsRiskAssessment';
        }
   }

    public function addBeforeAction(Event $event)
    {
    $this->ControllerAction->field('institution_filter', ['type' => 'hidden']);
    $this->ControllerAction->field('position_filter', ['type' => 'hidden']);
    $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
    $this->ControllerAction->field('risk_type', ['type' => 'hidden']); 
    $this->ControllerAction->field('institution_type_id', ['type' => 'hidden']);
    $this->ControllerAction->field('institution_id', ['type' => 'hidden']);
   }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        $attr['options'] = $this->controller->getFeatureOptions($this->alias());
         $attr['onChangeReload'] = true;
        return $attr;
    }
    
    public function validationHealthReports(Validator $validator)
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
    
    public function onUpdateFieldHealthReportType(Event $event, array $attr, $action, Request $request){
        if (isset($request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
			
            if ((in_array($feature, ['Report.HealthReports']))
                ) {

                $healthReportTypeOptions = [
                    'Overview' => __('Overview'),
                    'Allergies' => __('Allergies'),
                    'Consultations' => __('Consultations'),
                    'Families' => __('Families'),
                    'Histories' => __('Histories'),
                    'Immunizations' => __('Immunizations'),
                    'Medications' => __('Medications'),
                    'Tests' => __('Tests'),
                    'Insurance' => __('Insurance'),
                ];
                
                $attr['options'] = $healthReportTypeOptions;
                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['onChangeReload'] = true;
                
                return $attr;
            }
        }
    }
        

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
			
            if ((in_array($feature, ['Report.BodyMassStatusReports','Report.HealthReports'])))
		{
                $AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
                $academicPeriodOptions = $AcademicPeriodTable->getYearList();
                $currentPeriod = $AcademicPeriodTable->getCurrent();

                $attr['options'] = $academicPeriodOptions;
                $attr['type'] = 'select';
                $attr['select'] = false;
                if (in_array($feature, ['Report.ClassAttendanceNotMarkedRecords', 'Report.InstitutionCases', 'Report.StudentAttendanceSummary', 'Report.StaffAttendances'])) {
                    $attr['onChangeReload'] = true;
                }

                if (empty($request->data[$this->alias()]['academic_period_id'])) {
                    $request->data[$this->alias()]['academic_period_id'] = $currentPeriod;
                }
                return $attr;
            }
        }
    }
    
    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];

            if (in_array($feature, ['Report.BodyMassStatusReports',
                                    'Report.HealthReports'
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
					
                    if (in_array($feature, ['Report.BodyMassStatusReports'])) {
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
     
    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
        $feature = $this->request->data[$this->alias()]['feature'];
        if (in_array($feature, ['Report.StudentsRiskAssessment'])) {
        $AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $academicPeriodOptions = $AcademicPeriodTable->getYearList();

        $attr['options'] = $academicPeriodOptions;
        $attr['type'] = 'select';
        $attr['select'] = false;
        $attr['onChangeReload'] = true;


        if (empty($request->data[$this->alias()]['academic_period_id'])) {
        reset($academicPeriodOptions);
        $request->data[$this->alias()]['academic_period_id'] = key($academicPeriodOptions);
        }
        return $attr;
        }
        }
   }

   public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request)
   {
   if (isset($this->request->data[$this->alias()]['feature'])) {
   $feature = $this->request->data[$this->alias()]['feature'];
   
   if (in_array($feature, ['Report.StudentsRiskAssessment',
   'Report.HealthReports'
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
   
   if (in_array($feature, ['Report.BodyMassStatusReports'])) {
   $institutionOptions = ['' => '-- ' . _('Select') . ' --', '0' => _('All Institutions')] + $institutionList;
   }elseif (in_array($feature, ['Report.HealthReports'])) {
   $institutionOptions = ['' => '-- ' . _('Select') . ' --', '0' => _('All Institutions'),'-1' => __('No Institutions')] + $institutionList;
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

    public function startStudentsPhotoDownload() {
            
        $cmd  = ROOT . DS . 'bin' . DS . 'cake StudentsPhotoDownload';
        $logs = ROOT . DS . 'logs' . DS . 'StudentsPhotoDownload.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
    }
}
