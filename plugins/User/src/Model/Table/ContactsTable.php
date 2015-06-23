<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use App\Model\Traits\OptionsTrait;

class ContactsTable extends AppTable {
	use OptionsTrait;
	public function initialize(array $config) {
		$this->table('user_contacts');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('ContactTypes', ['className' => 'User.ContactTypes']);
	}

	public function indexBeforeAction(Event $event) {
		$this->ControllerAction->addField('description', []);

		$this->fields['contact_type_id']['visible'] = 'false';

		$order = 0;
		$this->ControllerAction->setFieldOrder('description', $order++);
		$this->ControllerAction->setFieldOrder('value', $order++);
		$this->ControllerAction->setFieldOrder('preferred', $order++);
	}

	public function addEditBeforeAction(Event $event) {
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
	}

	public function beforeAction() {
		$this->fields['preferred']['type'] = 'select';
		$this->fields['preferred']['options'] = $this->getSelectOptions('general.yesno');
		if ($this->action == 'index') {
			// todo-mlee: need to implement virtual fields using ContactType Entity _getFullContactTypeName 'full_contact_type_name'
		}		
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		$validator
			// ->add('value', 'ruleMoney',  [
			// 	'rule' => 'tester',
			// 	'provider' => 'custom'
			// ])
			->add('preferred', 'ruleValidatePreferred', [
				'rule' => ['validatePreferred'],
			])
			;

		return $validator;
	}

	public function validationMandatory(Validator $validator) {
		$validator = parent::validationDefault($validator);

		$validator
			// ->add('value', 'ruleMoney',  [
			// 	'rule' => ['money'],
			// ])
			->add('preferred', 'ruleValidatePreferred', [
				'rule' => ['validatePreferred'],
			])
			;
		pr('reached mandatory');
		return $validator;
	}

	public function validationNonMandatory(Validator $validator) {
		$validator = parent::validationDefault($validator);

		$validator
			// ->add('value', 'ruleMoney',  [
			// 	'rule' => ['money']
			// ])
			->add('preferred', 'ruleValidatePreferred', [
				'rule' => ['validatePreferred'],
			])
			;
		return $validator;
	}



}
