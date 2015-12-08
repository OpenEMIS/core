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

		// $this->addBehavior('TrackActivity', ['target' => 'Student.StudentActivities', 'key' => 'security_user_id', 'session' => 'Users.id']);

		// $this->InstitutionStudent = TableRegistry::get('Institution.Students');
	}

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$this->fields = [];
		$this->ControllerAction->field('institution', ['order' => 50]);
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
