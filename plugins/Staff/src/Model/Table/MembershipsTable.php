<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;

class MembershipsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('staff_memberships');
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		return $validator
			->add('issue_date', 'ruleCompareDate', [
				'rule' => ['compareDate', 'expiry_date', false]
			])
			->add('expiry_date', [
			])
		;
	}

	private function setupTabElements() {
		$tabElements = $this->controller->getProfessionalDevelopmentTabElements();
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function afterAction(Event $event) {
		$this->ControllerAction->setFieldOrder(['membership', 'issue_date', 'expiry_date', 'comment']);
		$this->setupTabElements();
	}
}
