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
        $tabElements = $this->controller->getUserTabElements();
        $session = $this->request->session();
        $guardianId = $session->read('Guardian.Guardians.id');
        if($this->controller->name == 'Directories' && !empty($guardianId)) {
            $tabElements = $this->controller->getUserTabElements(['id' => $guardianId, 'userRole' => 'Guardians']);
        }

        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

}
