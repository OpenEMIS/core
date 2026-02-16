<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
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
	public function initialize(array $config): void {
		$this->setTable('student_fees');
		parent::initialize($config);
		
		$this->belongsTo('InstitutionFees', ['className' => 'Institution.InstitutionFees']);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('CreatedBy', ['className' => 'User.Users', 'foreignKey' => 'created_user_id']);

		$this->fields = $this->getFields();
	}

	public function validationDefault(Validator $validator): Validator {
		$validator = parent::validationDefault($validator);
		$validator->setProvider('custom', $this);
		return $validator
			->requirePresence('amount')
			->add('amount', 'notBlank', [
				'rule' => 'notBlank'
			])
			->add('amount', 'isDecimal', [
				'rule' => ['decimal'],
				'message' => 'Not a valid value'
			])
			->add('amount', 'greaterThan', [
				//'rule' => ['comparison', 'isgreater', 0],//POCOR-7485 the way of comparison removed in cakephp 4
				'rule' => function ($value, $context) {
	                return $value > 0;
	            },
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
		$schema = $this->getSchema();
		$columns = $schema->columns();
		$fields = [];
		foreach ($columns as $col) {
			$fields[$col] = $schema->getColumn($col);
		}
		$visibility = ['view' => true, 'edit' => true, 'index' => true];

		$i = 50;
		foreach($fields as $key => $obj) {
			$fields[$key]['order'] = $i++;
			$fields[$key]['visible'] = $visibility;
			$fields[$key]['field'] = $key;
			$fields[$key]['model'] = $this->getAlias();
			$fields[$key]['className'] = $this->getRegistryAlias();

			if ($key == 'password') {
				$fields[$key]['visible'] = false;
			}
			/*
			if ($obj['type'] == 'binary') {
				$fields[$key]['visible']['index'] = false;
			}
			*/
		}
		
		$fields[$this->getPrimaryKey()]['type'] = 'hidden';
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
	
	public function setFieldOrder($field, $order=0) {
		$fields = $this->fields;
		if (is_array($field)) {
			foreach ($field as $key) {
				$fields[$key]['order'] = $order++;
			}
			uasort($fields, [$this, 'sortFields']);
		}
		$this->fields = $fields;
	}

	public static function sortFields($a, $b) {
		if (isset($a['order']) && isset($b['order'])) {
			return $a['order'] >= $b['order'];
		} else {
			return true;
		}
	}

}
