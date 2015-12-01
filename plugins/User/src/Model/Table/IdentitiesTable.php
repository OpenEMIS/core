<?php
namespace User\Model\Table;

use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Exception;
use DateTime;

class IdentitiesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('user_identities');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('IdentityTypes', ['className' => 'FieldOption.IdentityTypes']);
	}

	public function beforeAction($event) {
		$this->fields['identity_type_id']['type'] = 'select';
	}

	public function indexBeforeAction(Event $event) {
		$this->fields['comments']['visible'] = 'false';
	}

	private function setupTabElements($studentId) {
		$id = !is_null($this->request->query('id')) ? $this->request->query('id') : 0;

		$options = [
			'userRole' => '',
			'action' => $this->action,
			'id' => $id,
			'userId' => $studentId
		];
		$tabElements = $this->controller->getUserTabElements($options);

		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function indexAfterAction(Event $event, $data) {
		$studentId = $this->Session->read('Student.Students.id');
		$this->setupTabElements($studentId);
	}

	public function validationDefault(Validator $validator)
	{
		$validator = parent::validationDefault($validator);
		
		return $validator
			->add('issue_location',  [
			])
			->add('issue_date', 'ruleCompareDate', [
				'rule' => ['compareDate', 'expiry_date', false]
			])
			->add('expiry_date',  [
			])
			->add('number', [])
		;
	}
	
	public function validationNonMandatory(Validator $validator) {
		$this->validationDefault($validator);
		return $validator->allowEmpty('number');
	}
}