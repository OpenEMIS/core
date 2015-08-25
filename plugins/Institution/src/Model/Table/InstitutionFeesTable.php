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

class InstitutionFeesTable extends AppTable {
	public $institutionId = 0;
	private $_selectedAcademicPeriodId = 0;
	private $_academicPeriodOptions = [];
	private $_gradeOptions = [];
	public $currency = '';


/******************************************************************************************************************
**
** CakePHP default methods
**
******************************************************************************************************************/
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);

		$this->hasMany('InstitutionFeeTypes', ['className' => 'Institution.InstitutionFeeTypes', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('StudentFees', ['className' => 'Institution.StudentFeesAbstract', 'dependent' => true, 'cascadeCallbacks' => true]);

	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function beforeAction(Event $event) {
		$session = $this->request->session();
		$this->institutionId = $session->read('Institutions.id');

    	$this->ControllerAction->field('total', ['type' => 'float', 'visible' => ['add' => false, 'edit' => false, 'index' => true, 'view' => true]]);
    	$this->ControllerAction->field('institution_id', ['type' => 'hidden', 'visible' => ['edit'=>true]]);
    	$this->ControllerAction->field('academic_period_id', ['type' => 'select', 'visible' => ['view'=>true, 'edit'=>true], 'onChangeReload'=>true]);
    	$this->ControllerAction->field('education_grade_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
    	$this->ControllerAction->field('education_programme', ['type' => 'select', 'visible' => ['index'=>true]]);

		$ConfigItems = TableRegistry::get('ConfigItems');
    	$this->currency = $ConfigItems->value('currency');
    	$this->ControllerAction->field('fee_types', ['type' => 'element', 'element' => 'Institution.Fees/fee_types', 'currency' => $this->currency, 'visible' => ['view'=>true, 'edit'=>true]]);
	}

	public function onUpdateIncludes(Event $event, ArrayObject $includes, $action) {
		if ($action == 'edit' || $action == 'add') {
			$includes['fees'] = [
				'include' => true,
				'js' => ['Institution.../js/fees']
			];
		}
	}

/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/
    public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$this->ControllerAction->setFieldOrder([
			'education_programme', 'education_grade_id', 'total'
		]);
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$academicPeriodOptions = $this->AcademicPeriods->getList();
		$selectedOption = $this->queryString('academic_period_id', $academicPeriodOptions);

		$Fees = $this;
		$institutionId = $this->institutionId;

		$this->advancedSelectOptions($academicPeriodOptions, $selectedOption, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noProgrammeGradeFees')),
			'callable' => function($id) use ($Fees, $institutionId) {
				return $Fees->find()->where(['institution_id'=>$institutionId, 'academic_period_id'=>$id])->count();
			}
		]);
		
		$this->controller->set('selectedOption', $selectedOption);
		$this->controller->set(compact('academicPeriodOptions'));

		$toolbarElements = [
			['name' => 'Institution.Fees/controls', 'data' => [], 'options' => []]
		];
		$this->controller->set('toolbarElements', $toolbarElements);

		$academicPeriodId = $selectedOption;
		$query
			->contain(['InstitutionFeeTypes'])
			->find('withProgrammes')
			->where([$this->aliasField('academic_period_id') => $academicPeriodId]);
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
		$query->contain([
			'EducationGrades',
			'InstitutionFeeTypes.FeeTypes'
		]);
	}

    public function viewAfterAction(Event $event, Entity $entity) {
		$feeTypes = [];
		$amount = 0.00;
    	foreach ($entity->institution_fee_types as $key=>$obj) {
    		$feeTypes[] = [
    			'id' => $obj->id,
    			'type' => $obj->fee_type->name,
				'fee_type_id' => $obj->fee_type_id,
				'amount' => number_format($obj->amount, 2)
			];
			$amount = (float)$amount + (float)$obj->amount;
    	}
		$this->fields['fee_types']['data'] = $feeTypes;
		$this->fields['fee_types']['total'] = $this->currency.' '.number_format($amount, 2);
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
		foreach ($entity->institution_fee_types as $key=>$obj) {
    		$exists[] = [
    			'id' => $obj->id,
    			'type' => $types[$obj->fee_type_id],
				'fee_type_id' => $obj->fee_type_id,
				'amount' => $obj->amount
			];
    	}
		$this->fields['fee_types']['exists'] = $exists;
		$this->fields['fee_types']['currency'] = $this->currency;
 		
		$this->fields['academic_period_id']['attr']['value'] = $this->_academicPeriodOptions[$entity->academic_period_id];
		$this->fields['education_grade_id']['attr']['value'] = isset($this->_gradeOptions[$entity->education_grade_id]) ? $this->_gradeOptions[$entity->education_grade_id] : $entity->education_grade->name;

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
								$this->aliasField('institution_id') => $this->institutionId,
								$this->aliasField('academic_period_id') => $this->_selectedAcademicPeriodId
							])
							->toArray();
		// remove the existed grades from the options
		$gradeOptions = array_diff_key($this->_gradeOptions, $existedGrades);
		$this->fields['education_grade_id']['options'] = $gradeOptions;
		$this->fields['institution_id']['value'] = $this->institutionId;
		// $attr['attr']['value'] = $this->institutionId;
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
		$this->fields['fee_types']['currency'] = $this->currency;
	}


