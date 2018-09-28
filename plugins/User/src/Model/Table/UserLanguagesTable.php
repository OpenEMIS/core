<?php
namespace User\Model\Table;

use Cake\Validation\Validator;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;

class UserLanguagesTable extends ControllerActionTable {
	public function initialize(array $config) {
		parent::initialize($config);

        $this->behaviors()->get('ControllerAction')->config('actions.search', false);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('Languages', ['className' => 'Languages']);
	}

	public function beforeAction($event) {
		$this->fields['language_id']['type'] = 'select';
		$gradeOptions = $this->getGradeOptions();
		$this->fields['listening']['type'] = 'select';
		$this->fields['listening']['options'] = $gradeOptions;
		$this->fields['listening']['translate'] = false;
		$this->fields['speaking']['type'] = 'select';
		$this->fields['speaking']['options'] = $gradeOptions;
		$this->fields['speaking']['translate'] = false;
		$this->fields['reading']['type'] = 'select';
		$this->fields['reading']['options'] = $gradeOptions;
		$this->fields['reading']['translate'] = false;
		$this->fields['writing']['type'] = 'select';
		$this->fields['writing']['options'] = $gradeOptions;
		$this->fields['writing']['translate'] = false;
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
		$options = [
			'userRole' => '',
		];

		switch ($this->controller->name) {
			case 'Students':
				$options['userRole'] = 'Students';
				break;
			case 'Staff':
				$options['userRole'] = 'Staff';
				break;
		}

        $tabElements = $this->controller->getUserTabElements($options);
$session = $this->request->session();
$guardianID = $session->read('Guardian.Guardians.id');
$studentID = $session->read('Guardian.Students.id');
if (!empty($guardianID)) {
    $userId = $guardianID;
    $StudentGuardianID=$this->request->session()->read('Student.Guardians.primaryKey');
    $newStudentGuardianID=$StudentGuardianID['id'];
    $url = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name];
    $guardianstabElements = [
        'Guardians' => ['text' => __('Relation')],
        'GuardianUser' => ['text' => __('Overview')]
     ];
    $action = 'StudentGuardians';
    $actionUser = 'StudentGuardianUser';
    if ($this->controller->name == 'Students') {
        $tabElements = $this->controller->getGuardianTabElements($options);
        $action = 'Guardians';
        $actionUser = 'GuardianUser';
    }
    $guardianstabElements['Guardians']['url'] = array_merge($url, ['action' => $action, 'view', $this->paramsEncode(['id' => $newStudentGuardianID])]);
    $guardianstabElements['GuardianUser']['url'] = array_merge($url, ['action' => $actionUser, 'view', $this->paramsEncode(['id' => $userId, 'StudentGuardians.id' => $newStudentGuardianID])]);
    $guardianId = $userId;
    $tabElements = array_merge($guardianstabElements, $tabElements);
}
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', 'Languages');
	}

	public function afterAction(Event $event, $data) {
		$this->setupTabElements();
	}
}
