<?php
namespace User\Model\Table;

use ArrayObject;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class UsersTable extends AppTable {
	use OptionsTrait;

	const defaultWidth = 90;
	const defaultHeight = 115;
	const defaultStudentProfile = "Student.default_student_profile.jpg";
	const defaultStaffProfile = "Staff.default_staff_profile.jpg";
	private $specialFields = ['default_identity_type'];

	public function initialize(array $config) {
		$this->table('security_users');
		parent::initialize($config);

		$this->addBehavior('ControllerAction.FileUpload', [
			'name' => 'photo_name',
			'content' => 'photo_content',
			'size' => '2MB',
			'allowEmpty' => true,
			'useDefaultName' => false
		]);

		$this->belongsTo('Genders', ['className' => 'User.Genders']);
		$this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
		$this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);

		$this->hasMany('InstitutionSiteStaff', ['className' => 'Institution.InstitutionSiteStaff', 'foreignKey' => 'security_user_id']);
		$this->hasMany('InstitutionSiteStudents', ['className' => 'Institution.InstitutionSiteStudents', 'foreignKey' => 'security_user_id']);
		$this->hasMany('InstitutionSiteStaff', ['className' => 'Institution.InstitutionSiteStaff', 'foreignKey' => 'security_user_id']);
		$this->hasMany('Identities', ['className' => 'User.Identities', 'foreignKey' => 'security_user_id']);
		$this->hasMany('user_Nationalities', ['className' => 'User.user_Nationalities', 'foreignKey' => 'security_user_id']);
		$this->hasMany('SpecialNeeds', ['className' => 'User.SpecialNeeds', 'foreignKey' => 'security_user_id']);
		$this->hasMany('Contacts', ['className' => 'User.Contacts', 'foreignKey' => 'security_user_id']);

		$this->belongsToMany('SecurityRoles', [
			'className' => 'Security.SecurityRoles',
			'through' => 'Security.SecurityGroupUsers'
		]);
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('username', ['visible' => false]);

		$this->ControllerAction->field('super_admin', ['visible' => false]);
		$this->ControllerAction->field('photo_name', ['visible' => false]);
		$this->ControllerAction->field('date_of_death', ['visible' => false]);
		$this->ControllerAction->field('status', ['options' => $this->getSelectOptions('general.active')]);
		if (in_array($this->action, ['view', 'edit'])) {
			$this->setTabElements();
		}
	}

	public function indexBeforeAction(Event $event) {
		$this->ControllerAction->field('photo_content', ['visible' => false]);
		$this->ControllerAction->field('address', ['visible' => false]);
		$this->ControllerAction->field('postal_code', ['visible' => false]);
		$this->ControllerAction->field('address_area_id', ['visible' => false]);
		$this->ControllerAction->field('birthplace_area_id', ['visible' => false]);
	}

	public function indexBeforePaginate(Event $event, Request $request, array $options) {
		$options['finder'] = ['notSuperAdmin' => []];
		return $options;
	}

	public function findNotSuperAdmin(Query $query, array $options) {
		return $query->where([$this->aliasField('super_admin') => 0]);
	}

	public function viewEditBeforeQuery(Event $event, Query $query, array $contain) {
		$query->find('notSuperAdmin');
		return [$query, $contain];
	}

	public function viewBeforeAction(Event $event) {
		if (array_key_exists('pass', $this->request->params)) {
			$id = reset($this->request->params['pass']);
		}

		// would be 'Student' or 'Staff'
		$name = $this->controller->name;
		$roleName = Inflector::singularize($name);
		if (isset($id)) {
			$this->Session->write($roleName.'.security_user_id', $id);
		} else {
			$id = $this->Session->read($roleName.'.security_user_id');
		}
	}

	public function setTabElements() {
		$plugin = $this->controller->plugin;
		$name = $this->controller->name;

		if (array_key_exists('pass', $this->request->params)) {
			if ($this->controller->name == 'Securities') {
				$id = $this->request->params['pass'][1];
			} else {
				$id = $this->request->params['pass'][0];
			}
		}

		$tabElements = [
			$this->alias => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'view', $id],
				'text' => __('Details')
			],
			'Accounts' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Accounts', 'view', $id],
				'text' => __('Account')	
			]
		];

		$this->controller->set('selectedAction', $this->alias);
        $this->controller->set('tabElements', $tabElements);
	}

	public function addBeforeAction(Event $event) {
		// if ($this->Session->check('Institutions.id')) {
		// 	$institutionId = $this->Session->read('Institutions.id');
		// } else {
		// 	// todo-mlee need to put correct alert saying need to select institution first
		$name = $this->controller->name;
		if (in_array($name, ['Students', 'Staff'])) {
			$this->ControllerAction->addField('institution_site_'.strtolower($name).'.0.institution_site_id', [
				'type' => 'hidden', 
				'value' => 0
			]);
			$this->fields['openemis_no']['attr']['readonly'] = true;
			$this->fields['openemis_no']['attr']['value'] = $this->getUniqueOpenemisId(['model'=>Inflector::singularize($name)]);
		}


		$this->ControllerAction->setFieldOrder(['openemis_no', 'first_name', 'middle_name', 'third_name', 'last_name', 'preferred_name', 'address', 'postal_code', 'gender_id', 'date_of_birth',
			// mandatory fields inserted here if behavior attached
			'status','modified_user_id','modified','created_user_id','created'
		]);
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

	public function addEditBeforeAction(){
		$this->fields['photo_content']['type'] = 'image';
		$this->fields['super_admin']['type'] = 'hidden';
		$this->fields['super_admin']['value'] = 0;
		$this->fields['gender_id']['type'] = 'select';
		$this->fields['gender_id']['options'] = $this->Genders->find('list', ['keyField' => 'id', 'valueField' => 'name'])->toArray();//
	}

	public function addEditBeforePatch(Event $event, Entity $entity, array $data, array $options) {
		$options['associated'] = ['InstitutionSiteStudents', 'InstitutionSiteStaff', 'Identities', 'user_Nationalities', 'SpecialNeeds', 'Contacts'];
		// $options['validate'] = 'mandatory';
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
			->allowEmpty('username')
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
			->allowEmpty('photo_content')
				->add('photo_content', [
					'ruleCheckSelectedFileAsImage' => [
							'rule' => 'checkSelectedFileAsImage',
							'message' => 'Please upload image format files. Eg. jpg, png, gif.'
					],
					'ruleCheckIfImageExceedsUploadSize' => [
							'rule' => 'checkIfImageExceedsUploadSize',
							'message' => 'Uploaded file exceeds 2MB in size.'
					]
				])
// password
// newPassword
// retypeNewPassword

				// todo-mlee: sort out saving for user name and password
			;

			// ->requirePresence('username',false);

			

			

		return $validator;
	}

	public function onGetPhotoContent(Event $event, Entity $entity) {
		$fileContent = $entity->photo_content;
		$value = "";

		if(empty($fileContent) && is_null($fileContent)) {
			$controllerName = $this->controller->name;	
			if($controllerName == "Students"){
				$value = self::defaultStudentProfile;
			} else if($controllerName == "Staff") {
				$value = self::defaultStaffProfile;
			}
		} else {
			$value = $fileContent;
		}

		return $value;
	}

	public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true) {
		if(in_array($field, $this->specialFields)){
			if($field == 'default_identity_type') {
				$IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
				$defaultIdentity = $IdentityType
								   ->find()
								   ->contain(['FieldOptions'])
								   ->where(['FieldOptions.code' => 'IdentityTypes']) //, 'IdentityTypes.default' => 1
								   ->order(['IdentityTypes.default DESC'])
								   ->first();
				if($defaultIdentity)
					$value = $defaultIdentity->name;

				return (!empty($value)) ? $value : parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
			}	
		} else {
			return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
		}
	}
}