/******************************************************************************************************************
**
** field specific methods
**
******************************************************************************************************************/
	public function onBeforeDelete(Event $event, ArrayObject $deleteOptions, $id) {
		$idKey = $this->aliasField($this->primaryKey());
		if ($this->exists([$idKey => $id])) {
			$query = $this->find()
				->contain(['StudentFees'])
				->where([$idKey => $id])
				->first();

			if ($query->has('student_fees') && count($query->student_fees)>0) {
				$this->Alert->error('InstitutionFees.fee_payments_exists');
				$event->stopPropagation();
				$action = $this->ControllerAction->url('index');
				return $this->controller->redirect($action);
			}
		}
	}


/******************************************************************************************************************
**
** field specific methods
**
******************************************************************************************************************/
	public function onGetEducationProgramme(Event $event, Entity $entity) {
		return $entity->education_grade->education_programme->name;
	}

	public function onGetTotal(Event $event, Entity $entity) {
		return $this->currency.' '.number_format($this->getTotal($entity), 2);
	}

	public function getTotal(Entity $entity) {
		/**
		 * PHPOE-1414
		 * Not using $this->total anymore since it only saves till 11 digits with 2 decimal places
		 * and when a feeType is for example, 999,999,999.99, the rest of the fee types cannot be added saved into the "total" record.
		 * Implements a manual count of the extracted feeTypes.
		 */
		$amount = 0.00;
		foreach ($entity->institution_fee_types as $key=>$feeType) {
			$amount = (float)$amount + (float)$feeType->amount;
		}
		return $amount;
	}

	public function onUpdateFieldFeeTypes(Event $event, array $attr, $action, $request) {
		$attr['options'] = $this->InstitutionFeeTypes->FeeTypes->getList();
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
 		
		$this->_gradeOptions = $this->Institutions->InstitutionGrades->getGradeOptions($this->institutionId, $this->_selectedAcademicPeriodId);
		$attr['options'] = $this->_gradeOptions;
		return $attr;
	}


/******************************************************************************************************************
**
** essential methods
**
******************************************************************************************************************/
	private function cleanFeeTypes(&$data) {
		if (isset($data[$this->alias()]['institution_fee_types'])) {
			$types = $data[$this->alias()]['institution_fee_types'];
			$total = 0;
			foreach ($types as $i => $obj) {
				if (empty($obj['amount'])) {
					unset($data[$this->alias()]['institution_fee_types'][$i]);
				} else {
					$total = $total + $obj['amount'];
				}
			}
			$data[$this->alias()]['total'] = $total;
		}
	}
	
}
