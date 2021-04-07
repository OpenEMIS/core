<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StaffTable extends AppTable  {
	public function initialize(array $config) {
		$this->table('security_users');
		parent::initialize($config);
		
		$this->belongsTo('Genders', ['className' => 'User.Genders']);
		$this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
		$this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);
		
		$this->addBehavior('Excel', [
			'excludes' => ['is_student', 'is_staff', 'is_guardian', 'photo_name', 'super_admin', 'status'],
			'pages' => false
		]);
		$this->addBehavior('Report.ReportList');
		$this->addBehavior('Report.CustomFieldList', [
			'model' => 'Staff.Staff',
			'formFilterClass' => null,
			'fieldValueClass' => ['className' => 'StaffCustomField.StaffCustomFieldValues', 'foreignKey' => 'staff_id', 'dependent' => true, 'cascadeCallbacks' => true],
			'tableCellClass' => ['className' => 'StaffCustomField.StaffCustomTableCells', 'foreignKey' => 'staff_id', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']
		]);
	}
    
    public function validationStaffLeaveReport(Validator $validator)

    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->notEmpty('academic_period_id')
            ->notEmpty('institution_id');
        return $validator;
    }
	
	public function validationStaffDuties(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->notEmpty('academic_period_id')
            ->notEmpty('institution_id');
        return $validator;
    }

    public function validationStaffHealthReports(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->notEmpty('institution_id');
        return $validator;
    }

     public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
		if ($data[$this->alias()]['feature'] == 'Report.StaffDuties') {
            $options['validate'] = 'StaffDuties';
        } 
        if ($data[$this->alias()]['feature'] == 'Report.StaffLeaveReport') {
            $options['validate'] = 'StaffLeaveReport';
        }else if ($data[$this->alias()]['feature'] == 'Report.StaffHealthReports') {
            $options['validate'] = 'StaffHealthReports';
        }
    }


	public function beforeAction(Event $event) 
	{
		$this->fields = [];
		$this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('system_usage', ['type' => 'hidden']);
        $this->ControllerAction->field('status', ['type' => 'hidden']);
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
        $this->ControllerAction->field('area_id', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_id', ['type' => 'hidden']);
        $this->ControllerAction->field('staff_leave_type_id', ['type' => 'hidden']);
        $this->ControllerAction->field('format');
        $this->ControllerAction->field('health_report_type',['type' => 'hidden']);
        
	}
	
	public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = $this->controller->getFeatureOptions($this->alias());
        $attr['onChangeReload'] = true;
		return $attr;
	}

    public function onUpdateFieldStaffLeaveTypeId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, ['Report.StaffLeaveReport'])) {
                $staffLeaveTypeTable = TableRegistry::get('Staff.StaffLeaveTypes');
                $staffLeaveTypeOptions = $staffLeaveTypeTable->find('list', [
                            'keyField' => 'id',
                            'valueField' => 'name'
                        ]);

               $staffLeaveTypeList = $staffLeaveTypeOptions->toArray();
                
               if (empty($staffLeaveTypeList)) {
                    $staffLeaveTypeOptions = ['' => $this->getMessage('general.select.noOptions')];
                    $attr['type'] = 'select';
                    $attr['options'] = $staffLeaveTypeOptions;
                    $attr['attr']['required'] = true;
                } else {
                    
                    if (in_array($feature, [
                        'Report.StaffLeaveReport'
                    ])) {
                        $staffLeaveTypeOptions = ['' => '-- ' . __('Select') . ' --', '0' => __('All Staff Leaves')] + $staffLeaveTypeList;
                    }else {
                        $staffLeaveTypeOptions = ['' => '-- ' . __('Select') . ' --'] + $staffLeaveTypeList;
                    }

                    $attr['type'] = 'chosenSelect';
                    $attr['onChangeReload'] = true;
                    $attr['attr']['multiple'] = false;
                    $attr['options'] = $staffLeaveTypeOptions;
                    $attr['attr']['required'] = true;
                }
            }
        }
        
        return $attr;
    }
    
    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, ['Report.StaffSalaries',
                                    'Report.StaffLeaveReport','Report.StaffDuties','Report.StaffHealthReports'])) {
                $AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
                $academicPeriodOptions = $AcademicPeriodTable->getYearList();

                $attr['options'] = $academicPeriodOptions;
                $attr['type'] = 'select';
                $attr['select'] = false;

                if (empty($request->data[$this->alias()]['academic_period_id'])) {
                    reset($academicPeriodOptions);
                    $request->data[$this->alias()]['academic_period_id'] = key($academicPeriodOptions);
                }
                return $attr;
            }
        }
    }

    public function onUpdateFieldSystemUsage(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, ['Report.StaffSystemUsage'])) {
                $options = [
                    '1' => __('No previous login'),
                    '2' => __('Logged in within the last 7 days')
                ];
                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['options'] = $options;
                return $attr;
            }
        }
    }

    public function onUpdateFieldStatus(Event $event, array $attr, $action, Request $request) {
        if ($action == 'add') {
            if (isset($this->request->data[$this->alias()]['feature'])) {
                $feature = $this->request->data[$this->alias()]['feature'];

                if (in_array($feature, ['Report.StaffLicenses'])) {
                    $licenseStatuses = $this->Workflow->getWorkflowStatuses('Staff.Licenses');
                    $licenseStatuses = ['-1' => __('All Statuses')] + $licenseStatuses;

                    $attr['type'] = 'select';
                    $attr['select'] = false;
                    $attr['options'] = $licenseStatuses;
                    return $attr;
                }
            }
        }
    }

	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
		$query->where([$this->aliasField('is_staff') => 1]);
	}

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) {
		$IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
        $identity = $IdentityType->getDefaultEntity();
        
        foreach ($fields as $key => $field) { 
        	//get the value from the table, but change the label to become default identity type.
            if ($field['field'] == 'identity_number') { 
                $fields[$key] = [
                    'key' => 'Staff.identity_number',
                    'field' => 'identity_number',
                    'type' => 'string',
                    'label' => __($identity->name)
                ];
                break;
            }
        }
	}

    public function onUpdateFieldAreaId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];

            if (in_array($feature, ['Report.StaffPositions', 'Report.PositionSummary',
                  ])) { 
                    $Areas = TableRegistry::get('Area.Areas');
                    $entity = $attr['entity'];

                    if ($action == 'add') {
                        $areaOptions = $Areas
                            ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                            ->order([$Areas->aliasField('order')]);

                        $attr['type'] = 'chosenSelect';
                        $attr['attr']['multiple'] = false;
                        $attr['select'] = true;
                        $attr['options'] = ['' => '-- ' . __('Select') . ' --', '0' => __('All Areas')] + $areaOptions->toArray();
                        $attr['onChangeReload'] = true;
                    } else {
                        $attr['type'] = 'hidden';
                    }
            }
        }
        return $attr;
    }
    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];

            if (in_array($feature, ['Report.StaffPositions', 'Report.StaffHealthReports',
                                    'Report.StaffLeaveReport','Report.StaffDuties','Report.PositionSummary'])) { 
                $area_id = $this->request->data[$this->alias()]['area_id'];
                $area_ids = [];
                
                if(!empty($area_id)) {
                    $AreaTable = TableRegistry::get('Area.Areas');
                    $areaData = [];
                    $areaData = $AreaTable
                        ->find()
                        ->select([
                            $AreaTable->aliasField('id'),
                        ])
                        ->where([
                            $AreaTable->aliasField('parent_id') => $area_id,
                        ])
                        ->hydrate(false)
                        ->toArray();
                    
                    if(!empty($areaData)) {
                        foreach($areaData as $data) {
                            $area_ids[] = $data['id'];
                        }
                        
                        $areaIds = [];
                        if(!empty($area_ids)) {
                            $areaIds = $AreaTable
                                ->find()
                                ->select([
                                    $AreaTable->aliasField('id'),
                                ])
                                ->where([
                                    $AreaTable->aliasField('parent_id').' IN'  => $area_ids,
                                ])
                                ->hydrate(false)
                                ->toArray();
                        }
                        if(!empty($areaIds)) {
                            foreach($areaIds as $area) {
                                $area_ids[] = $area['id'];
                            }
                        }
                    } else {
                        $area_ids[] = $area_id;
                    }
                }
                
                $institutionList = [];

                if ($area_id == 0) {
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
                } else {

                    $InstitutionsTable = TableRegistry::get('Institution.Institutions');
                    $institutionQuery = $InstitutionsTable
                        ->find('list', [
                            'keyField' => 'id',
                            'valueField' => 'code_name'
                        ])
                        ->where([
                                $InstitutionsTable->aliasField('area_id').' IN' => $area_ids
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
                        'Report.StaffPositions',
                        'Report.StaffLeaveReport',
                        'Report.StaffDuties',
                        'Report.PositionSummary'
                    ])) {
                        $institutionOptions = ['' => '-- ' . __('Select') . ' --', '0' => __('All Institutions')] + $institutionList;
                    }elseif (in_array($feature, ['Report.StaffHealthReports'])) {
                        $institutionOptions = ['' => '-- ' . __('Select') . ' --', '0' => __('All Institutions'),'-1' => __('No Institutions')] + $institutionList;
                    }
                    else {
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
    public function onUpdateFieldHealthReportType(Event $event, array $attr, $action, Request $request){
        if (isset($request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if ((in_array($feature, ['Report.StaffHealthReports']))
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
}
