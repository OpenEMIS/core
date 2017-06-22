<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Traits\MessagesTrait;
use App\Model\Table\ControllerActionTable;

class StudentFeesTable extends ControllerActionTable {
	use MessagesTrait;

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

		if ($this->behaviors()->has('ControllerAction')) {
            $this->behaviors()->get('ControllerAction')->config([
                'actions' => [
					'index' => true,
					'view' => true,
					'add' => false,
					'edit' => false,
					'remove' => false,
					'search' => false,
					'reorder' => false
				],
            ]);
        }
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		$session = $this->Session;
		if ($this->controller->name == 'Directories') {
			$this->studentId = $session->read('Directory.Directories.id');
		} else if ($this->controller->name == 'Profiles') {
			$this->studentId = $session->read('Auth.User.id');
		} else {
			$this->studentId = $session->read('Student.Students.id');
		}

		$ConfigItems = TableRegistry::get('Configuration.ConfigItems');
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

    	$this->field('total',	 			['visible' => false]);

    	$this->field('institution_id', 		['visible' => true]);
    	$this->field('academic_period_id', 	['visible' => true]);
    	$this->field('education_grade_id', 	['visible' => true]);

    	$this->field('total_fee', 		['type' => 'float', 'visible' => ['index'=>true]]);
    	$this->field('amount_paid', 		['type'=>'float', 'visible' => ['index'=>true]]);
    	$this->field('outstanding_fee', 	['type' => 'float', 'visible' => true]);

    	$this->field('fee_types', ['type' => 'element', 'element' => 'Institution.Fees/fee_types', 'currency' => $this->currency, 'non-editable'=>true, 'visible' => ['view'=>true]]);
    	$this->field('payments', ['type' => 'element', 'element' => 'Student.Fees/payments', 'currency' => $this->currency, 'visible' => ['view'=>true]]);

		$this->setFieldOrder([
   			'institution_id', 'academic_period_id', 'education_grade_id', 'total_fee', 'amount_paid', 'outstanding_fee'
		]);

	}


/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
	{
		$query
			->contain([
				'EducationGrades.EducationProgrammes',
				'InstitutionFeeTypes',
				'StudentFeesAbstract'
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

		if (!empty(array_keys($this->_conditions['institutionIds']))
			&& !empty(array_keys($this->_conditions['academicPeriodIds']))
			&& !empty(array_keys($this->_conditions['educationGradeIds']))){
				$query
					->where([
					$this->aliasField('institution_id') . ' IN ' => array_keys($this->_conditions['institutionIds']),
					$this->aliasField('academic_period_id') . ' IN ' => array_keys($this->_conditions['academicPeriodIds']),
					$this->aliasField('education_grade_id') . ' IN ' => array_keys($this->_conditions['educationGradeIds'])
				]);
		} else {
			$query
				->where([
				$this->aliasField('id') => -1 // the student doesnt have a student record, this is to make sure no record return
			]);
		}
	}

	public function indexAfterAction(Event $event, Query $query, ResultSet $resultSet, ArrayObject $extra) {
		$options = ['type' => 'student'];
		$tabElements = $this->controller->getFinanceTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}


/******************************************************************************************************************
**
** view action methods
**
******************************************************************************************************************/
	public function viewBeforeQuery(Event $event, Query $query) {
		if (isset($this->request->pass[1])) {
			$query
			->contain([
				'EducationGrades.EducationProgrammes',
				'InstitutionFeeTypes.FeeTypes',
			])
			->group(['StudentFees.id'])
			;
		}
	}

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		$feeTypes = [];
		$amount = 0.00;
    	foreach ($entity->institution_fee_types as $key=>$obj) {
    		$feeTypes[$obj->fee_type->order] = [
    			'id' => $obj->id,
    			'type' => $obj->fee_type->name,
				'fee_type_id' => $obj->fee_type_id,
				'amount' => number_format(floatval($obj->amount), 2)
			];
			$amount = (float)$amount + (float)$obj->amount;
    	}
    	ksort($feeTypes);

		$this->fields['fee_types']['data'] = $feeTypes;
		$this->fields['fee_types']['total'] = $this->currency.' '.number_format($amount, 2);

    	$StudentFeesAbstract = TableRegistry::get('Institution.StudentFeesAbstract');
    	$fields = $StudentFeesAbstract->fields;
    	$fields['payment_date']['tableHeader'] = __('Payment Date');
    	$fields['created_user_id']['tableHeader'] = __('Created By');
    	$fields['comments']['tableHeader'] = __('Comments');
    	$fields['amount']['tableHeader'] = __('Amount' . ' (' . $this->currency . ')');
		$this->fields['payments']['fields'] = $fields;
    	$this->fields['payments']['data'] = $this->getPaymentRecords($entity);
    	$this->fields['payments']['total'] = $this->onGetAmountPaid($event, $entity);

		$this->setFieldOrder([
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

	public function onGetEducationGradeId(Event $event, Entity $entity)
	{
		return $entity->education_grade->programme_name . ' - ' . $entity->education_grade->name;
	}

	public function onGetTotalFee(Event $event, Entity $entity)
	{
		return $this->currency.' '.number_format($this->getTotalFee($entity), 2);
	}

	public function getTotalFee(Entity $entity)
	{
		$amount = $entity->total;
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
		return $entityRecords;
	}

}
