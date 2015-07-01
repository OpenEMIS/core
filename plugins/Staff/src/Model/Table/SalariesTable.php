<?php
namespace Staff\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;

class SalariesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('staff_salaries');
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->hasMany('SalaryAdditions', ['className' => 'Staff.SalaryAdditions']);
		$this->hasMany('SalaryDeductions', ['className' => 'Staff.SalaryDeductions']);
	}

	public function beforeAction() {
		$this->fields['gross_salary']['attr'] = array('data-compute-variable' => 'true', 'data-compute-operand' => 'plus', 'maxlength' => 9);
		$this->fields['net_salary']['attr'] = array('data-compute-target' => 'true', 'readonly' => true);

		if (strtolower($this->action) != 'index') {
			$this->Navigation->addCrumb($this->getHeader($this->action));
		}
	}

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		parent::beforeSave($event, $entity, $options);

		$totalAddition = 0;
		$totalDeduction = 0;

		$SalaryAdditions = TableRegistry::get('Staff.SalaryAdditions');
		$present = [];
		if ($entity->has('salary_additions')) {
			foreach ($entity->salary_additions as $key => $value) {
				if ($value->has('amount')) {
					$totalAddition += $value->amount;
				}
				if ($value->has($SalaryAdditions->primaryKey())) {
					$present[] = $value->{$SalaryAdditions->primaryKey()};
				}
			}
		}
		$deleteOptions = [
		    'staff_salary_id' => $entity->id,
		];
		if (!empty($present)) {
			$deleteOptions[$SalaryAdditions->primaryKey().' NOT IN'] = $present;
		}
		$SalaryAdditions->deleteAll($deleteOptions);
		
		$SalaryDeductions = TableRegistry::get('Staff.SalaryDeductions');
		$present = [];
		if ($entity->has('salary_deductions')) {
			foreach ($entity->salary_deductions as $key => $value) {
				if ($value->has('amount')) {
					$totalDeduction += $value->amount;
				}
				if ($value->has($SalaryDeductions->primaryKey())) {
					$present[] = $value->{$SalaryDeductions->primaryKey()};
				}
			}
		}
		$deleteOptions = [
		    'staff_salary_id' => $entity->id,
		];
		if (!empty($present)) {
			$deleteOptions[$SalaryDeductions->primaryKey().' NOT IN'] = $present;
		}
		$SalaryDeductions->deleteAll($deleteOptions);

		$data = ['additions' => $totalAddition, 'deductions' => $totalDeduction];

		$entity = $this->patchEntity($entity, $data);
	}

	public function addEditBeforePatch(Event $event, Entity $entity, $data, $options) {
		if (array_key_exists($this->alias(), $data)) {
			if (!array_key_exists('salary_additions', $data[$this->alias()])) {
				$data[$this->alias()]['salary_additions'] = [];
			}
			if (!array_key_exists('salary_deductions', $data[$this->alias()])) {
				$data[$this->alias()]['salary_deductions'] = [];
			}
		}
		
		return compact('entity', 'data', 'options');
	}


	public function indexBeforeAction(Event $event) {
		$this->fields['comment']['visible'] = false;

		$order = 0;
		$this->ControllerAction->setFieldOrder('salary_date', $order++);
		$this->ControllerAction->setFieldOrder('gross_salary', $order++);
		$this->ControllerAction->setFieldOrder('additions', $order++);
		$this->ControllerAction->setFieldOrder('deductions', $order++);
		$this->ControllerAction->setFieldOrder('net_salary', $order++);
	}

	public function editBeforeQuery(Event $event, Query $query, array $contain) {
		//Retrieve associated data
		$contain[] = 'SalaryAdditions'; 
		$contain[] = 'SalaryDeductions'; 
		return compact('query', 'contain');
	}

	public function addEditBeforeAction(Event $event) {
		$this->fields['additions']['visible'] = false;
		$this->fields['deductions']['visible'] = false;

		$this->fields['gross_salary']['type'] = 'string';
		$this->fields['net_salary']['type'] = 'string';

		$this->fields['gross_salary']['attr']['onkeyup'] = 'jsForm.compute(this)';
		$this->fields['net_salary']['attr']['onkeyup'] = 'jsForm.compute(this)';

		$SalaryAdditionType = TableRegistry::get('FieldOption.SalaryAdditionTypes')->getList();
		$SalaryDeductionType = TableRegistry::get('FieldOption.SalaryDeductionTypes')->getList();

		$order = 0;
		$this->ControllerAction->setFieldOrder('salary_date', $order++);
		$this->ControllerAction->setFieldOrder('gross_salary', $order++);
		$this->ControllerAction->setFieldOrder('net_salary', $order++);

		$this->ControllerAction->addField('addition_set', [
			'type' => 'element',
			'element' => 'Staff.salary_info',
			'order' => $order++,
			'visible' => true,
			'fieldName' => 'salary_additions',
			'operation' => 'add',
			'fieldOptions' => $SalaryAdditionType->toArray()
		]);
		$this->ControllerAction->addField('deduction_set', [
			'type' => 'element',
			'element' => 'Staff.salary_info',
			'order' => $order++,
			'visible' => true,
			'fieldName' => 'salary_deductions',
			'operation' => 'deduct',
			'fieldOptions' => $SalaryDeductionType->toArray()
		]);

		$this->ControllerAction->setFieldOrder('comment', $order++);
	}

	public function addEditOnAddRow(Event $event, Entity $entity, array $data, array $options) {
		$data[$this->alias()]['salary_additions'][] = ['amount' => 0];
		$options['associated'] = [
			'SalaryAdditions' => ['validate' => false],
			//'SalaryDeductions' => ['validate' => false]
		];
		
		return compact('entity', 'data', 'options');
	}


	public function addEditOnDeductRow(Event $event, Entity $entity, array $data, array $options) {
		$data[$this->alias()]['salary_deductions'][] = ['amount' => 0];
		$options['associated'] = [
			//'SalaryAdditions' => ['validate' => false],
			'SalaryDeductions' => ['validate' => false]
		];
		
		return compact('entity', 'data', 'options');
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		return $validator
			->add('gross_salary', 'ruleMoney',  [
				'rule' => ['money']
			])
			->add('net_salary', 'ruleMoney',  [
				'rule' => ['money']
			])
		;
	}

}
