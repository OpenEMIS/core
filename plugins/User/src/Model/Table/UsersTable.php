<?php
namespace User\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\UserTrait;
use Cake\I18n\Time;
use Cake\Network\Session;
use Cake\Datasource\ConnectionManager;

class UsersTable extends AppTable {
	use OptionsTrait;
	use UserTrait;

	// private $defaultStudentProfile = "Student.default_student_profile.jpg";
	// private $defaultStaffProfile = "Staff.default_staff_profile.jpg";

	private $defaultStudentProfileIndex = "<div class='table-thumb'><div class='profile-image-thumbnail'><i class='kd-students'></i></div></div>";
	private $defaultStaffProfileIndex = "<div class='table-thumb'><div class='profile-image-thumbnail'><i class='kd-staff'></i></div></div>";
	private $defaultGuardianProfileIndex = "<div class='table-thumb'><div class='profile-image-thumbnail'><i class='fa fa-user'></i></div></div>";
	private $defaultUserProfileIndex = "<div class='table-thumb'><div class='profile-image-thumbnail'><i class='fa fa-user'></i></div></div>";

	private $defaultStudentProfileView = "<div class='profile-image'><i class='kd-students'></i></div>";
	private $defaultStaffProfileView = "<div class='profile-image'><i class='kd-staff'></i></div>";
	private $defaultGuardianProfileView = "<div class='profile-image'><i class='fa fa-user'></i></div>";
	private $defaultUserProfileView = "<div class='profile-image'><i class='fa fa-user'></i></div>";


	private $defaultImgIndexClass = "profile-image-thumbnail";
	private $defaultImgViewClass= "profile-image";
	private $photoMessage = 'Advisable photo dimension 90 by 115px';
	private $formatSupport = 'Format Supported: ';
	private $defaultImgMsg = "<p>* %s <br>* %s.jpg, .jpeg, .png, .gif </p>";

	public $fieldOrder1;
	public $fieldOrder2;

	public function initialize(array $config) {
		$this->table('security_users');
		parent::initialize($config);

		self::handleAssociations($this);

		$this->fieldOrder1 = new ArrayObject(['photo_content', 'openemis_no', 'first_name', 'middle_name', 'third_name', 'last_name', 'preferred_name', 'gender_id', 'date_of_birth', 'address', 'postal_code']);
		$this->fieldOrder2 = new ArrayObject(['status','modified_user_id','modified','created_user_id','created']);

		$this->addBehavior('ControllerAction.FileUpload', [
			'name' => 'photo_name',
			'content' => 'photo_content',
			'size' => '2MB',
			'contentEditable' => true,
			'allowable_file_types' => 'image',
			'useDefaultName' => true
		]);

		$this->addBehavior('Area.Areapicker');
		$this->addBehavior('User.AdvancedNameSearch');
		$this->addBehavior('Restful.RestfulAccessControl', [
        	'StaffRoom' => ['index', 'add']
        ]);
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'Model.Auth.createAuthorisedUser' => 'createAuthorisedUser',
			'Model.Users.afterLogin' => 'afterLogin',
			'Model.Users.updateLoginLanguage' => 'updateLoginLanguage',
			'Model.UserNationalities.afterSave' => 'userNationalitiesAfterSaveDelete',
			'Model.UserNationalities.afterDelete' => 'userNationalitiesAfterSaveDelete',
			'Model.UserIdentities.afterSave' => 'userNationalitiesAfterSaveDelete',
			'Model.UserIdentities.afterDelete' => 'userNationalitiesAfterSaveDelete'
		];

