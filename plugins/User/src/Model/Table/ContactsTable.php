<?php
namespace User\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
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
		$this->fields['contact_option_id']['attr']['required'] = true;
		
	}

	public function beforeAction() {
		$this->fields['preferred']['type'] = 'select';
		$this->fields['preferred']['options'] = $this->getSelectOptions('general.yesno');
		
		if (strtolower($this->action) != 'index') {
			$this->Navigation->addCrumb($this->getHeader($this->action));
		}
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
			// ->allowEmpty('value')
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


		return $validator;
	}

	public function validationNonMandatory(Validator $validator) {
		$this->validationDefault($validator);
		return $validator->allowEmpty('value');
	}

}
