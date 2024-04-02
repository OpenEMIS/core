<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;

class DataQualityTable extends AppTable {
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
	}

	public function beforeAction(Event $event) {
		$controllerName = $this->controller->name;
		$reportName = __('Data Quality');
		$this->controller->Navigation->substituteCrumb($this->alias(), $reportName);
		$this->controller->set('contentHeader', __($controllerName).' - '.$reportName);
		$this->fields = [];
		$this->ControllerAction->field('feature', ['select' => false]);
		$this->ControllerAction->field('academic_period_id', ['select' => false]);
        $this->ControllerAction->field('area_level_id', ['select' => false]);
        $this->ControllerAction->field('area_education_id', ['select' => false]);
        $this->ControllerAction->field('institution_id', ['select' => false]);
		//$this->ControllerAction->field('format');
	}

	/*public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = $this->controller->getFeatureOptions($this->alias());
		return $attr;
	}*/

	public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['options'] = $this->controller->getFeatureOptions($this->alias());
            $attr['onChangeReload'] = true;
            if (!(isset($this->request->data[$this->alias()]['feature']))) {
                $option = $attr['options'];
                reset($option);
                $this->request->data[$this->alias()]['feature'] = key($option);
            }
            return $attr;
        }
    }

    /*public function addAfterAction(Event $event, Entity $entity)
    {
    	if ($entity->has('feature')) { 
            $feature = $entity->feature;
            $fieldsOrder = ['feature'];
            switch ($feature) {
                case 'Report.EnrollmentOutliers':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'format';
                    break;
                }
             $this->ControllerAction->setFieldOrder($fieldsOrder);
       	}
    }*/

    //POCOR-7211
    public function addBeforeAction(Event $event)
    {
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
        $this->ControllerAction->field('area_level_id', ['type' => 'hidden']);
        $this->ControllerAction->field('area_education_id', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_id', ['type' => 'hidden']);
        $this->ControllerAction->field('format');
    }


    /**
     * add academic period id
     * POCOR-7211
     */
    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
    	if (isset($request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature,['Report.EnrollmentOutliers','Report.AgeOutliers','Report.ValidationReport'])){
            
            	$AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
                $academicPeriodOptions = $AcademicPeriodTable->getYearList();
                $currentPeriod = $AcademicPeriodTable->getCurrent();
                $attr['options'] = $academicPeriodOptions;
                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['onChangeReload'] = true;
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

            if ((in_array($feature, ['Report.ValidationReport',
                
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
                    $attr['options'] = ['' => '-- ' . __('Select') . ' --', '-1' => __('All Areas Level')] + $areaOptions->toArray();
                    
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
        if (isset($request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            $areaLevelId = $this->request->data[$this->alias()]['area_level_id'];//POCOR-6333
            if ((in_array($feature,
                [
                    'Report.ValidationReport',
                ]))) {
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

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        $this->Institutions  = TableRegistry::get('Institution.Institutions');
        $areaId = $request->data[$this->alias()]['area_education_id'];
        if ($areaId > 0) {
            $condition[$this->Institutions->aliasField('area_id')] = $areaId;
        }
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            $reportModels = [
                'Report.ValidationReport',
            ];
            
                if (in_array($feature, $reportModels)) {
                    $institutionQuery = $this->Institutions
                                ->find('list', [
                                    'keyField' => 'id',
                                    'valueField' => 'code_name'
                                ])
                                ->where([$condition])
                                ->order([
                                    $this->Institutions->aliasField('code') => 'ASC',
                                    $this->Institutions->aliasField('name') => 'ASC'
                                ]);
                // if user is not super admin than list will be filtered
                $superAdmin = $this->Auth->user('super_admin');
                if (!$superAdmin) {
                    $userId = $this->Auth->user('id');
                    $institutionQuery->find('byAccess', ['userId' => $userId]);
                }
                $institutionList = $institutionQuery->toArray();
                $attr['type'] = 'select';
                $attr['select'] = false;

                if (count($institutionList) > 1) {
                    $institutionOptions = ['' => '-- ' . __('Select') . ' --', '-1' => __('All Institutions')] + $institutionList;
                } else {
                    $institutionOptions = ['' => '-- ' . __('Select') . ' --'] + $institutionList;
                }
                
                $attr['type'] = 'chosenSelect';
                $attr['onChangeReload'] = true;
                $attr['attr']['multiple'] = false;
                $attr['options'] = $institutionOptions;
                $attr['attr']['required'] = true;
            }
            return $attr;
        }
    }
	    
}