		$events = array_merge($events, $newEvent);
		return $events;
	}

	public function updateLoginLanguage(Event $event, $user, $language)
	{
		if ($user['preferred_language'] != $language) {
			$user = $this->get($user['id']);
			$user->preferred_language = $language;
			$this->save($user);
		}
	}

	public function afterLogin(Event $event, $user)
    {
    	$user['last_login'] = new Time();
    	$controller = $event->subject();
    	$SSO = $controller->SSO;
    	$Cookie = $controller->Localization->getCookie();
    	$session = $controller->request->session();
    	if ($session->read('System.language_menu') && $SSO->getAuthenticationType() != 'Local') {
    		if (empty($user['preferred_language'])) {
    			$user['preferred_language'] = 'en';
    		}
    		$Cookie->write('System.language', $user['preferred_language']);
    	} else {
    		$user['preferred_language'] = $session->read('System.language');
    	}
    	$userEntity = $this->newEntity($user);
    	$this->save($userEntity);
    }

	public function createAuthorisedUser(Event $event, $userName, array $userInfo) {
        return false;
	}

	public static function handleAssociations($model) {
		$model->belongsTo('Genders', 		 		['className' => 'User.Genders']);
		$model->belongsTo('AddressAreas', 	 		['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
		$model->belongsTo('BirthplaceAreas', 		['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);
		$model->belongsTo('MainNationalities',		['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);
		$model->belongsTo('MainIdentityTypes',		['className' => 'FieldOption.IdentityTypes', 'foreignKey' => 'identity_type_id']);

		$model->hasMany('Identities', 				['className' => 'User.Identities',		'foreignKey' => 'security_user_id', 'dependent' => true]);
		$model->hasMany('Nationalities', 			['className' => 'User.UserNationalities',	'foreignKey' => 'security_user_id', 'dependent' => true]);
		$model->hasMany('SpecialNeeds', 			['className' => 'User.SpecialNeeds',	'foreignKey' => 'security_user_id', 'dependent' => true]);
		$model->hasMany('Contacts', 				['className' => 'User.Contacts',		'foreignKey' => 'security_user_id', 'dependent' => true]);
		$model->hasMany('Attachments', 				['className' => 'User.Attachments',		'foreignKey' => 'security_user_id', 'dependent' => true]);
		$model->hasMany('BankAccounts', 			['className' => 'User.BankAccounts',	'foreignKey' => 'security_user_id', 'dependent' => true]);
		$model->hasMany('Comments', 				['className' => 'User.Comments',		'foreignKey' => 'security_user_id', 'dependent' => true]);
		$model->hasMany('Languages', 				['className' => 'User.UserLanguages',	'foreignKey' => 'security_user_id', 'dependent' => true]);
		$model->hasMany('Awards', 					['className' => 'User.Awards',			'foreignKey' => 'security_user_id', 'dependent' => true]);
		$model->hasMany('ExaminationItemResults', 	['className' => 'Examination.ExaminationItemResults', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true]);

		$model->belongsToMany('SecurityRoles', [
			'className' => 'Security.SecurityRoles',
			'foreignKey' => 'security_role_id',
			'targetForeignKey' => 'security_user_id',
			'through' => 'Security.SecurityGroupUsers',
			'dependent' => true
		]);

		$model->hasMany('ClassStudents', [
			'className' => 'Institution.InstitutionClassStudents',
			'foreignKey' => 'student_id'
		]);
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('username', ['visible' => false]);
		$this->ControllerAction->field('super_admin', ['visible' => false]);
		$this->ControllerAction->field('photo_name', ['visible' => false]);
		$this->ControllerAction->field('date_of_death', ['visible' => false]);
		$this->ControllerAction->field('status', ['options' => $this->getSelectOptions('general.active'), 'visible' => false]);
		$this->ControllerAction->field('photo_content', ['type' => 'image']);
		$this->ControllerAction->field('last_login', ['visible' => false]);
		$this->ControllerAction->field('address_area_id', ['type' => 'areapicker', 'source_model' => 'Area.AreaAdministratives']);
		$this->ControllerAction->field('birthplace_area_id', ['type' => 'areapicker', 'source_model' => 'Area.AreaAdministratives']);

		$this->ControllerAction->field('date_of_birth', [
				'date_options' => [
					'endDate' => date('d-m-Y', strtotime("-2 year"))
				],
				'default_date' => false,
			]
		);

		if ($this->action == 'add') {
			$this->ControllerAction->field('username', ['visible' => false]);
			$this->ControllerAction->field('password', ['visible' => false, 'type' => 'password']);
		}
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
		if ($this->alias() != 'Users') {
			return;
		}

		$plugin = $this->controller->plugin;
		$name = $this->controller->name;

		// $id = $this->ControllerAction->buttons['view']['url'][0];
		$action = $this->ControllerAction->url('view');
		$id = $action[0];

		if ($id=='view' || $id=='edit') {
			if (isset($this->ControllerAction->buttons['view']['url'][1])) {
				$id = $this->ControllerAction->buttons['view']['url'][1];
			}
		}

		$tabElements = [
			$this->alias => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'view', $this->paramsEncode(['id' => $id])],
				'text' => __('Details')
			],
			'Accounts' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Accounts', 'view', $this->paramsEncode(['id' => $id])],
				'text' => __('Account')
			]
		];

		if (!in_array($this->controller->name, ['Students', 'Staff', 'Guardians'])) {
			$tabElements[$this->alias] = [
				'url' => ['plugin' => Inflector::singularize($this->controller->name), 'controller' => $this->controller->name, 'action' => $this->alias(), 'view', $this->paramsEncode(['id' => $id])],
				'text' => __('Details')
			];
		}

		$this->controller->set('selectedAction', $this->alias);
        $this->controller->set('tabElements', $tabElements);
	}

	public function indexBeforeAction(Event $event, ArrayObject $settings) {
		$this->ControllerAction->field('first_name', ['visible' => false]);
		$this->ControllerAction->field('middle_name', ['visible' => false]);
		$this->ControllerAction->field('third_name', ['visible' => false]);
		$this->ControllerAction->field('last_name', ['visible' => false]);
		$this->ControllerAction->field('preferred_name', ['visible' => false]);
		$this->ControllerAction->field('address', ['visible' => false]);
		$this->ControllerAction->field('postal_code', ['visible' => false]);
		$this->ControllerAction->field('address_area_id', ['visible' => false]);
		$this->ControllerAction->field('gender_id', ['visible' => false]);
		$this->ControllerAction->field('date_of_birth', ['visible' => false]);
		$this->ControllerAction->field('username', ['visible' => false]);
		$this->ControllerAction->field('birthplace_area_id', ['visible' => false]);
		$this->ControllerAction->field('status', ['visible' => false]);
		$this->ControllerAction->field('photo_content', ['visible' => true]);

		$this->ControllerAction->field('address', ['visible' => false]);
		$this->ControllerAction->field('postal_code', ['visible' => false]);
		$this->ControllerAction->field('address_area_id', ['visible' => false]);
		$this->ControllerAction->field('birthplace_area_id', ['visible' => false]);

		$this->ControllerAction->field('staff_institution_name', ['visible' => false]);
		$this->ControllerAction->field('student_institution_name', ['visible' => false]);

		$this->fields['name']['sort'] = true;
		if($this->controller->name != 'Securities') {
			$this->fields['default_identity_type']['sort'] = true;
		}
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$queryParams = $request->query;

		if (array_key_exists('sort', $queryParams) && $queryParams['sort'] == 'name') {
			$query->find('withName', ['direction' => $queryParams['direction']]);
			$query->order([$this->aliasField('name') => $queryParams['direction']]);
		}

		if (array_key_exists('sort', $queryParams) && $queryParams['sort'] == 'default_identity_type') {
			$query->find('withDefaultIdentityType', ['direction' => $queryParams['direction']]);
			$query->order([$this->aliasField('default_identity_type') => $queryParams['direction']]);
			$request->query['sort'] = 'Users.default_identity_type';
		}
	}

	public function findWithName(Query $query, array $options) {
		$name = '';
        $separator = ", ";
        $keys = $this->getNameKeys();
        foreach($keys as $k=>$v){
            if(!is_null($this->aliasField($k))&&$v){
                if($k!='last_name'){
                    if($k=='preferred_name'){
                        $name .= $separator . '('. $this->aliasField($k) .')';
                    } else {
                        $name .= $this->aliasField($k) . $separator;
                    }
                } else {
                    $name .= $this->aliasField($k);
                }
            }
        }
        $name = trim(sprintf('%s', $name));
        $name = str_replace($this->alias,"inner_users",$name);

		return $query
			->join([
					'table' => 'security_users',
					'alias' => 'inner_users',
					'type'  => 'left',
					'select' => 'CONCAT('.$name.') AS inner_name',
					'conditions' => ['inner_users.id' => $this->aliasField('id')],
					'order' => ['inner_users.inner_name' => $options['direction']]
				])
			->order([$this->aliasField('first_name') => $options['direction']]);

		// return $query
		// 		->order([$this->aliasField('first_name') => $options['direction'],
		// 				$this->aliasField('middle_name') => $options['direction'],
		// 				$this->aliasField('third_name') => $options['direction'],
		// 				$this->aliasField('last_name') => $options['direction']
		// 			]);
	}

	public function findWithDefaultIdentityType(Query $query, array $options) {
		return $query
			->join([
				[
					'table' => 'user_identities',
					'alias' => 'Identities',
					'select' => ['*'],
					'type' => 'left',
					'group by' => ['Identities.number'],
					'conditions' => [
						'Identities.security_user_id' => $this->aliasField('id')
					]
				]
			])
			->contain([
					'Identities' => function ($q) {
					   return $q
							->select(['IdentityTypes.id'])
							->contain(['IdentityTypes'])
							->where(['IdentityTypes.default' => 1, 'Identities.identity_type_id' => 'IdentityTypes.id'])
							->order(['IdentityTypes.default DESC']);
					}
				])
			->group(['Identities.number'])
			->order(['Identities.number' => $options['direction']]);
	}

	public function viewBeforeAction(Event $event) {
		if ($this->alias() == 'Users') {
			// means that this originates from a controller
			$roleName = $this->controller->name;
			if (array_key_exists('pass', $this->request->params)) {
				$id = reset($this->request->params['pass']);
			}
		} else {
			// originates from a model
			$roleName = $this->controller->name.'.'.$this->alias();
			if (array_key_exists('pass', $this->request->params)) {
				$id = $this->request->params['pass'][1];
			}
		}

		if (isset($id)) {
			$this->Session->write($roleName.'.security_user_id', $id);
		} else {
			$id = $this->Session->read($roleName.'.security_user_id');
		}

		$fieldOrder = array_merge($this->fieldOrder1->getArrayCopy(), $this->fieldOrder2->getArrayCopy());
		$this->ControllerAction->setFieldOrder($fieldOrder);
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

		$prefix = TableRegistry::get('Configuration.ConfigItems')->value('openemis_id_prefix');
		$prefix = explode(",", $prefix);
		$prefix = ($prefix[1] > 0)? $prefix[0]: '';

		$latest = $this->find()
			->order($this->aliasField('id').' DESC')
			->first();

		if (is_array($latest)) {
			$latestOpenemisNo = $latest['SecurityUser']['openemis_no'];
		} else {
			$latestOpenemisNo = $latest->openemis_no;
		}
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
		$validator = parent::validationDefault($validator);

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
                'ruleMinLength' => [
                    'rule' => ['minLength', 6]
                ],
				'ruleUnique' => [
					'rule' => 'validateUnique',
					'provider' => 'table',
				],
				'ruleCheckUsername' => [
					'rule' => 'checkUsername',
					'provider' => 'table',
				]
			])
			->allowEmpty('username')
			->allowEmpty('password')
			// password validation now in behavior
			->allowEmpty('photo_content')
			;
		return $validator;
	}

	// this is the method to call for user validation - currently in use by Student Staff..
	public function setUserValidation(Validator $validator, $thisModel = null) {
		$validator
			->add('first_name', [
					'ruleCheckIfStringGotNoNumber' => [
						'rule' => 'checkIfStringGotNoNumber',
					],
					'ruleNotBlank' => [
						'rule' => 'notBlank',
					]
				])
			->add('last_name', [
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
                'ruleMinLength' => [
                    'rule' => ['minLength', 6]
                ],
				'ruleUnique' => [
					'rule' => 'validateUnique',
					'provider' => 'table',
				],
				'ruleCheckUsername' => [
					'rule' => 'checkUsername',
					'provider' => 'table',
				]
			])
			->allowEmpty('username')
			// password validation now in behavior
			->allowEmpty('password')
			->allowEmpty('photo_content')
			;

		$thisModel = ($thisModel == null)? $this: $thisModel;
		$thisModel->setValidationCode('first_name.ruleCheckIfStringGotNoNumber', 'User.Users');
		$thisModel->setValidationCode('first_name.ruleNotBlank', 'User.Users');
		$thisModel->setValidationCode('last_name.ruleCheckIfStringGotNoNumber', 'User.Users');
		$thisModel->setValidationCode('openemis_no.ruleUnique', 'User.Users');
        $thisModel->setValidationCode('username.ruleMinLength', 'User.Users');
		$thisModel->setValidationCode('username.ruleUnique', 'User.Users');
		$thisModel->setValidationCode('username.ruleAlphanumeric', 'User.Users');
		$thisModel->setValidationCode('username.ruleCheckUsername', 'User.Users');
		$thisModel->setValidationCode('password.ruleNoSpaces', 'User.Users');
		$thisModel->setValidationCode('password.ruleMinLength', 'User.Users');
		$thisModel->setValidationCode('date_of_birth.ruleValidDate', 'User.Users');
		return $validator;
	}

	public function onGetPhotoContent(Event $event, Entity $entity) {
		$fileContent = $entity->photo_content;
		$value = "";
		if(empty($fileContent) && is_null($fileContent)) {
			if(($this->hasBehavior('Student')) && ($this->action == "index")){
				$value = $this->defaultStudentProfileIndex;
			} else if(($this->hasBehavior('Staff')) && ($this->action == "index")){
				$value = $this->defaultStaffProfileIndex;
			} else if(($this->hasBehavior('Guardian')) && ($this->action == "index")){
				$value = $this->defaultGuardianProfileIndex;
			} else if(($this->hasBehavior('User')) && ($this->action == "index")){
				$value = $this->defaultUserProfileIndex;
			} else if(($this->hasBehavior('Student')) && ($this->action == "view")){
				$value = $this->defaultStudentProfileView;
			} else if(($this->hasBehavior('Staff')) && ($this->action == "view")){
				$value = $this->defaultStaffProfileView;
			} else if(($this->hasBehavior('Guardian')) && ($this->action == "view")){
				$value = $this->defaultGuardianProfileView;
			} else if(($this->hasBehavior('User')) && ($this->action == "view")){
				$value = $this->defaultUserProfileView;
			}
		} else {
			$value = base64_encode( stream_get_contents($fileContent) );//$fileContent;
		}

		return $value;
	}

	public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true) {
		if ($field == 'default_identity_type') {
			$IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
			$defaultIdentity = $IdentityType->getDefaultEntity();
			if ($defaultIdentity)
				$value = $defaultIdentity->name;

			return (!empty($value)) ? $value : parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
		} else if ($field == 'student_status' || $field == 'staff_status') {
			return 'Status';
		} else if ($field == 'programme_class') {
			return 'Programme<span class="divider"></span>Class';
		} else {
			return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
		}
	}

	public function getDefaultImgMsg() {
		return sprintf($this->defaultImgMsg, __($this->photoMessage), __($this->formatSupport));
	}

	public function getDefaultImgIndexClass() {
		return $this->defaultImgIndexClass;
	}

	public function getDefaultImgViewClass() {
		return $this->defaultImgViewClass;
	}

	public function getDefaultImgView() {
		$value = "";
		$controllerName = $this->controller->name;

		if($this->hasBehavior('Student')){
			$value = $this->defaultStudentProfileView;
		} else if($this->hasBehavior('Staff')){
			$value = $this->defaultStaffProfileView;
		} else if($this->hasBehavior('Guardian')){
			$value = $this->defaultGuardianProfileView;
		} else if($this->hasBehavior('User')){
			$value = $this->defaultUserProfileView;
		}
		return $value;
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

		if ($this->controller->name != 'Securities') {
			$actions = ['view', 'edit'];
			foreach ($actions as $action) {
				if (array_key_exists($action, $buttons)) {
					$buttons[$action]['url'][1] = $this->paramsEncode(['id' => $entity->security_user_id]);
				}
			}
			if (array_key_exists('remove', $buttons)) {
				$buttons['remove']['attr']['field-value'] = $entity->security_user_id;
			}
		}

		return $buttons;
	}

	public function autocomplete($search) {
		$search = sprintf('%s%%', $search);
		$list = $this
			->find()
			->select([
				$this->aliasField('openemis_no'),
				$this->aliasField('first_name'),
				$this->aliasField('middle_name'),
				$this->aliasField('third_name'),
				$this->aliasField('last_name'),
				$this->aliasField('preferred_name'),
				$this->aliasField('id')
			])
			->where([
				'OR' => [
					$this->aliasField('openemis_no') . ' LIKE' => $search,
					$this->aliasField('first_name') . ' LIKE' => $search,
					$this->aliasField('middle_name') . ' LIKE' => $search,
					$this->aliasField('third_name') . ' LIKE' => $search,
					$this->aliasField('last_name') . ' LIKE' => $search
				]
			])
			->order([$this->aliasField('first_name')])
			->limit(100)
			->all();

		$data = array();
		foreach($list as $obj) {
			$data[] = [
				'label' => sprintf('%s - %s', $obj->openemis_no, $obj->name),
				'value' => $obj->id
			];
		}
		return $data;
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options)
	{
		// This logic is meant for Import
		if ($entity->has('customColumns')) {
			foreach ($entity->customColumns as $column => $value) {
				switch ($column) {
					case 'Identity':
						$userIdentitiesTable = TableRegistry::get('User.Identities');

						$defaultValue = $userIdentitiesTable->IdentityTypes->getDefaultValue();

						if ($defaultValue) {
							$userIdentityData = $userIdentitiesTable->newEntity([
							    'identity_type_id' => $defaultValue,
							    'number' => $value,
							    'security_user_id' => $entity->id
							]);
							$userIdentitiesTable->save($userIdentityData);
						}
						break;
				}
			}
		}
	}

	public function userNationalitiesAfterSaveDelete(Event $event, Entity $entity)
    {
        //update nationality and identity info
        list($nationalityId, $identityTypeId, $identityNumber) = array_values($this->Identities->getLatestDefaultIdentityNo($entity->security_user_id));
        $this->updateAll(
            [
                'nationality_id' => $nationalityId,
                'identity_type_id' => $identityTypeId,
                'identity_number' => $identityNumber
            ], 
            ['id' => $entity->security_user_id]
        );
    }

	public function updateAllIdentityNumber($nationalityType)
	{
		$connection = ConnectionManager::get('default');
		$connection->execute(
			'UPDATE `security_users`
			INNER JOIN `nationalities` ON `nationalities`.`id` = `security_users`.`nationality_id`
			LEFT JOIN `user_identities` ON `user_identities`.`identity_type_id` = `nationalities`.`identity_type_id` AND `user_identities`.`security_user_id` = `security_users`.`id`
			SET `security_users`.`identity_type_id` = `user_identities`.`identity_type_id`, `security_users`.`identity_number` = `user_identities`.`number`
			WHERE `security_users`.`nationality_id` = ?',
			[$nationalityType],
    		['integer']
		);
	}

	public function updateIdentityNumber($userId)
	{
		$connection = ConnectionManager::get('default');
        $connection->execute(
            'UPDATE `security_users`
            INNER JOIN `nationalities` ON `nationalities`.`id` = `security_users`.`nationality_id`
            LEFT JOIN `user_identities` ON `user_identities`.`identity_type_id` = `nationalities`.`identity_type_id` AND `user_identities`.`security_user_id` = `security_users`.`id`
            SET `security_users`.`identity_type_id` = `user_identities`.`identity_type_id`, `security_users`.`identity_number` = `user_identities`.`number`
            WHERE `security_users`.`id` = ?',
            [$userId],
            ['integer']
        );
	}
}
