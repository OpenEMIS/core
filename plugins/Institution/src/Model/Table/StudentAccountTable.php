<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class StudentAccountTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('User.Account', ['userRole' => 'Students', 'isInstitution' => true]);
		parent::initialize($config);
	}

	public function validationDefault(Validator $validator) {
		$validator = $this->getAccountValidation($validator);
		return $validator;
	}

}
