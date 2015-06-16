<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Utility\Inflector;

class SalariesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('staff_salaries');
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->hasMany('SalaryAdditions', ['className' => 'Staff.SalaryAdditions']);
		$this->hasMany('SalaryDeductions', ['className' => 'Staff.SalaryDeductions']);
	}

	public function beforeAction() {

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
