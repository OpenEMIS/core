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

class StaffAccountTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('User.Account', ['userRole' => 'Staff', 'isInstitution' => true, 'permission' => ['Institutions', 'StaffAccount', 'edit']]);
		parent::initialize($config);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		return $validator;
	}

    public function onUpdateFieldUsername(Event $event, array $attr, $action, Request $request) {
        $editStaffUsername = $this->AccessControl->check(['Institutions', 'StaffAccountUsername', 'edit']);

        if ($editStaffUsername) {
            $attr['type'] = 'string';
            return $attr;
        }
    }
}
