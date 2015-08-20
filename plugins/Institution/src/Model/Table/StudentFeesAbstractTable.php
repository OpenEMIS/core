<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;


class StudentFeesAbstractTable extends AppTable {
	public $fields = [];
/******************************************************************************************************************
**
** CakePHP default methods
**
******************************************************************************************************************/
	public function initialize(array $config) {
		$this->table('student_fees');
		parent::initialize($config);
		
		$this->belongsTo('InstitutionSiteFees', ['className' => 'Institution.InstitutionSiteFees']);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('CreatedBy', ['className' => 'User.Users', 'foreignKey' => 'created_user_id']);

		$this->fields = $this->getFields();
	}

	public function validationDefault(Validator $validator) {
		return $validator
			// ->add('start_date', 'ruleCompareDate', [
			// 	'rule' => ['compareDate', 'end_date', false]
			// ])
			->requirePresence('amount')
			->add('amount', 'notBlank', [
				'rule' => 'notBlank'
			])
			->add('amount', 'isDecimal', [
				'rule' => ['decimal'],
				'message' => 'Not a valid value'
			])
			->add('amount', 'greaterThan', [
				'rule' => ['comparison', 'isgreater', 0],
				'message' => 'Amount should be more than 0'
			])
			->requirePresence('payment_date')
			->add('payment_date', 'notBlank', [
				'rule' => 'notBlank'
			])
			->add('payment_date', 'validDate', [
				'rule' => ['lessThanToday', true],
				'message' => 'Date should not be later than today'
			])
		;
	}

	public function getFields() {
		$ignoreFields = [];
		$schema = $this->schema();
		$columns = $schema->columns();
		$fields = [];
		foreach ($columns as $col) {
			$fields[$col] = $schema->column($col);
		}
		$visibility = ['view' => true, 'edit' => true, 'index' => true];

		$i = 50;
		foreach($fields as $key => $obj) {
			$fields[$key]['order'] = $i++;
			$fields[$key]['visible'] = $visibility;
			$fields[$key]['field'] = $key;
			$fields[$key]['model'] = $this->alias();
			$fields[$key]['className'] = $this->registryAlias();

			if ($key == 'password') {
				$fields[$key]['visible'] = false;
			}
			/*
			if ($obj['type'] == 'binary') {
				$fields[$key]['visible']['index'] = false;
			}
			*/
		}
		
		$fields[$this->primaryKey()]['type'] = 'hidden';
		foreach ($ignoreFields as $field) {
			if (array_key_exists($field, $fields)) {
				$fields[$field]['visible']['index'] = false;
				$fields[$field]['visible']['view'] = true;
				$fields[$field]['visible']['edit'] = false;
				$fields[$field]['labelKey'] = 'general';
			}
		}
		$this->fields = $fields;
		return $fields;
	}
	
}
