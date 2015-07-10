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

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		if (array_key_exists($this->alias(), $data)) {
			if (!array_key_exists('salary_additions', $data[$this->alias()])) {
				$data[$this->alias()]['salary_additions'] = [];
			}
			if (!array_key_exists('salary_deductions', $data[$this->alias()])) {
				$data[$this->alias()]['salary_deductions'] = [];
			}
		}
	}


	public function indexBeforeAction(Event $event) {
		$this->fields['gross_salary']['type'] = 'float';
		$this->fields['net_salary']['type'] = 'float';
		$this->fields['additions']['type'] = 'float';
		$this->fields['deductions']['type'] = 'float';
		$this->fields['comment']['visible'] = false;
		$this->ControllerAction->setFieldOrder(['salary_date', 'gross_salary', 'additions', 'deductions', 'net_salary']);
	}

	public function editBeforeQuery(Event $event, Query $query) {
		$query->contain([
			'SalaryAdditions',
			'SalaryDeductions'
		]);
	}

	public function addEditBeforeAction(Event $event) {
		$this->fields['additions']['visible'] = false;
		$this->fields['deductions']['visible'] = false;

		$this->fields['gross_salary']['type'] = 'float';
		$this->fields['net_salary']['type'] = 'float';

		$this->fields['gross_salary']['attr']['step'] = 0.00;
		$this->fields['gross_salary']['attr']['min'] = 0.00;
		$this->fields['gross_salary']['attr']['onkeyup'] = 'jsForm.compute(this)';

		$this->fields['net_salary']['attr']['step'] = 0.00;
		$this->fields['net_salary']['attr']['min'] = 0.00;
		$this->fields['net_salary']['attr']['onkeyup'] = 'jsForm.compute(this)';

		$SalaryAdditionType = TableRegistry::get('FieldOption.SalaryAdditionTypes')->getList();
		$SalaryDeductionType = TableRegistry::get('FieldOption.SalaryDeductionTypes')->getList();

		$this->ControllerAction->addField('addition_set', [
			'type' => 'element',
			'element' => 'Staff.salary_info',
			'visible' => true,
			'fieldName' => 'salary_additions',
			'operation' => 'add',
			'fieldOptions' => $SalaryAdditionType->toArray()
		]);
		$this->ControllerAction->addField('deduction_set', [
			'type' => 'element',
			'element' => 'Staff.salary_info',
			'visible' => true,
			'fieldName' => 'salary_deductions',
			'operation' => 'deduct',
			'fieldOptions' => $SalaryDeductionType->toArray()
		]);

		$this->ControllerAction->setFieldOrder(['salary_date', 'gross_salary', 'net_salary', 'addition_set', 'deduction_set', 'comment']);
	}

	public function addEditOnAddRow(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$data[$this->alias()]['salary_additions'][] = ['amount' => '0.00'];
		$options['associated'] = [
			'SalaryAdditions' => ['validate' => false],
			//'SalaryDeductions' => ['validate' => false]
		];
	}

	public function addEditOnDeductRow(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$data[$this->alias()]['salary_deductions'][] = ['amount' => '0.00'];
		$options['associated'] = [
			//'SalaryAdditions' => ['validate' => false],
			'SalaryDeductions' => ['validate' => false]
		];
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

	public function viewBeforeAction(Event $event) {
		$this->fields['gross_salary']['type'] = 'float';
		$this->fields['net_salary']['type'] = 'float';
		$this->fields['additions']['type'] = 'float';
		$this->fields['deductions']['type'] = 'float';
	}
}
