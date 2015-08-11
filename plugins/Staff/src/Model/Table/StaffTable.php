<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Security\Model\Table\SecurityUserTypesTable as UserTypes;

class StaffTable extends AppTable {
	// used for type = image
	private $defaultImgIndexClass = "profile-image-thumbnail";
	private $defaultImgViewClass= "profile-image";
	private $defaultImgMsg = "<p>* Advisable photo dimension 90 by 115px<br>* Format Supported: .jpg, .jpeg, .png, .gif </p>";

	public $InstitutionStaff;

	public function initialize(array $config) {
		$this->table('security_users');
		$this->entityClass('User.User');
		parent::initialize($config);

		$this->belongsTo('Genders', ['className' => 'User.Genders']);
		$this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
		$this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);

		$this->addBehavior('User.User');
		$this->addBehavior('User.AdvancedNameSearch');
		$this->addBehavior('User.Mandatory', ['userRole' => 'Staff', 'roleFields' =>['Identities', 'Nationalities', 'Contacts', 'SpecialNeeds']]);
		// $this->addBehavior('Institution.User', ['associatedModel' => $this->InstitutionSiteStudents]);
		$this->addBehavior('AdvanceSearch');

		$this->addBehavior('CustomField.Record', [
			'behavior' => 'Staff',
			'fieldKey' => 'staff_custom_field_id',
			'tableColumnKey' => 'staff_custom_table_column_id',
			'tableRowKey' => 'staff_custom_table_row_id',
			'formKey' => 'staff_custom_form_id',
			'filterKey' => 'staff_custom_filter_id',
			'formFieldClass' => ['className' => 'StaffCustomField.StaffCustomFormsFields'],
			'formFilterClass' => ['className' => 'StaffCustomField.StaffCustomFormsFilters'],
			'recordKey' => 'security_user_id',
			'fieldValueClass' => ['className' => 'StaffCustomField.StaffCustomFieldValues', 'foreignKey' => 'security_user_id', 'dependent' => true, 'cascadeCallbacks' => true],
			'tableCellClass' => ['className' => 'StaffCustomField.StaffCustomTableCells', 'foreignKey' => 'security_user_id', 'dependent' => true, 'cascadeCallbacks' => true]
		]);

		$this->addBehavior('HighChart', [
			'institution_staff_gender' => [
				'_function' => 'getNumberOfStaffByGender'
			]
		]);

