<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;
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

	public function validationDefault(Validator $validator)
	{
		$validator = parent::validationDefault($validator);
		
		return $validator
			->add('issue_location',  [
			])
			->add('issue_date', 'ruleCompareDate', [
				'rule' => ['compareDate', 'expiry_date', false]
			])
			->add('expiry_date',  [
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
}