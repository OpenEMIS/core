<?php
namespace User\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Network\Request;
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
		if ($this->controller->name != 'Preferences') {
			$this->controller->set('selectedAction', $this->alias());
		} else {
			$this->controller->set('selectedAction', 'Contacts');
		}
		
		$this->controller->set('tabElements', $tabElements);
	}

	public function afterAction(Event $event) {
		$this->setupTabElements();
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		$this->ControllerAction->field('contact_option_id', ['type' => 'select']);
		$this->ControllerAction->field('contact_type_id', ['type' => 'select']);
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$contactOptionId = $this->ContactTypes->get($entity->contact_type_id)->contact_option_id;
		$entity->contact_option_id = $contactOptionId;
		$this->request->query['contact_option'] = $contactOptionId;
	}

	public function beforeAction(Event $event) {
		$this->fields['preferred']['type'] = 'select';
		$this->fields['preferred']['options'] = $this->getSelectOptions('general.yesno');
	}

	// public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
	// 	//Required by patchEntity for associated data
	// 	$newOptions = [];
	// 	$newOptions['validate'] = 'default';

	// 	$arrayOptions = $options->getArrayCopy();
	// 	$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
	// 	$options->exchangeArray($arrayOptions);
	// }

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		// pr('validationDefault');
		// var_dump($validator->hasField('value'));
		$validator->remove('value', 'notBlank');
		$validator
			->requirePresence('contact_option_id')
			->add('value', 'ruleValidateNumeric',  [
				'rule' => ['numeric', 'notBlank'],
				'on' => function ($context) {
					$contactOptionId = (array_key_exists('contact_option_id', $context['data']))? $context['data']['contact_option_id']: null;
					if (is_null($contactOptionId)) {
						if (array_key_exists('contact_type_id', $context['data'])) {
							$contactTypeId = $context['data']['contact_type_id'];
							$query = $this->ContactTypes
								->find()
								->where([$this->ContactTypes->aliasField($this->ContactTypes->primaryKey()) => $contactTypeId])
								->first();
								;
							if ($query) {
								$contactOptionId = $query->contact_option_id;
							}
						}
					}
					return in_array($contactOptionId, [1,2,3]);
				},
			])
			->add('value', 'ruleValidateEmail',  [
				'rule' => ['email', 'notBlank'],
				'on' => function ($context) {
					$contactOptionId = (array_key_exists('contact_option_id', $context['data']))? $context['data']['contact_option_id']: null;
					if (is_null($contactOptionId)) {
						if (array_key_exists('contact_type_id', $context['data'])) {
							$contactTypeId = $context['data']['contact_type_id'];
							$query = $this->ContactTypes
								->find()
								->where([$this->ContactTypes->aliasField($this->ContactTypes->primaryKey()) => $contactTypeId])
								->first();
								;
							if ($query) {
								$contactOptionId = $query->contact_option_id;
							}
						}
					}
					return ($contactOptionId == 4);
				},
			])
			->add('value', 'ruleValidateEmergency',  [
				'rule' => 'notBlank',
				'on' => function ($context) {
					$contactOptionId = (array_key_exists('contact_option_id', $context['data']))? $context['data']['contact_option_id']: null;
					if (is_null($contactOptionId)) {
						if (array_key_exists('contact_type_id', $context['data'])) {
							$contactTypeId = $context['data']['contact_type_id'];
							$query = $this->ContactTypes
								->find()
								->where([$this->ContactTypes->aliasField($this->ContactTypes->primaryKey()) => $contactTypeId])
								->first();
								;
							if ($query) {
								$contactOptionId = $query->contact_option_id;
							}
						}
					}
					return ($contactOptionId == 5);
				},
			])
			// end of value validators
			->add('preferred', 'ruleValidatePreferred', [
				'rule' => ['validatePreferred'],
			])
			;

		// validation code must always be set because this is also being used by prefererences 'usercontacts'
		$this->setValidationCode('value.ruleNotBlank', 'User.Contacts');
		$this->setValidationCode('value.ruleValidateNumeric', 'User.Contacts');
		$this->setValidationCode('value.ruleValidateEmail', 'User.Contacts');
		$this->setValidationCode('value.ruleValidateEmergency', 'User.Contacts');
		$this->setValidationCode('preferred.ruleValidatePreferred', 'User.Contacts');


		return $validator;
	}

	public function validationNonMandatory(Validator $validator) {
		$this->validationDefault($validator);
		return $validator->allowEmpty('value');
	}

	public function onUpdateFieldContactOptionId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			$contactOptions = TableRegistry::get('User.ContactOptions')
			->find('list')
			->find('order')
			->toArray();

			$attr['options'] = $contactOptions;
			$attr['onChangeReload'] = 'changeContactOption';
			$attr['attr']['required'] = true;
		}	
		return $attr;
	}

	public function onUpdateFieldContactTypeId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			if (array_key_exists('contact_option', $request->query)) {
				$contactOptionId = $request->query['contact_option'];
				$contactTypes = $this->ContactTypes
					->find('list')
					->find('order')
					->where([$this->ContactTypes->aliasField('contact_option_id') => $contactOptionId])
					->toArray();
			} else {
				$contactTypes = [];
			}
			$attr['options'] = $contactTypes;
		}
		return $attr;
	}

	public function addEditOnChangeContactOption(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['contact_option']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('contact_option_id', $request->data[$this->alias()])) {
					$request->query['contact_option'] = $request->data[$this->alias()]['contact_option_id'];
				}
			}
		}
	}
}
