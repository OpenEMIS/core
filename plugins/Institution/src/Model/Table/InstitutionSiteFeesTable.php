<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Text;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class InstitutionSiteFeesTable extends AppTable {
	public $institutionId = 0;
	private $_selectedAcademicPeriodId = 0;
	private $_academicPeriodOptions = [];
	private $_gradeOptions = [];


/******************************************************************************************************************
**
** CakePHP default methods
**
******************************************************************************************************************/
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);

		$this->hasMany('InstitutionSiteFeeTypes', ['className' => 'Institution.InstitutionSiteFeeTypes', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('StudentFees', ['className' => 'Institution.StudentFees', 'dependent' => true, 'cascadeCallbacks' => true]);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function beforeAction($event) {
    	$this->ControllerAction->field('total', ['type' => 'float', 'visible' => ['index'=>true, 'edit'=>true]]);
    	$this->ControllerAction->field('institution_site_id', ['type' => 'hidden', 'visible' => ['edit'=>true]]);
    	$this->ControllerAction->field('academic_period_id', ['type' => 'select', 'visible' => ['view'=>true, 'edit'=>true], 'onChangeReload'=>true]);
    	$this->ControllerAction->field('education_grade_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
    	$this->ControllerAction->field('education_programme', ['type' => 'select', 'visible' => ['index'=>true]]);

		$ConfigItems = TableRegistry::get('ConfigItems');
    	$currency = $ConfigItems->value('currency');
    	$this->ControllerAction->field('fee_types', ['type' => 'element', 'element' => 'Institution.Fees/fee_types', 'currency' => $currency, 'visible' => ['view'=>true, 'edit'=>true]]);
	}


/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/
    public function indexBeforeAction($event) {
		$this->ControllerAction->setFieldOrder([
			'education_programme', 'education_grade_id', 'total'
		]);

		$Fees = $this;
		$institutionId = $this->institutionId;
		$this->advancedSelectOptions($this->_academicPeriodOptions, $this->_selectedAcademicPeriodId, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noProgrammeGradeFees')),
			'callable' => function($id) use ($Fees, $institutionId) {
				return $Fees->find()->where(['institution_site_id'=>$institutionId, 'academic_period_id'=>$id])->count();
			}
		]);

		$toolbarElements = [
            ['name' => 'Institution.Fees/controls', 
             'data' => [
	            	'academicPeriodOptions'=>$this->_academicPeriodOptions,
	            ],
	         'options' => []
            ]
        ];

		$this->controller->set('toolbarElements', $toolbarElements);
		
	}

	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $paginateOptions) {
		$paginateOptions['finder'] = ['withProgrammes' => []];
	}

    public function findWithProgrammes(Query $query, array $options) {
    	return $query->contain(['EducationGrades'=>['EducationProgrammes']]);
    }


/******************************************************************************************************************
**
** view action methods
**
******************************************************************************************************************/
    public function viewBeforeAction(Event $event) {
		$this->ControllerAction->setFieldOrder([
			'academic_period_id', 'education_grade_id', 'fee_types'
		]);
	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain('InstitutionSiteFeeTypes.FeeTypes');
	}

    public function viewAfterAction(Event $event, Entity $entity) {
		$feeTypes = [];
    	foreach ($entity->institution_site_fee_types as $key=>$obj) {
    		$feeTypes[] = [
    			'id' => $obj->id,
    			'type' => $obj->fee_type->name,
				'fee_type_id' => $obj->fee_type_id,
				'amount' => $obj->amount
			];
    	}
		$this->fields['fee_types']['data'] = $feeTypes;
		$this->fields['fee_types']['total'] = $entity->total;
	}


/******************************************************************************************************************
**
** edit action methods
**
******************************************************************************************************************/
    public function editBeforeAction(Event $event) {
		$this->ControllerAction->setFieldOrder([
			'academic_period_id', 'education_grade_id'
		]);

		$this->fields['academic_period_id']['type'] = 'readonly';
		$this->fields['education_grade_id']['type'] = 'readonly';
		$this->ControllerAction->field('total', ['visible' => false]);
	}

	public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$this->cleanFeeTypes($data);
    }

    public function editAfterAction(Event $event, Entity $entity) {
		$feeTypes = [];
    	foreach ($this->fields['fee_types']['options'] as $key=>$obj) {
    		$feeTypes[] = [
    			'id' => Text::uuid(),
    			'type' => $obj,
				'fee_type_id' => $key,
				'amount' => ''
			];
    	}
		$this->fields['fee_types']['data'] = $feeTypes;

		$exists = [];
		$types = $this->fields['fee_types']['options']->toArray();
		foreach ($entity->institution_site_fee_types as $key=>$obj) {
    		$exists[] = [
    			'id' => $obj->id,
    			'type' => $types[$obj->fee_type_id],
				'fee_type_id' => $obj->fee_type_id,
				'amount' => $obj->amount
			];
    	}
		$this->fields['fee_types']['exists'] = $exists;

 		
		$this->fields['academic_period_id']['attr']['value'] = $this->_academicPeriodOptions[$entity->academic_period_id];
		$this->fields['education_grade_id']['attr']['value'] = $this->_gradeOptions[$entity->education_grade_id];

	}


