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

	// private $defaultStudentProfile = "Student.default_student_profile.jpg";
	// private $defaultStaffProfile = "Staff.default_staff_profile.jpg";

	private $defaultStudentProfileIndex = "<div class='table-thumb'><div class='profile-image-thumbnail'><i class='kd-students'></i></div></div>";
	private $defaultStaffProfileIndex = "<div class='table-thumb'><div class='profile-image-thumbnail'><i class='kd-staff'></i></div></div>";
	private $defaultStudentProfileView = "<div class='profile-image'><i class='kd-students'></i></div>";
	private $defaultStaffProfileView = "<div class='profile-image'><i class='kd-staff'></i></div>";
	private $defaultImgIndexClass = "profile-image-thumbnail";
	private $defaultImgViewClass= "profile-image";
	private $defaultImgMsg = "<p>* Advisable photo dimension 90 by 115px<br>* Format Supported: .jpg, .jpeg, .png, .gif </p>";

	private $specialFields = ['default_identity_type'];

	public $fieldOrder1;
	public $fieldOrder2;

	public function initialize(array $config) {
		$this->table('security_users');
		parent::initialize($config);

		$this->fieldOrder1 = new ArrayObject(['openemis_no', 'first_name', 'middle_name', 'third_name', 'last_name', 'preferred_name', 'gender_id', 'date_of_birth', 'address', 'postal_code']);
		$this->fieldOrder2 = new ArrayObject(['status','modified_user_id','modified','created_user_id','created']);

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

		$this->hasMany('InstitutionSiteStaff', 		['className' => 'Institution.InstitutionSiteStaff', 'foreignKey' => 'security_user_id']);
		$this->hasMany('InstitutionSiteStudents', 	['className' => 'Institution.InstitutionSiteStudents', 'foreignKey' => 'security_user_id']);
		$this->hasMany('Identities', 				['className' => 'User.Identities', 'foreignKey' => 'security_user_id']);
		$this->hasMany('Nationalities', 			['className' => 'User.Nationalities', 'foreignKey' => 'security_user_id']);
		$this->hasMany('SpecialNeeds', 				['className' => 'User.SpecialNeeds', 'foreignKey' => 'security_user_id']);
		$this->hasMany('Contacts', 					['className' => 'User.Contacts', 'foreignKey' => 'security_user_id']);

		$this->hasMany('StudentActivities', 		['className' => 'Student.StudentActivities', 'foreignKey' => 'security_user_id']);
		$this->hasMany('StaffActivities', 			['className' => 'Staff.StaffActivities', 'foreignKey' => 'security_user_id']);

		$this->belongsToMany('SecurityRoles', [
			'className' => 'Security.SecurityRoles',
			'through' => 'Security.SecurityGroupUsers'
		]);

	}

	public function beforeAction(Event $event) {
		$modelName = inflector::singularize($this->controller->name);
		if (strtolower($modelName)=='institution') {
			$modelName = inflector::singularize($this->alias());
		}
        $this->addBehavior('TrackActivity', ['target' => $modelName.'.'.$modelName.'Activities', 'key' => 'security_user_id', 'session' => 'Users.id']);
		
		$this->ControllerAction->field('username', ['visible' => false]);

		$this->ControllerAction->field('super_admin', ['visible' => false]);
		$this->ControllerAction->field('photo_name', ['visible' => false]);
		$this->ControllerAction->field('date_of_death', ['visible' => false]);
		$this->ControllerAction->field('status', ['options' => $this->getSelectOptions('general.active')]);
		$this->ControllerAction->field('photo_content', ['type' => 'image']);
	}

	public function afterAction(Event $event) {
		if (isset($this->action) && in_array($this->action, ['view', 'edit'])) {
			$this->setTabElements();
		}

		if (isset($this->action) && strtolower($this->action) != 'index') {
			$this->Navigation->addCrumb($this->getHeader($this->action));
		}
	}

	public function setTabElements() {
		if ($this->controller->name == 'Institutions') return;
		
		$plugin = $this->controller->plugin;
		$name = $this->controller->name;
		$id = $this->controller->viewVars['_buttons']['edit']['url'][0];
		if ($id=='view' || $id=='edit') {
			if (isset($this->controller->viewVars['_buttons']['edit']['url'][1])) {
				$id = $this->controller->viewVars['_buttons']['edit']['url'][1];
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

		if (!in_array($this->controller->name, ['Students', 'Staff'])) {
			$tabElements[$this->alias] = [
				'url' => ['plugin' => Inflector::singularize($this->controller->name), 'controller' => $this->controller->name, 'action' => $this->alias(), 'view', $id],
				'text' => __('Details')
			];
		}

		$this->controller->set('selectedAction', $this->alias);
        $this->controller->set('tabElements', $tabElements);
	}

	public function indexBeforeAction(Event $event) {
		$this->ControllerAction->field('address', ['visible' => false]);
		$this->ControllerAction->field('postal_code', ['visible' => false]);
		$this->ControllerAction->field('address_area_id', ['visible' => false]);
		$this->ControllerAction->field('birthplace_area_id', ['visible' => false]);
	}

	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $options) {
		$options['finder'] = ['notSuperAdmin' => []];
	}

	public function findNotSuperAdmin(Query $query, array $options) {
		return $query->where([$this->aliasField('super_admin') => 0]);
	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->find('notSuperAdmin');
	}

	public function viewBeforeAction(Event $event) {
		if (array_key_exists('pass', $this->request->params)) {
			$id = reset($this->request->params['pass']);
			$this->ControllerAction->setFieldOrder(['photo_content']);
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

	public function addEditBeforeAction(Event $event) {
		$this->fields['openemis_no']['attr']['readonly'] = true;
		$this->fields['photo_content']['type'] = 'image';
		$this->fields['super_admin']['type'] = 'hidden';
		$this->fields['super_admin']['value'] = 0;
		$this->fields['gender_id']['type'] = 'select';
		$this->fields['gender_id']['options'] = $this->Genders->find('list', ['keyField' => 'id', 'valueField' => 'name'])->toArray();
		$this->fields['address']['type'] = 'text';

		$fieldOrder = array_merge($this->fieldOrder1->getArrayCopy(), $this->fieldOrder2->getArrayCopy());
		$this->ControllerAction->setFieldOrder($fieldOrder);
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
			->order($this->aliasField('id').' DESC')
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
			->allowEmpty('middle_name')
			->allowEmpty('third_name')
			->add('last_name', [
					'ruleCheckIfStringGotNoNumber' => [
						'rule' => 'checkIfStringGotNoNumber',
					]
				])
			->allowEmpty('preferred_name')
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
			->add('address', [])
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
			if(($this->hasBehavior('Student')) && ($this->action == "index")){
				$value = $this->defaultStudentProfileIndex;
			} else if(($this->hasBehavior('Staff')) && ($this->action == "index")){
				$value = $this->defaultStaffProfileIndex;
			} else if(($this->hasBehavior('Student')) && ($this->action == "view")){
				$value = $this->defaultStudentProfileView;
			} else if(($this->hasBehavior('Staff')) && ($this->action == "view")){
				$value = $this->defaultStaffProfileView;
			}
		} else {
			$value = base64_encode( stream_get_contents($fileContent) );//$fileContent;
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

	public function getDefaultImgMsg(){
		return $this->defaultImgMsg;
	}

	public function getDefaultImgIndexClass(){
		return $this->defaultImgIndexClass;
	}

	public function getDefaultImgViewClass(){
		return $this->defaultImgViewClass;
	}

	public function getDefaultImgView(){
		$value = "";
		$controllerName = $this->controller->name;	
		if($this->hasBehavior('Student')){
			$value = $this->defaultStudentProfileView;
		} else if($this->hasBehavior('Staff')){
			$value = $this->defaultStaffProfileView;
		}
		return $value;
	}
}