		$this->InstitutionStaff = TableRegistry::get('Institution.Staff');
	}

	// public function beforeAction(Event $event) {

	// }

	public function viewAfterAction(Event $event, Entity $entity) {
		// to set the staff name in headers
		$this->request->session()->write('Staff.name', $entity->name);
	}

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$settings['model'] = 'Security.SecurityUserTypes';
		$this->fields = []; // unset all fields first

		$this->ControllerAction->field('institution', ['order' => 50]);
	}

	public function onGetInstitution(Event $event, Entity $entity) {
		$userId = $entity->security_user_id;
		$institutions = $this->InstitutionStaff->find('list', ['valueField' => 'Institutions.name'])
		->contain(['Institutions'])
		->select(['Institutions.name'])
		->where([$this->InstitutionStaff->aliasField('security_user_id') => $userId])
		->toArray();
		;

		$value = '';
		if (!empty($institutions)) {
			$value = implode(', ', $institutions);
		}
		return $value;
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$query->where(['SecurityUserTypes.user_type' => UserTypes::STAFF]);

		$search = $this->ControllerAction->getSearchKey();
		if (!empty($search)) {
			// function from AdvancedNameSearchBehavior
			$query = $this->addSearchConditions($query, ['searchTerm' => $search]);
		}
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		$UserTypes = TableRegistry::get('Security.SecurityUserTypes');

        if ($entity->isNew()) {
			$obj = $UserTypes->newEntity(['security_user_id' => $entity->id, 'user_type' => UserTypes::STAFF]);
			$UserTypes = $UserTypes->save($obj);
        }
	}
	
	// Logic for the mini dashboard
	public function afterAction(Event $event) {
		if ($this->action == 'index') {
			$userTypes = TableRegistry::get('Security.SecurityUserTypes');
			$institutionSiteArray = [];

			// Get total number of students
			$count = $userTypes->find()
				->distinct(['security_user_id'])
				->where([$userTypes->aliasField('user_type') => UserTypes::STAFF])
				->count(['security_user_id']);

			// Get the gender for all students
			$institutionSiteArray['Gender'] = $this->getDonutChart('institution_staff_gender', ['key' => 'Gender']);

			$indexDashboard = 'dashboard';
			$this->controller->viewVars['indexElements']['mini_dashboard'] = [
	            'name' => $indexDashboard,
	            'data' => [
	            	'model' => 'staff',
	            	'modelCount' => $count,
	            	'modelArray' => $institutionSiteArray,
	            ],
	            'options' => [],
	            'order' => 1
	        ];
	    }
	}

	public function addBeforeAction(Event $event) {
		$openemisNo = $this->getUniqueOpenemisId(['model' => 'Staff']);
		$this->ControllerAction->field('openemis_no', [ 
			'attr' => ['value' => $openemisNo],
			'value' => $openemisNo
		]);

		$this->ControllerAction->field('username', ['order' => 100]);
		$this->ControllerAction->field('password', ['order' => 101, 'visible' => true]);
	}

	public function afterDelete(Event $event, Entity $entity, ArrayObject $options) {
		$userTypes = TableRegistry::get('Security.SecurityUserTypes');
		$affectedRows = $userTypes->deleteAll([
			'security_user_id' => $entity->id,
			'user_type' => UserTypes::STAFF
		]);
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
				'ruleUnique' => [
					'rule' => 'validateUnique',
					'provider' => 'table',
				],
				'ruleAlphanumeric' => [
				    'rule' => 'alphanumeric',
				]
			])
			->allowEmpty('username')
			->allowEmpty('password')
			->allowEmpty('photo_content')
			;

		$this->setValidationCode('first_name.ruleCheckIfStringGotNoNumber', 'User.Users');
		$this->setValidationCode('first_name.ruleNotBlank', 'User.Users');
		$this->setValidationCode('last_name.ruleCheckIfStringGotNoNumber', 'User.Users');
		$this->setValidationCode('openemis_no.ruleUnique', 'User.Users');
		$this->setValidationCode('username.ruleUnique', 'User.Users');
		$this->setValidationCode('username.ruleAlphanumeric', 'User.Users');
		return $validator;
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
		foreach (['view', 'edit'] as $action) {
			if (array_key_exists($action, $buttons)) {
				$buttons[$action]['url'][1] = $entity->security_user_id;
			}
		}
		if (array_key_exists('remove', $buttons)) {
			$buttons['remove']['attr']['field-value'] = $entity->security_user_id;
		}
		return $buttons;
	}

	// Function use by the mini dashboard (For Staff.Staff)
	public function getNumberOfStaffByGender($params=[]) {

		$institutionSiteRecords = $this->find();
		$institutionSiteStaffCount = $institutionSiteRecords
			->select([
				'count' => $institutionSiteRecords->func()->count('DISTINCT '.$this->aliasField('id')),	
				'gender' => 'Genders.name'
			])
			->contain(['Genders'])
			->innerJoin(['UserTypes' => 'security_user_types'], [
				'UserTypes.security_user_id = '.$this->aliasField('id'),
				'UserTypes.user_type' => UserTypes::STAFF
			])
			->group('gender');

		// Creating the data set		
		$dataSet = [];
		foreach ($institutionSiteStaffCount->toArray() as $value) {
			//Compile the dataset
			$dataSet[] = [$value['gender'], $value['count']];
		}
		$params['dataSet'] = $dataSet;
		return $params;
	}
}