/******************************************************************************************************************
**
** add action methods
**
******************************************************************************************************************/
    public function addBeforeAction($event) {
		$this->ControllerAction->setFieldOrder([
			'academic_period_id', 'education_grade_id'
		]);

		$this->advancedSelectOptions($this->_academicPeriodOptions, $this->_selectedAcademicPeriodId);
		$this->fields['academic_period_id']['options'] = $this->_academicPeriodOptions;

		// find the grades that already has fees
		$existedGrades = $this->find('list', ['keyField' => 'education_grade_id', 'valueField' => 'education_grade_id'])
							->where([
								$this->aliasField('institution_site_id') => $this->institutionId,
								$this->aliasField('academic_period_id') => $this->_selectedAcademicPeriodId
							])
							->toArray();
		// remove the existed grades from the options
		$gradeOptions = array_diff_key($this->_gradeOptions, $existedGrades);
		$this->fields['education_grade_id']['options'] = $gradeOptions;

	}

	public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$this->cleanFeeTypes($data);
    }

    public function addAfterAction(Event $event, Entity $entity) {
		$feeTypes = [];
    	foreach ($this->fields['fee_types']['options'] as $key=>$obj) {
    		$feeTypes[] = [
    			'id' => Text::uuid(),
    			'type' => $obj,
				'fee_type_id' => $key,
				'amount' => ''
			];
    	}
		$this->fields['fee_types']['data'] = $feeTypes;
	}


/******************************************************************************************************************
**
** field specific methods
**
******************************************************************************************************************/
	public function onGetEducationProgramme(Event $event, Entity $entity) {
		return $entity->education_grade->education_programme->name;
	}

	public function onUpdateFieldFeeTypes(Event $event, array $attr, $action, $request) {
		$attr['options'] = $this->InstitutionSiteFeeTypes->FeeTypes->getList();
		return $attr;
	}

	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, $request) {
		$this->_academicPeriodOptions = $this->AcademicPeriods->getAvailableAcademicPeriods(true);
		$this->_selectedAcademicPeriodId = $this->postString('academic_period_id', $this->_academicPeriodOptions);
		$attr['options'] = $this->_academicPeriodOptions;
		return $attr;
	}

	public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, $request) {
		if ($this->_selectedAcademicPeriodId ==0) {
			$this->_academicPeriodOptions = $this->AcademicPeriods->getAvailableAcademicPeriods(true);
			$this->_selectedAcademicPeriodId = $this->postString('academic_period_id', $this->_academicPeriodOptions);
		}
 		
		$this->_gradeOptions = $this->Institutions->InstitutionSiteGrades->getInstitutionSiteGradeOptions($this->institutionId, $this->_selectedAcademicPeriodId);
		$attr['options'] = $this->_gradeOptions;
		return $attr;
	}


/******************************************************************************************************************
**
** essential methods
**
******************************************************************************************************************/
	private function cleanFeeTypes(&$data) {
		if (isset($data[$this->alias()]['institution_site_fee_types'])) {
			$types = $data[$this->alias()]['institution_site_fee_types'];
			$total = 0;
			foreach ($types as $i => $obj) {
				if (empty($obj['amount'])) {
					unset($data[$this->alias()]['institution_site_fee_types'][$i]);
				} else {
					$total = $total + $obj['amount'];
				}
			}
			$data[$this->alias()]['total'] = $total;
		}
	}
	
}
