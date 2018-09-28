<?php
namespace Directory\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;
use App\Model\Table\AppTable;

class AccountsTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('User.Account', ['permission' => ['Directories', 'Accounts', 'edit']]);
		parent::initialize($config);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		return $validator;
	}


private function setupTabElements()
{
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
    }
    if($this->controller->name == 'Directories' && !empty($guardianID)){
        $StudentGuardianID=$this->request->session()->read('Student.Guardians.primaryKey');
         $newStudentGuardianID=$StudentGuardianID['id'];
        $url = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name];
        $guardianstabElements = [
            'Guardians' => ['text' => __('Relation')],
            'GuardianUser' => ['text' => __('Overview')]
         ];
        $action = 'StudentGuardians';
        $actionUser = 'StudentGuardianUser';
        $guardianstabElements['Guardians']['url'] = array_merge($url, ['action' => $action, 'view', $this->paramsEncode(['id' => $newStudentGuardianID])]);
        $guardianstabElements['GuardianUser']['url'] = array_merge($url, ['action' => $actionUser, 'view', $this->paramsEncode(['id' => $userId, 'StudentGuardians.id' => $newStudentGuardianID])]);
        $guardianId = $userId;
        $tabElements = array_merge($guardianstabElements, $tabElements);
    }

    $this->controller->set('tabElements', $tabElements);
    $this->controller->set('selectedAction', $this->alias());
}

public function afterAction(Event $event, ArrayObject $extra)
{
    $this->setupTabElements();
}

}
