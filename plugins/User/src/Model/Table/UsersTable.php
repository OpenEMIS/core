<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class UsersTable extends AppTable {
	public function initialize(array $config) {
		$this->table('security_users');
		parent::initialize($config);
		$this->addBehavior('ControllerAction.FileUpload');
		// $this->addBehavior('User.Mandatory',['userRole'=>'Student']);

		$this->belongsTo('Genders', ['className' => 'User.Genders']);
		$this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
		$this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);

		$this->hasMany('InstitutionSiteStaff', ['className' => 'Institution.InstitutionSiteStaff', 'foreignKey' => 'security_user_id']);
		$this->hasMany('InstitutionSiteStudents', ['className' => 'Institution.InstitutionSiteStudents', 'foreignKey' => 'security_user_id']);
		$this->hasMany('InstitutionSiteStaff', ['className' => 'Institution.InstitutionSiteStaff', 'foreignKey' => 'security_user_id']);
		$this->hasMany('Identities', ['className' => 'User.UserIdentities', 'foreignKey' => 'security_user_id']);
		$this->hasMany('Nationalities', ['className' => 'User.UserNationalities', 'foreignKey' => 'security_user_id']);
		$this->hasMany('SpecialNeeds', ['className' => 'User.UserSpecialNeeds', 'foreignKey' => 'security_user_id']);
		$this->hasMany('Contacts', ['className' => 'User.UserContacts', 'foreignKey' => 'security_user_id']);
	}

	public function viewBeforeAction(Event $event) {
		if (array_key_exists('pass', $this->controller->request->params)) {
			$id = reset($this->controller->request->params['pass']);
		}

		// would be 'Student' or 'Staff'
		$roleName = Inflector::singularize($this->controller->request->params['controller']);
		if (isset($id)) {
			$this->ControllerAction->Session->write($roleName.'.security_user_id', $id);
		} else {
			$id = $this->ControllerAction->Session->read($roleName.'.security_user_id');
		}
	}

	public function addBeforeAction(Event $event) {
		// if ($this->Session->check('Institutions.id')) {
		// 	$institutionId = $this->Session->read('Institutions.id');
		// } else {
		// 	// todo-mlee need to put correct alert saying need to select institution first
		// 	$action = $this->ControllerAction->buttons['index']['url'];
		// 	$this->controller->redirect($action);
		// 	return false;
		// }

		if (in_array($this->controller->name, ['Students','Staff'])) {
			// $this->ControllerAction->addField('institution_site_'.strtolower($this->controller->name).'.0.institution_site_id', [
			// 	'type' => 'hidden', 
			// 	'value' =>$institutionId
			// ]);
			$this->fields['openemis_no']['attr']['readonly'] = true;
			$this->fields['openemis_no']['attr']['value'] = $this->getUniqueOpenemisId(['model'=>Inflector::singularize($this->controller->name)]);
		}

		$this->fields['photo_content']['type'] = 'image';

		$this->fields['super_admin']['type'] = 'hidden';
		$this->fields['super_admin']['value'] = 0;
		$this->fields['status']['type'] = 'select';
		$this->fields['status']['options'] = $this->getStatus();
		$this->fields['gender_id']['type'] = 'select';
		$this->fields['gender_id']['options'] = $this->Genders->find('list', ['keyField' => 'id', 'valueField' => 'name'])->toArray();

		// contact 'mandatory field'
		$contactOptions = TableRegistry::get('User.ContactTypes')
			->find('list', ['keyField' => 'id', 'valueField' => 'full_contact_type_name'])
			->find('withContactOptions')
			->toArray();
		$this->ControllerAction->addField('contact_type', [
			'type' => 'select', 
			'fieldName' => 'Users.user_contacts.0.contact_type_id',
			'options' => $contactOptions
		]);
		$this->ControllerAction->addField('contact_value', [
			'type' => 'string',
			'fieldName' => 'Users.user_contacts.0.value'
		]);

		$Countries = TableRegistry::get('FieldOption.Countries');
		$nationalityOptions = $Countries->getList()->toArray();
		$this->ControllerAction->addField('nationality', [
			'type' => 'select', 
			'options' => $nationalityOptions, 
			'onChangeReload' => 'changeNationality',
			'attr' => ['name' => 'Users[user_nationalities][0][country_id]']
		]);

		// identity 'mandatory field'
		$identityTypeOptions = TableRegistry::get('FieldOption.IdentityTypes')->getList();
		$this->ControllerAction->addField('identity_type', [
			'type' => 'select', 
			'fieldName' => 'Users.user_identities.0.identity_type_id',
			'options' => $identityTypeOptions->toArray()
		]);
		$this->ControllerAction->addField('identity_number', [
			'type' => 'string',
			'fieldName' => 'Users.user_identities.0.number'
		]);

		// special need 'mandatory field'
		$specialNeedOptions = TableRegistry::get('FieldOption.SpecialNeedTypes')->getList();
		$this->ControllerAction->addField('special_need', [
			'type' => 'select', 
			'fieldName' => 'Users.user_special_needs.0.special_need_type_id',
			'options' => $specialNeedOptions->toArray()
		]);
		$this->ControllerAction->addField('special_need_comment', [
			'type' => 'string',
			'fieldName' => 'Users.user_special_needs.0.comment'
		]);

		$order = 0;
		$this->ControllerAction->setFieldOrder('openemis_no', $order++);
		$this->ControllerAction->setFieldOrder('first_name', $order++);
		$this->ControllerAction->setFieldOrder('middle_name', $order++);
		$this->ControllerAction->setFieldOrder('third_name', $order++);
		$this->ControllerAction->setFieldOrder('last_name', $order++);
		$this->ControllerAction->setFieldOrder('preferred_name', $order++);
		$this->ControllerAction->setFieldOrder('address', $order++);
		$this->ControllerAction->setFieldOrder('postal_code', $order++);
		$this->ControllerAction->setFieldOrder('gender_id', $order++);
		$this->ControllerAction->setFieldOrder('date_of_birth', $order++);

		if (array_key_exists('contact_type', $this->fields)) {
			$this->ControllerAction->setFieldOrder('contact_type', $order++);
			$this->ControllerAction->setFieldOrder('contact_value', $order++);
		}
		if (array_key_exists('nationality', $this->fields)) {
			$this->ControllerAction->setFieldOrder('nationality', $order++);
		}
		if (array_key_exists('identity_type', $this->fields)) {
			$this->ControllerAction->setFieldOrder('identity_type', $order++);
			$this->ControllerAction->setFieldOrder('identity_number', $order++);
		}
		if (array_key_exists('special_need', $this->fields)) {
			$this->ControllerAction->setFieldOrder('special_need', $order++);
			$this->ControllerAction->setFieldOrder('special_need_comment', $order++);
		}

		$this->ControllerAction->setFieldOrder('status', $order++);

		$this->ControllerAction->setFieldOrder('modified_user_id', $order++);
		$this->ControllerAction->setFieldOrder('modified', $order++);
		$this->ControllerAction->setFieldOrder('created_user_id', $order++);
		$this->ControllerAction->setFieldOrder('created', $order++);
	}

	public function addOnChangeNationality(Event $event, Entity $entity, array $data, array $options) {
		$Countries = TableRegistry::get('FieldOption.Countries');
		$countryId = $data['Users']['user_nationalities'][0]['country_id'];
		$country = $Countries->findById($countryId)->first();
		$defaultIdentityType = $country->identity_type_id;
		if (is_null($defaultIdentityType)) {
			$IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');
			$defaultIdentityType = $IdentityTypes->getDefaultValue();
		}

		$this->fields['nationality']['default'] = $data['Users']['user_nationalities'][0]['country_id'];

		// overriding the  previous input to put in default identities
		$this->fields['identity_type']['default'] = $defaultIdentityType;
		$data['Users']['user_identities'][0]['identity_type_id'] = $defaultIdentityType;

		$options['associated'] = [
			'InstitutionSiteStudents' => ['validate' => false],
			'InstitutionSiteStaff' => ['validate' => false],
			'UserIdentities' => ['validate' => false],
			'UserNationalities' => ['validate' => false],
			'UserSpecialNeeds' => ['validate' => false],
			'UserContacts' => ['validate' => false]
		];

		return compact('entity', 'data', 'options');
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		$Countries = TableRegistry::get('FieldOption.Countries');
		$defaultCountry = $Countries->getDefaultEntity();
		
		$this->fields['nationality']['default'] = $defaultCountry->id;

		$defaultIdentityType = $defaultCountry->identity_type_id;
		if (is_null($defaultIdentityType)) {
			$IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');
			$defaultIdentityType = $IdentityTypes->getDefaultValue();
		}
		$this->fields['identity_type']['default'] = $defaultIdentityType;

		return $entity;
	}

	public function setIdentityBasedOnCountry($countryEntity) {

	}

	public function addEditBeforePatch(Event $event, Entity $entity, array $data, array $options) {
		$options['associated'] = ['InstitutionSiteStudents', 'InstitutionSiteStaff', 'UserIdentities', 'UserNationalities', 'UserSpecialNeeds', 'UserContacts'];
		return compact('entity', 'data', 'options');
	}

	public function getUniqueOpenemisId($options = []) {
		$prefix = '';
		
		if (array_key_exists('model', $options)) {
			switch ($options['model']) {
				case 'Student': case 'Staff':
					$prefix = TableRegistry::get('ConfigItems')->value(strtolower($options['model']).'_prefix');
					$prefix = explode(",", $prefix);
					$prefix = ($prefix[1] > 0)? $prefix[0]: '';
					break;
			}
		}
		
		$latest = $this->find()
			->order('Users.id DESC')
			->first();

		$latestOpenemisNo = $latest['SecurityUser']['openemis_no'];
		if(empty($prefix)){
			$latestDbStamp = $latestOpenemisNo;
		}else{
			$latestDbStamp = substr($latestOpenemisNo, strlen($prefix));
		}
		
		$currentStamp = time();
		if($latestDbStamp >= $currentStamp){
			$newStamp = $latestDbStamp + 1;
		}else{
			$newStamp = $currentStamp;
		}

		return $prefix.$newStamp;
	}

	public function getStatus() {
		return array(0 => __('Inactive', true), 1 => __('Active', true));
	}

	public function validationDefault(Validator $validator) {
		$validator
			->add('first_name', [
					'ruleCheckIfStringGotNoNumber' => [
						'rule' => 'checkIfStringGotNoNumber',
					],
					'ruleNotBlank' => [
						'rule' => 'notBlank',
					]
				])
			->add('middle_name', [
					'ruleCheckIfStringGotNoNumber' => [
						'rule' => 'checkIfStringGotNoNumber',
					]
				])
			->add('third_name', [
					'ruleCheckIfStringGotNoNumber' => [
						'rule' => 'checkIfStringGotNoNumber',
					]
				])
			->add('last_name', [
					'ruleCheckIfStringGotNoNumber' => [
						'rule' => 'checkIfStringGotNoNumber',
					]
				])
			->add('preferred_name', [
					'ruleCheckIfStringGotNoNumber' => [
						'rule' => 'checkIfStringGotNoNumber',
					]
				])
			->add('openemis_no', [
					'ruleUnique' => [
						'rule' => 'validateUnique',
						'provider' => 'table',
					]
				])
			->add('username', [
				'ruleUnique' => [
					'rule' => 'validateUnique',
					'provider' => 'table',
				],
				'ruleAlphanumeric' => [
				    'rule' => 'alphanumeric',
				]
			])
			->add('password', [
				// 'ruleUnique' => [
				// 	'rule' => 'validateUnique',
				// 	'provider' => 'table',
				// ],
				// 'ruleAlphanumeric' => [
				//     'rule' => 'alphanumeric',
				// ]


				// 'ruleChangePassword' => [
				// 	'rule' => array('changePassword',false),
				// 	 // authenticate changePassword ('new password', retyped password) // validate behaviour
				// 	// 'on' => 'update',
				// ],
				// 'ruleCheckUsernameExists' => array(
				// 	'rule' => array('checkUsernameExists'),
				// 	'message' => 'Please enter a valid password'
				// ),
				// 'ruleMinLength' => array(
				// 	'rule' => array('minLength', 6),
				// 	'on' => 'create',
				// 	'allowEmpty' => true,
				// 	'message' => 'Password must be at least 6 characters'
				// )
			])
// password
// newPassword
// retypeNewPassword

				// todo-mlee: sort out saving for user name and password
			;

			// ->requirePresence('username',false);



			// ->add('openemis_no', [
			// 		'ruleUnique' => [
			// 			'rule' => 'validateUnique',
			// 			'provider' => 'table',
			// 		]
			// 	])
			

			

		return $validator;
	}

}