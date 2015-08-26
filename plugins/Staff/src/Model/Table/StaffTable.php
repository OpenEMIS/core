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

class StaffTable extends AppTable {
	public $InstitutionStaff;

	public function initialize(array $config) {
		$this->table('security_users');
		$this->entityClass('User.User');
		parent::initialize($config);

		$this->belongsTo('Genders', ['className' => 'User.Genders']);
		$this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
		$this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);

		$this->belongsToMany('Institutions', [
			'className' => 'Institution.Institutions',
			'joinTable' => 'institution_site_staff', // will need to change to institution_staff
			'foreignKey' => 'security_user_id', // will need to change to staff_id
			'targetForeignKey' => 'institution_site_id', // will need to change to institution_id
			'through' => 'Institution.Staff',
			'dependent' => true
		]);

		$this->addBehavior('User.User');
		$this->addBehavior('User.AdvancedNameSearch');
		$this->addBehavior('User.Mandatory', ['userRole' => 'Staff', 'roleFields' =>['Identities', 'Nationalities', 'Contacts', 'SpecialNeeds']]);
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

		$this->addBehavior('Excel', [
			'excludes' => ['photo_name', 'is_student', 'is_staff', 'is_guardian'],
			'filename' => 'Staff',
			'pages' => ['view']
		]);

		$this->addBehavior('HighChart', [
			'count_by_gender' => [
				'_function' => 'getNumberOfStaffByGender'
			]
		]);

		$this->InstitutionStaff = TableRegistry::get('Institution.Staff');
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

	public function viewAfterAction(Event $event, Entity $entity) {
		// to set the staff name in headers
		$this->Session->write('Staff.name', $entity->name);
		$this->setupTabElements(['id' => $entity->id]);
	}

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		// fields are set in UserBehavior
		$this->fields = []; // unset all fields first

		$this->ControllerAction->field('institution', ['order' => 50]);
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$query->where([$this->aliasField('is_staff') => 1]);

		$search = $this->ControllerAction->getSearchKey();
		if (!empty($search)) {
			// function from AdvancedNameSearchBehavior
			$query = $this->addSearchConditions($query, ['searchTerm' => $search]);
		}

		// this part filters the list by institutions/areas granted to the group
		if (!$this->AccessControl->isAdmin()) { // if user is not super admin, the list will be filtered
			$institutionIds = $this->AccessControl->getInstitutionsByUser();
			$query->innerJoin(
				['InstitutionStaff' => 'institution_site_staff'],
				[
					'InstitutionStaff.security_user_id = ' . $this->aliasField($this->primaryKey()),
					'InstitutionStaff.institution_site_id IN ' => $institutionIds
				]
			);
		}
	}

	public function onGetInstitution(Event $event, Entity $entity) {
		$userId = $entity->id;
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

	public function addBeforeAction(Event $event) {
		$openemisNo = $this->getUniqueOpenemisId(['model' => 'Staff']);
		$this->ControllerAction->field('openemis_no', [ 
			'attr' => ['value' => $openemisNo],
			'value' => $openemisNo
		]);

		$this->ControllerAction->field('username', ['order' => 100]);
		$this->ControllerAction->field('password', ['order' => 101, 'visible' => true]);
		$this->ControllerAction->field('is_staff', ['value' => 1]);
	}

	public function onBeforeDelete(Event $event, ArrayObject $options, $id) {
		$process = function($model, $id, $options) {
			$model->updateAll(['is_staff' => 0], [$model->primaryKey() => $id]);
			return true;
		};
		return $process;
	}

	// Logic for the mini dashboard
	public function afterAction(Event $event) {
		if ($this->action == 'index') {
			// Get total number of students
			$count = $this->find()->where([$this->aliasField('is_staff') => 1])->count();

			// Get the gender for all students
			$data = [];
			$data[__('Gender')] = $this->getDonutChart('count_by_gender', ['key' => __('Gender')]);

			$indexDashboard = 'dashboard';
			$this->controller->viewVars['indexElements']['mini_dashboard'] = [
	            'name' => $indexDashboard,
	            'data' => [
	            	'model' => 'staff',
	            	'modelCount' => $count,
	            	'modelArray' => $data,
	            ],
	            'options' => [],
	            'order' => 1
	        ];
	    }
	}
	
	private function setupTabElements() {
		$this->controller->set('selectedAction', $this->alias);
		$this->controller->set('tabElements', $this->controller->getUserTabElements($options));
	}

	// Function use by the mini dashboard (For Staff.Staff)
	public function getNumberOfStaffByGender($params=[]) {
		$query = $this->find();
		$query
		->select(['gender_id', 'count' => $query->func()->count($this->aliasField($this->primaryKey()))])
		->where([$this->aliasField('is_staff') => 1])
		->group('gender_id')
		;

		$genders = $this->Genders->getList()->toArray();

		$resultSet = $query->all();
		foreach ($resultSet as $entity) {
			$dataSet[] = [__($genders[$entity['gender_id']]), $entity['count']];
		}
		$params['dataSet'] = $dataSet;
		return $params;
	}
}
