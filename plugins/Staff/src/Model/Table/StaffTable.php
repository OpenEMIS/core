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

		// Associations
		$Users = TableRegistry::get('User.Users');
		$Users::handleAssociations($this);
		self::handleAssociations($this);

		// Behaviors
		$this->addBehavior('User.User');
		$this->addBehavior('User.AdvancedNameSearch');
		$this->addBehavior('User.Mandatory', ['userRole' => 'Staff', 'roleFields' =>['Identities', 'Nationalities', 'Contacts', 'SpecialNeeds']]);
		$this->addBehavior('AdvanceSearch');

		$this->addBehavior('CustomField.Record', [
			'model' => 'Staff.Staff',
			'behavior' => 'Staff',
			'fieldKey' => 'staff_custom_field_id',
			'tableColumnKey' => 'staff_custom_table_column_id',
			'tableRowKey' => 'staff_custom_table_row_id',
			'fieldClass' => ['className' => 'StaffCustomField.StaffCustomFields'],
			'formKey' => 'staff_custom_form_id',
			'filterKey' => 'staff_custom_filter_id',
			'formFieldClass' => ['className' => 'StaffCustomField.StaffCustomFormsFields'],
			'formFilterClass' => ['className' => 'StaffCustomField.StaffCustomFormsFilters'],
			'recordKey' => 'staff_id',
			'fieldValueClass' => ['className' => 'StaffCustomField.StaffCustomFieldValues', 'foreignKey' => 'staff_id', 'dependent' => true, 'cascadeCallbacks' => true],
			'tableCellClass' => ['className' => 'StaffCustomField.StaffCustomTableCells', 'foreignKey' => 'staff_id', 'dependent' => true, 'cascadeCallbacks' => true]
		]);

		$this->addBehavior('Excel', [
			'excludes' => ['photo_name', 'is_student', 'is_staff', 'is_guardian', 'super_admin', 'date_of_death' ],
			'filename' => 'Staff',
			'pages' => ['view']
		]);

		$this->addBehavior('HighChart', [
			'count_by_gender' => [
				'_function' => 'getNumberOfStaffByGender'
			]
		]);
        $this->addBehavior('Import.ImportLink');

        $this->addBehavior('TrackActivity', ['target' => 'User.UserActivities', 'key' => 'security_user_id', 'session' => 'Staff.Staff.id']);

		$this->InstitutionStaff = TableRegistry::get('Institution.Staff');
	}

	public static function handleAssociations($model) {
		$model->belongsToMany('Institutions', [
			'className' => 'Institution.Institutions',
			'joinTable' => 'institution_staff', // will need to change to institution_staff
			'foreignKey' => 'staff_id', // will need to change to staff_id
			'targetForeignKey' => 'institution_id', // will need to change to institution_id
			'through' => 'Institution.Staff',
			'dependent' => true
		]);

		// class should never cascade delete
		$model->hasMany('InstitutionClasses', 		['className' => 'Institution.InstitutionClasses', 'foreignKey' => 'staff_id']);

		$model->belongsToMany('Subjects', [
			'className' => 'Institution.InstitutionSubject',
			'joinTable' => 'institution_subject_staff',
			'foreignKey' => 'staff_id',
			'targetForeignKey' => 'institution_subject_id',
			'through' => 'Institution.InstitutionSubjectStaff',
			'dependent' => true
		]);

		$model->hasMany('StaffActivities', 			['className' => 'Staff.StaffActivities', 'foreignKey' => 'staff_id', 'dependent' => true]);
		$model->hasMany('InstitutionRubrics', 		['className' => 'Institution.InstitutionRubrics', 'foreignKey' => 'staff_id', 'dependent' => true]);
	}


	public function validationDefault(Validator $validator) {
		$BaseUsers = TableRegistry::get('User.Users');
		return $BaseUsers->setUserValidation($validator, $this);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->Session->write('Staff.Staff.name', $entity->name);
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
			$this->Session->write('AccessControl.Institutions.ids', $institutionIds);
			$this->joinInstitutionStaffs($institutionIds, $query);
			$query->group([$this->aliasField('id')]);

			// $query->innerJoin(
			// 	['InstitutionStaff' => 'institution_staff'],
			// 	[
			// 		'InstitutionStaff.staff_id = ' . $this->aliasField($this->primaryKey()),
			// 		'InstitutionStaff.institution_id IN ' => $institutionIds
			// 	]
			// )
			// ->group([$this->aliasField('id')]);
		}
	}

	private function joinInstitutionStaffs(array $institutionIds, Query $query) {
		$query->innerJoin(
			['InstitutionStaff' => 'institution_site_staff'],
			[
				'InstitutionStaff.security_user_id = ' . $this->aliasField($this->primaryKey()),
				'InstitutionStaff.institution_site_id IN ' => $institutionIds
			]
		);
	}

	public function onGetInstitution(Event $event, Entity $entity) {
		$userId = $entity->id;
		$institutions = $this->InstitutionStaff->find('list', ['valueField' => 'Institutions.name'])
		->contain(['Institutions'])
		->select(['Institutions.name'])
		->where([$this->InstitutionStaff->aliasField('staff_id') => $userId])
		->andWhere([$this->InstitutionStaff->aliasField('end_date').' IS NULL'])
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
		$this->ControllerAction->field('password', ['order' => 101]);
		$this->ControllerAction->field('is_staff', ['value' => 1]);
	}

	public function addAfterAction(Event $event) { 
		// need to find out order values because recordbehavior changes it
		$allOrderValues = [];
		foreach ($this->fields as $key => $value) {
			$allOrderValues[] = (array_key_exists('order', $value) && !empty($value['order']))? $value['order']: 0;
		}
		$highestOrder = max($allOrderValues);

		// username and password is always last... 
		$this->ControllerAction->field('username', ['order' => ++$highestOrder, 'visible' => true]);
		$this->ControllerAction->field('password', ['order' => ++$highestOrder, 'visible' => true, 'type' => 'password', 'attr' => ['value' => '', 'autocomplete' => 'off']]);
	}

	public function onBeforeDelete(Event $event, ArrayObject $options, $id) {
		$process = function($model, $id, $options) {
			// classes are not to be deleted (cascade delete is not set and need to change id)
			$InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
			$InstitutionClasses->updateAll(
					['staff_id' => 0],
					['staff_id' => $id]
				);

			$userQuery = $model->find()->where([$this->aliasField('id') => $id])->first();

			if (!empty($userQuery)) {
				if ($userQuery->is_student || $userQuery->is_guardian) {
					$model->updateAll(['is_staff' => 0], [$model->primaryKey() => $id]);
				} else {
					$model->delete($userQuery);
				}
			}

			return true;
		};
		return $process;
	}

	// Logic for the mini dashboard
	public function afterAction(Event $event) {
		if ($this->action == 'index') {

			$searchConditions = $this->getSearchConditions($this, $this->request->data['Search']['searchField']);
			$searchConditions['OR'] = array_merge($searchConditions['OR'], $this->advanceNameSearch($this, $this->request->data['Search']['searchField']));
			// Get total number of students
			$count = $this->find()
				->where([$this->aliasField('is_staff') => 1])
				->where($searchConditions);
			if (!$this->AccessControl->isAdmin()) {
				$institutionIds = $this->Session->read('AccessControl.Institutions.ids');
				$this->joinInstitutionStaffs($institutionIds, $count);
				$count->group([$this->aliasField('id')]);
			}
			$this->advancedSearchQuery($this->request, $count);

			// Get the gender for all students
			$data = [];
			$data[__('Gender')] = $this->getDonutChart('count_by_gender', ['searchConditions' => $searchConditions, 'key' => __('Gender')]);

			$indexDashboard = 'dashboard';
			$this->controller->viewVars['indexElements']['mini_dashboard'] = [
	            'name' => $indexDashboard,
	            'data' => [
	            	'model' => 'staff',
	            	'modelCount' => $count->count(),
	            	'modelArray' => $data,
	            ],
	            'options' => [],
	            'order' => 1
	        ];
	    }
	}
	
	private function setupTabElements($options) {
		$this->controller->set('selectedAction', $this->alias);
		$this->controller->set('tabElements', $this->controller->getUserTabElements($options));
	}

	// Function use by the mini dashboard (For Staff.Staff)
	public function getNumberOfStaffByGender($params=[]) {
		$searchConditions = isset($params['searchConditions']) ? $params['searchConditions'] : [];
		$query = $this->find();
		$query
			->select(['gender_id', 'count' => $query->func()->count('DISTINCT '.$this->aliasField($this->primaryKey()))])
			->where([$this->aliasField('is_staff') => 1])
			->where($searchConditions)
			->group('gender_id')
			;
		if (!$this->AccessControl->isAdmin()) {
			$institutionIds = $this->Session->read('AccessControl.Institutions.ids');
			$this->joinInstitutionStaffs($institutionIds, $query);
		}
		$this->advancedSearchQuery($this->request, $query);

		$genders = $this->Genders->getList()->toArray();

		$resultSet = $query->all();
		$dataSet = [];
		foreach ($resultSet as $entity) {
			$dataSet[] = [__($genders[$entity['gender_id']]), $entity['count']];
		}
		$params['dataSet'] = $dataSet;
		return $params;
	}

	public function getCareerTabElements($options = []) {
		$tabElements = [];
		$studentUrl = ['plugin' => 'Staff', 'controller' => 'Staff'];
		$studentTabElements = [
			'Employments' => ['text' => __('Employments')],
			'Positions' => ['text' => __('Positions')],
			'Classes' => ['text' => __('Classes')],
			'Subjects' => ['text' => __('Subjects')],
			'Absences' => ['text' => __('Absences')],
			'Leaves' => ['text' => __('Leaves')],
			'Behaviours' => ['text' => __('Behaviours')],
			'Awards' => ['text' => __('Awards')],
		];

		$tabElements = array_merge($tabElements, $studentTabElements);

		foreach ($studentTabElements as $key => $tab) {
			$tabElements[$key]['url'] = array_merge($studentUrl, ['action' => $key, 'index']);
		}
		return $tabElements;
	}
}
