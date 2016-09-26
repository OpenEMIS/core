<?php
namespace User\Model\Table;

use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Exception;
use DateTime;

class IdentitiesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('user_identities');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('IdentityTypes', ['className' => 'FieldOption.IdentityTypes']);
	}

	public function beforeAction($event) {
		$this->fields['identity_type_id']['type'] = 'select';
	}

	public function indexBeforeAction(Event $event) {
		$this->fields['comments']['visible'] = 'false';
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
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function afterAction(Event $event) {
		$this->setupTabElements();
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		return $validator
			->add('issue_location',  [
			])
			->add('issue_date', 'ruleCompareDate', [
				'rule' => ['compareDate', 'expiry_date', false]
			])
			->add('expiry_date',  [
            ])
            ->add('identity_type_id',  [
            ])
			->add('number', [
	    		'ruleUnique' => [
			        'rule' => ['validateUnique', ['scope' => 'identity_type_id']],
			        'provider' => 'table'
			    ]
		    ]);
		;
	}

	public function validationNonMandatory(Validator $validator) {
		$this->validationDefault($validator);
		return $validator->allowEmpty('number');
	}

	public function afterSave(Event $event, Entity $entity) 
	{
		$this->Users->updateIdentityNumber($entity->security_user_id, $this->getLatestDefaultIdentityNo($entity->security_user_id)); //update identity_number field on security_user table on add/edit action
	}

	public function afterDelete(Event $event, Entity $entity)
	{	
		if ($entity->identity_type_id == $this->IdentityTypes->getDefaultValue()) { //if the delete is done to the default identity type
			$this->Users->updateIdentityNumber($entity->security_user_id, $this->getLatestDefaultIdentityNo($entity->security_user_id)); //update identity_number field on security_user table on delete action
		}
	}

	public function getLatestDefaultIdentityNo($userId)
	{	
		$defaultIdentityType = $this->IdentityTypes->getDefaultValue();

		$latestDefaultIdentityNo = NULL;
		$latestDefaultIdentityNo = $this->find()
										->select('number')
										->where([
											'identity_type_id' => $defaultIdentityType,
											'security_user_id' => $userId
										])
										->order(['created DESC'])
										->first();

		return $latestDefaultIdentityNo->number;
	}
}