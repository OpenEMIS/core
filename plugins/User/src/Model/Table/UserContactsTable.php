<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;

class UserContactsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('ContactTypes', ['className' => 'User.ContactTypes']);
	}

	public function beforeAction() {
		$contactOptions = TableRegistry::get('User.ContactOptions')
			->find('list')
			->find('order')
			->toArray();

		$contactOptionId = key($contactOptions);
		if ($this->request->data($this->aliasField('contact_option_id'))) {
			$contactOptionId = $this->request->data($this->aliasField('contact_option_id'));
		}

		$contactTypes = $this->ContactTypes
			->find('list')
			->find('order')
			->where([$this->ContactTypes->aliasField('contact_option_id')=>$contactOptionId])
			->toArray();

		$this->fields['contact_type_id']['type'] = 'select';
		$this->fields['contact_type_id']['options'] = $contactTypes;
		
		$this->ControllerAction->addField('contact_option_id',['type' => 'select','options'=>$contactOptions]);
		$this->fields['contact_option_id']['attr'] = ['onchange' => "$('#reload').click()"];

		if ($this->action == 'index') {
			// todo-mlee: need to implement virtual fields using ContactType Entity _getFullContactTypeName 'full_contact_type_name'
		}

		
	}

	public function validationDefault(Validator $validator) {
		
		return $validator;
	}

}
