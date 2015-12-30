<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class StudentFeesTable extends AppTable {
	public $currency;
	public $studentId;
	private $_conditions = [];

	public function initialize(array $config) {
		$this->table('institution_fees');
		parent::initialize($config);

		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);

		$this->hasMany('InstitutionFeeTypes', ['className' => 'Institution.InstitutionFeeTypes', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('StudentFeesAbstract', ['className' => 'Institution.StudentFeesAbstract', 'dependent' => true, 'cascadeCallbacks' => true]);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function beforeAction() {
		$session = $this->request->session();
		if ($this->controller->name == 'Directories') {
			$this->studentId = $session->read('Directory.Directories.id');
		} else {
			$this->studentId = $session->read('Student.Students.id');
		}

		$ConfigItems = TableRegistry::get('ConfigItems');
    	$this->currency = $ConfigItems->value('currency');

		$InstitutionStudents = TableRegistry::get('Institutions.InstitutionStudents');
		$buffer = $InstitutionStudents->find()->where([$InstitutionStudents->aliasField('student_id') => $this->studentId])->toArray();
		$this->_conditions['institutionIds'] = [];
		$this->_conditions['academicPeriodIds'] = [];
		$this->_conditions['educationGradeIds'] = [];
		foreach ($buffer as $key => $value) {
			$this->_conditions['institutionIds'][$value->institution_id] = '';
			$this->_conditions['academicPeriodIds'][$value->academic_period_id] = '';
			$this->_conditions['educationGradeIds'][$value->education_grade_id] = '';
		}

    	$this->ControllerAction->field('total',	 			['visible' => false]);

    	$this->ControllerAction->field('institution_id', 		['visible' => true]);
    	$this->ControllerAction->field('academic_period_id', 	['visible' => true]);
    	$this->ControllerAction->field('education_grade_id', 	['visible' => true]);

    	$this->ControllerAction->field('total_fee', 		['type' => 'float', 'visible' => ['index'=>true]]);
    	$this->ControllerAction->field('amount_paid', 		['type'=>'float', 'visible' => ['index'=>true]]);
    	$this->ControllerAction->field('outstanding_fee', 	['type' => 'float', 'visible' => true]);

    	$this->ControllerAction->field('fee_types', ['type' => 'element', 'element' => 'Institution.Fees/fee_types', 'currency' => $this->currency, 'non-editable'=>true, 'visible' => ['view'=>true]]);
    	$this->ControllerAction->field('payments', ['type' => 'element', 'element' => 'Student.Fees/payments', 'currency' => $this->currency, 'visible' => ['view'=>true]]);

		$this->ControllerAction->setFieldOrder([
   			'institution_id', 'academic_period_id', 'education_grade_id', 'total_fee', 'amount_paid', 'outstanding_fee'
		]);

	}


/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/
	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$query
			->contain([
				'EducationGrades.EducationProgrammes',
				'InstitutionFeeTypes',
				'StudentFeesAbstract'
			])
			->where([
				$this->aliasField('institution_id') . ' IN ' => array_keys($this->_conditions['institutionIds']),
				$this->aliasField('academic_period_id') . ' IN ' => array_keys($this->_conditions['academicPeriodIds']),
				$this->aliasField('education_grade_id') . ' IN ' => array_keys($this->_conditions['educationGradeIds'])
			])
			->join([
				[
					'type' => 'left',
					'table' => 'student_fees',
					'alias' => 'StudentFeesAbstract',
					'conditions' => [
						'StudentFeesAbstract.institution_fee_id' => 'StudentFees.id',
						'StudentFeesAbstract.student_id' => $this->studentId
					]
				]
			])
			->group(['StudentFees.id'])
			;

	}

	private function setupTabElements() {
		$options = ['type' => 'student'];
		$tabElements = $this->controller->getFinanceTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', 'Fees');
	}

	public function indexAfterAction(Event $event, $data) {
		$this->setupTabElements();
	}


/******************************************************************************************************************
**
** view action methods
**
******************************************************************************************************************/
	public function viewBeforeQuery(Event $event, Query $query) {
		if (isset($event->subject->url('view')[1])) {
			$query
			->contain([
				'EducationGrades.EducationProgrammes',
				'InstitutionFeeTypes.FeeTypes',
				// 'StudentFeesAbstract.CreatedBy'
			])
			// ->where([
			// 	$this->aliasField('institution_id') . ' IN ' => array_keys($this->_conditions['institutionIds']),
			// 	$this->aliasField('academic_period_id') . ' IN ' => array_keys($this->_conditions['academicPeriodIds']),
			// 	$this->aliasField('education_grade_id') . ' IN ' => array_keys($this->_conditions['educationGradeIds'])
			// ])
			// ->join([
			// 	[
			// 		'type' => 'left',
			// 		'table' => 'student_fees',
			// 		'alias' => 'StudentFeesAbstract',
			// 		'conditions' => [
			// 			'StudentFeesAbstract.institution_fee_id' => $event->subject->url('view')[1],
			// 			'StudentFeesAbstract.student_id' => $this->studentId
			// 		]
			// 	]
			// ])
			->group(['StudentFees.id'])
			;
			// pr($query->sql());die;
		}
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$feeTypes = [];
    	foreach ($entity->institution_fee_types as $key=>$obj) {
    		$feeTypes[] = [
    			'id' => $obj->id,
    			'type' => $obj->fee_type->name,
				'fee_type_id' => $obj->fee_type_id,
				'amount' => number_format(floatval($obj->amount), 2)
			];
    	}

		$this->fields['fee_types']['data'] = $feeTypes;
		$this->fields['fee_types']['total'] = $this->onGetTotalFee($event, $entity);

    	$StudentFeesAbstract = TableRegistry::get('Institution.StudentFeesAbstract');
    	$fields = $StudentFeesAbstract->fields;
    	$fields['payment_date']['tableHeader'] = __('Payment Date'); 
    	$fields['created_user_id']['tableHeader'] = __('Created By'); 
    	$fields['comments']['tableHeader'] = __('Comments'); 
    	$fields['amount']['tableHeader'] = __('Amount' . ' (' . $this->currency . ')'); 
		$this->fields['payments']['fields'] = $fields;
    	$this->fields['payments']['data'] = $this->getPaymentRecords($entity);
    	$this->fields['payments']['total'] = $this->onGetAmountPaid($event, $entity);

		$this->ControllerAction->setFieldOrder([
			'institution_id', 'academic_period_id', 'education_grade_id', 'fee_types', 'payments', 'outstanding_fee'
		]);

    }


/******************************************************************************************************************
**
** specific field methods
**
******************************************************************************************************************/
	public function onGetAmountPaid(Event $event, Entity $entity) {
		return $this->currency.' '.number_format($this->getAmountPaid($entity), 2);
	}

	public function getAmountPaid(Entity $entity) {
		$query = $this->StudentFeesAbstract->find();
		$entityRecord = $query->where([
				$this->StudentFeesAbstract->aliasField('institution_fee_id') => $entity->id,
				$this->StudentFeesAbstract->aliasField('student_id') => $this->studentId,
			])
		->select([
			'paid' => $query->func()->sum($this->StudentFeesAbstract->aliasField('amount'))
		])
		->first()
		;
		if ($entityRecord) {
			return (float)$entityRecord->paid;
		} else {
			return (float)0.00;
		}
	}

	public function onGetEducationGradeId(Event $event, Entity $entity) {
		return $entity->education_grade->education_programme->name . ' - ' . $entity->education_grade->name;
	}

	public function onGetTotalFee(Event $event, Entity $entity) {
		return $this->currency.' '.number_format($this->getTotalFee($entity), 2);
	}

	public function getTotalFee(Entity $entity) {
		$amount = 0.00;
		foreach ($entity->institution_fee_types as $key=>$feeType) {
			$amount = (float)$amount + (float)$feeType->amount;
		}
		return $amount;
	}

	public function onGetOutstandingFee(Event $event, Entity $entity) {
		return $this->currency.' '.$this->getOutstandingFee($entity);	
	}

	public function getOutstandingFee(Entity $entity) {
		$totalFee = $this->getTotalFee($entity);
		$amountPaid = $this->getAmountPaid($entity);
		return number_format(($totalFee - $amountPaid), 2);
	}

	private function getPaymentRecords(Entity $entity) {
		$query = $this->StudentFeesAbstract->find('all');
		$entityRecords = $query->contain(['CreatedBy'])->where([
				$this->StudentFeesAbstract->aliasField('institution_fee_id') => $entity->id,
				$this->StudentFeesAbstract->aliasField('student_id') => $this->studentId,
			])
		->toArray()
		;
		// pr($entityRecords);die;
		// // foreach ($entityRecords as $key => $value) {
		// // 	$entityRecords[$key]->amount = number_format((float)$entityRecords[$key]->amount, 2);
		// // }
		return $entityRecords;
		// pr($entity);die;
	}

}
