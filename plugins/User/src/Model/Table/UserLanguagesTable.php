<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;

class UserLanguagesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('Languages', ['className' => 'Languages']);
	}

	public function beforeAction($event) {
		$this->fields['language_id']['type'] = 'select';
		$gradeOptions = $this->getGradeOptions();
		$this->fields['listening']['type'] = 'select';
		$this->fields['listening']['options'] = $gradeOptions;
		$this->fields['speaking']['type'] = 'select';
		$this->fields['speaking']['options'] = $gradeOptions;
		$this->fields['reading']['type'] = 'select';
		$this->fields['reading']['options'] = $gradeOptions;
		$this->fields['writing']['type'] = 'select';
		$this->fields['writing']['options'] = $gradeOptions;
	}

	public function getGradeOptions() {
		$gradeOptions = array();
		for ($i = 0; $i < 6; $i++) {
			$gradeOptions[$i] = $i;
		}
		return $gradeOptions;
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		return $validator
			->add('listening', 'ruleRange', [
				'rule' => ['range', -1, 6]
			])
			->add('speaking', 'ruleRange', [
				'rule' => ['range', -1, 6]
			])
			->add('reading', 'ruleRange', [
				'rule' => ['range', -1, 6]
			])
			->add('writing', 'ruleRange', [
				'rule' => ['range', -1, 6]
			])
		;
	}

	private function setupTabElements() {
		if ($this->controller->name == 'Students') {
			$tabElements = $this->controller->getUserTabElements();
			$this->controller->set('tabElements', $tabElements);
			$this->controller->set('selectedAction', 'Languages');
		}
	}

	public function indexAfterAction(Event $event, $data) {
		$this->setupTabElements();
	}
}
