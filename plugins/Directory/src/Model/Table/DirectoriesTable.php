<?php
namespace Directory\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class DirectoriesTable extends AppTable {
	// public $InstitutionStudent;

	public function initialize(array $config) {
		$this->table('security_users');
		$this->entityClass('User.User');
		parent::initialize($config);

		$this->belongsTo('Genders', ['className' => 'User.Genders']);
		$this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
		$this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);

		$this->addBehavior('User.User');
		$this->addBehavior('User.AdvancedNameSearch');
		$this->addBehavior('User.Mandatory', ['userRole' => 'Student', 'roleFields' => ['Identities', 'Nationalities', 'Contacts', 'SpecialNeeds']]);
		$this->addBehavior('AdvanceSearch');

		// $this->addBehavior('Excel', [
		// 	'excludes' => ['photo_name', 'is_student', 'is_staff', 'is_guardian'],
		// 	'filename' => 'Students',
		// 	'pages' => ['view']
		// ]);

		// $this->addBehavior('TrackActivity', ['target' => 'Student.StudentActivities', 'key' => 'security_user_id', 'session' => 'Users.id']);®
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$search = $this->ControllerAction->getSearchKey();
		if (!empty($search)) {
			// function from AdvancedNameSearchBehavior
			$query = $this->addSearchConditions($query, ['searchTerm' => $search]);
		}

		// this part filters the list by institutions/areas granted to the group
		if (!$this->AccessControl->isAdmin()) { // if user is not super admin, the list will be filtered
			$institutionIds = $this->AccessControl->getInstitutionsByUser();
			$this->Session->write('AccessControl.Institutions.ids', $institutionIds);
			
			$InstitutionStudentTable = TableRegistry::get('Institution.Students');

			$institutionStudents = $InstitutionStudentTable->find()
				->where([
					$InstitutionStudentTable->aliasField('institution_id').' IN ('.$InstitutionIds.')',
					$InstitutionStudentTable->aliasField('student_id').' = '.$this->aliasField('id')
				]);

			$InstitutionStaffTable = TableRegistry::get('Institution.Staff');

			$institutionStaff = $InstitutionStaffTable->find()
				->where([
					$InstitutionStudentTable->aliasField('institution_site_id').' IN ('.$InstitutionIds.')',
					$InstitutionStudentTable->aliasField('security_user_id').' = '.$this->aliasField('id')
				]);

			$query->where([
					'OR' => [
						['EXISTS ('.$institutionStaff->sql().')'],
						['EXISTS ('.$institutionStudents->sql().')'],
					]
				])
				->group([$this->aliasField('id')]);
		}
	}

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$this->fields = [];
		$this->ControllerAction->field('institution', ['order' => 50]);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->Session->write('Directory.Directories.id', $entity->id);
		$this->Session->write('Directory.Directories.name', $entity->name);
		$isStudent = $entity->is_student;
		$isStaff = $entity->is_staff;
		$isGuardian = $entity->is_guardian;
		$isSet = false;

		if ($isStudent) {
			$this->Session->write('Directory.Directories.is_student', true);
			$isSet = true;
		}

		if ($isStaff) {
			$this->Session->write('Directory.Directories.is_staff', true);
			$isSet = true;
		}

		if ($isGuardian) {
			$this->Session->write('Directory.Directories.is_guardian', true);
			$isSet = true;
		}

		// To make sure the navigation component has already read the set value
		if ($isSet) {
			$reload = $this->Session->read('Directory.Directories.reload');
			if (!isset($reload)) {
				$urlParams = $this->ControllerAction->url('view');
				$event->stopPropagation();
				return $this->controller->redirect($urlParams);
			}
		}

		$this->setupTabElements($entity);
	}

	private function setupTabElements($entity) {
		$id = !is_null($this->request->query('id')) ? $this->request->query('id') : 0;

		$options = [
			// 'userRole' => 'Student',
			// 'action' => $this->action,
			// 'id' => $id,
			// 'userId' => $entity->id
		];

		$tabElements = $this->controller->getUserTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function onGetInstitution(Event $event, Entity $entity) {
		$userId = $entity->id;
		$isStudent = $entity->is_student;
		$isStaff = $entity->is_staff;
		$isGuardian = $entity->is_guardian;

		$studentInstitutions = [];
		if ($isStudent) {
			$InstitutionStudentTable = TableRegistry::get('Institution.Students');
			$studentInstitutions = $InstitutionStudentTable->find('list', [
					'keyField' => 'id',
					'valueField' => 'name'
				])
				->matching('StudentStatuses')
				->matching('Institutions')
				->where([
					$InstitutionStudentTable->aliasField('student_id') => $userId,
					'StudentStatuses.code' => 'CURRENT'
				])
				->distinct(['id'])
				->select(['id' => $InstitutionStudentTable->aliasField('institution_id'), 'name' => 'Institutions.name'])
				->toArray();
		}

		$staffInstitutions = [];
		if ($isStaff) {
			$InstitutionStaffTable = TableRegistry::get('Institution.Staff');
			$staffInstitutions = $InstitutionStaffTable->find('list', [
					'keyField' => 'id',
					'valueField' => 'name'
				])
				->matching('Institutions')
				->select(['Institutions.name'])
				->where([$InstitutionStaffTable->aliasField('security_user_id') => $userId])
				->andWhere([$InstitutionStaffTable->aliasField('end_date').' IS NULL'])
				->select(['id' => 'Institutions.id', 'name' => 'Institutions.name'])
				->toArray();
		}

		$combineArray = array_merge($studentInstitutions, $staffInstitutions);

		$value = implode('<BR>', $combineArray);

		return $value;
	}
}
