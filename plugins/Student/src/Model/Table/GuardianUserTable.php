<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Directory\Model\Table\DirectoriesTable as UserTable;

class GuardianUserTable extends UserTable {

	public function addAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        if (!$entity->errors()) {
            $sessionKey = 'Student.Guardians.new';
            if ($this->Session->check($sessionKey)) {
                $guardianData = $this->Session->read($sessionKey);
                $guardianData['guardian_id'] = $entity->id;

                $Guardians = TableRegistry::get('Student.Guardians');
                $Guardians->save($Guardians->newEntity($guardianData));
                $this->Session->delete($sessionKey);
            }
            $event->stopPropagation();

            $controller = $this->controller->name;
            $action = 'Guardians';

            if ($controller == 'Directories') { //this is for Directories/StudentGuardians/ (adding guardian for student through directories)
                $action = 'StudentGuardians';
            }

            $redirect = ['plugin' => $this->controller->plugin, 'controller' => $controller, 'action' => $action, 'index'];

            return $this->controller->redirect($redirect);
        }
	}

	public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
		$this->setupTabElements($entity);

		unset($extra['toolbarButtons']['back']);

        if ($extra['toolbarButtons']->offsetExists('export')) {
            unset($extra['toolbarButtons']['export']);
        }
	}

	public function beforeAction(Event $event, ArrayObject $extra)
	{
        $this->request->query['user_type'] = UserTable::GUARDIAN;
		
		//parent::hideOtherInformationSection($this->controller->name, $this->action);
	}

	public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra) 
    {
		$this->setupTabElements($entity);

        $this->field('nationality_id', ['type' => 'readonly', 'after' => 'date_of_birth']);
        $this->field('identity_type_id', ['type' => 'readonly', 'after' => 'nationality_id']);
        $this->field('identity_number', ['type' => 'readonly', 'after' => 'identity_type_id']);

	}

	public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
		$options['type'] = 'student';
		$tabElements = $this->controller->getStudentGuardianTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		
        // pr($this->fields);
        $this->field('user_type', ['type' => 'hidden', 'value' => UserTable::GUARDIAN]);
		$this->field('nationality_id', ['visible' => 'false']);
        $this->field('identity_type_id', ['visible' => 'false']);
        $this->field('identity_number', ['visible' => 'false']);

		$backUrl = $this->controller->getStudentGuardianTabElements();
		$extra['toolbarButtons']['back']['url']['action'] = $backUrl['Guardians']['url']['action'];
		$extra['toolbarButtons']['back']['url'][0] = 'add';
	}

	private function setupTabElements($entity) 
    {
		$url = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name];

		$tabElements = [
			'Guardians' => ['text' => __('Relation')],
			'GuardianUser' => ['text' => __('General')]
		];
		$action = 'Guardians';
		$actionUser = $this->alias();
		if ($this->controller->name == 'Directories') {
			$action = 'StudentGuardians';
			$actionUser = 'StudentGuardianUser';
		}
		
        $encodedParam = $this->request->params['pass'][1];
        $ids = $this->paramsDecode($encodedParam);

        $guardianId = $ids['id'];
        $studentGuardiansId = $ids['StudentGuardians.id'];

        $tabElements['Guardians']['url'] = array_merge($url, ['action' => $action, 'view', $this->paramsEncode(['id' => $studentGuardiansId])]);
        $tabElements['GuardianUser']['url'] = array_merge($url, ['action' => $actionUser, 'view', $this->paramsEncode(['id' => $entity->id, 'StudentGuardians.id' => $studentGuardiansId])]);

		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}
}
