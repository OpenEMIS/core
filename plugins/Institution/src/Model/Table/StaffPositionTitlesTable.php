<?php
namespace Institution\Model\Table;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;

use ArrayObject;

class StaffPositionTitlesTable extends AppTable {
	public function initialize(array $config) {
        $this->addBehavior('ControllerAction.FieldOption');
        $this->table('staff_position_titles');
        parent::initialize($config);
        $this->hasMany('Titles', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'staff_position_title_id']);
        $this->hasMany('TrainingCoursesTargetPopulations', ['className' => 'Training.TrainingCoursesTargetPopulations', 'foreignKey' => 'target_population_id']);
        $this->belongsTo('SecurityRoles', ['className' => 'Security.SecurityRoles']);

		$this->addBehavior('OpenEmis.OpenEmis');
		$this->addBehavior('ControllerAction.ControllerAction', [
			'actions' => ['remove' => 'transfer'],
			'fields' => ['excludes' => ['modified_user_id', 'created_user_id']]
		]);

		$this->systemRolesList = $this->SecurityRoles->getSystemRolesList();
	}

	public function validationDefault(Validator $validator) {
		$validator->notEmpty('security_role_id');
		return $validator;
	}

	public function beforeAction($event) {
		$this->field('type', [
			'visible' => true,
			'type' => 'select',
			'options' => $this->getSelectOptions('Staff.position_types'),
			'after' => 'name'
		]);
	}

	public function addEditBeforeAction(Event $event) {
		$systemRolesList = ['' => '--'.__('Select One').'--'] + $this->systemRolesList;
		$this->field('security_role_id', [
			'visible' => true,
			'type' => 'select',
			'options' => $systemRolesList,
			'after' => 'type'
		]);
	}

	public function editBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
		$titleId = $entity->id;
		// A
		// Get a list of staff / group user id that belongs to the current title
		$InstitutionStaffTable = TableRegistry::get('Institution.Staff');
		$query = $InstitutionStaffTable->find()
			->innerJoin(['Institutions' => 'institutions'], [
				'Institutions.id = '.$InstitutionStaffTable->aliasField('institution_id')
			])
			->innerJoin(['Positions' => 'institution_positions'], [
				'Positions.id = '.$InstitutionStaffTable->aliasField('institution_position_id')
			])
			->innerJoin(['StaffPositionTitles' => 'staff_position_titles'], [
				'StaffPositionTitles.id = Positions.staff_position_title_id',
				'StaffPositionTitles.id' => $titleId
			])
			->leftJoin(['SecurityGroupUsers' => 'security_group_users'], [
				'SecurityGroupUsers.security_user_id = '.$InstitutionStaffTable->aliasField('staff_id'),
				'SecurityGroupUsers.security_role_id = StaffPositionTitles.security_role_id',
				'SecurityGroupUsers.security_group_id = Institutions.security_group_id'
			]);

		$cloneQuery = clone ($query);
		$data['staffWithCurrentRole'] = $cloneQuery
			->find('list', [
				'keyField' => 'institution_staff_id',
				'valueField' => 'security_group_users_id'
			])
			->select(['institution_staff_id' => $InstitutionStaffTable->aliasField('id'), 'security_group_users_id' => 'SecurityGroupUsers.id'])
			->hydrate(false)
			->toArray();
		
		$data['staffWithCurrentRoleDetails'] = $query
			->find('list', [
				'groupField' => 'institution_staff_id',
				'keyField' => 'staff_id',
				'valueField' => 'security_group_id'
			])
			->select([
				'institution_staff_id' => $InstitutionStaffTable->aliasField('id'), 
				'staff_id' => $InstitutionStaffTable->aliasField('staff_id'),
				'security_group_id' => 'Institutions.security_group_id'], true)
			->hydrate(false)
			->toArray();
	}

	public function editAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$SecurityGroupUsersTable = TableRegistry::get('Security.SecurityGroupUsers');
		$titleId = $entity->id;
		$newSecurityRoleId = $entity->security_role_id;
		$originalSecurityRoleId = $entity->getOriginal('security_role_id');
		$staffInCurrentTitle = $data['staffWithCurrentRole'];
		$staffInCurrentTitleWithDetails = $data['staffWithCurrentRoleDetails'];

		$InstitutionStaffTable = TableRegistry::get('Institution.Staff');
		$query = $InstitutionStaffTable->find()
			->innerJoin(['Institutions' => 'institutions'], [
				'Institutions.id = '.$InstitutionStaffTable->aliasField('institution_id')
			])
			->innerJoin(['Positions' => 'institution_positions'], [
				'Positions.id = '.$InstitutionStaffTable->aliasField('institution_position_id')
			])
			->select(['institution_staff_id' => $InstitutionStaffTable->aliasField('id'), 'security_group_users_id' => 'SecurityGroupUsers.id']);

		$cloneQuery = clone $query;

		// B
		// Get a list of staff / group user id that belongs to other titles but havint the same role id as the current title
		$staffWithCurrentRole = $cloneQuery
			->find('list', [
				'keyField' => 'institution_staff_id',
				'valueField' => 'security_group_users_id'
			])
			->innerJoin(['StaffPositionTitles' => 'staff_position_titles'], [
				'StaffPositionTitles.id = Positions.staff_position_title_id',
				'StaffPositionTitles.id <> ' => $titleId
			])
			->innerJoin(['SecurityGroupUsers' => 'security_group_users'], [
				'SecurityGroupUsers.security_user_id = '.$InstitutionStaffTable->aliasField('staff_id'),
				'SecurityGroupUsers.security_role_id = StaffPositionTitles.security_role_id',
				'SecurityGroupUsers.security_group_id = Institutions.security_group_id',
				'SecurityGroupUsers.security_role_id' => $originalSecurityRoleId
			])
			->hydrate(false)
			->toArray();

		$cloneQuery = clone $query;

		// C
		// Get a list of staff / group user id that belongs to the new role id
		$staffBelongingToNewRoleId = $cloneQuery
			->find('list', [
				'keyField' => 'institution_staff_id',
				'valueField' => 'security_group_users_id'
			])
			->innerJoin(['StaffPositionTitles' => 'staff_position_titles'], [
				'StaffPositionTitles.id = Positions.staff_position_title_id'
			])
			->innerJoin(['SecurityGroupUsers' => 'security_group_users'], [
				'SecurityGroupUsers.security_user_id = '.$InstitutionStaffTable->aliasField('staff_id'),
				'SecurityGroupUsers.security_role_id = StaffPositionTitles.security_role_id',
				'SecurityGroupUsers.security_group_id = Institutions.security_group_id',
				'SecurityGroupUsers.security_role_id' => $newSecurityRoleId
			])
			->hydrate(false)
			->toArray();
		
		$allUpdateRecords = array_diff($staffInCurrentTitle, $staffWithCurrentRole);

		// Records to be inserted into the table
		$insertRecords = array_intersect($staffWithCurrentRole, $staffInCurrentTitle);
		$updateAndDeleteRecords = array_diff_key($allUpdateRecords, $staffBelongingToNewRoleId);

		// Records to be updated
		$updateRecords = array_intersect_key($updateAndDeleteRecords, $allUpdateRecords);

		// Records to be deleted
		$deleteRecords = array_diff_key($updateAndDeleteRecords, $updateRecords);

		// Logic to handle insertion of security group user records
		foreach ($insertRecords as $key => $value) {
			$staffId = key($staffInCurrentTitleWithDetails[$key]);
			$securityGroupId = $staffInCurrentTitleWithDetails[$key][$staffId];
			$obj = [
				'security_group_id' => $securityGroupId, 
				'security_role_id' => $newSecurityRoleId, 
				'security_user_id' => $staffId
			];
			$entity = $SecurityGroupUsersTable->newEntity($obj);
			$SecurityGroupUsersTable->save($entity);
		}

		// Remove empty records for security group user id
		$updateRecords = array_filter($updateRecords);

		// Logic to handle updating of security group user records
		foreach ($updateRecords as $key => $value) {
			$groupRecord = $SecurityGroupUsersTable->get($value);
			$groupRecord->security_role_id = $newSecurityRoleId;
			$SecurityGroupUsersTable->save($groupRecord);
		}

		// Remove empty records for security group user id
		$deleteRecords = array_filter($deleteRecords);

		// Logic to handle deleting of security group user records
		foreach ($deleteRecords as $key => $value) {
			$groupRecord = $SecurityGroupUsersTable->get($value);
			$SecurityGroupUsersTable->delete($groupRecord);
		}

		// Logic to handle initial assignment of role to title
		foreach ($staffInCurrentTitle as $key => $value) {
			if (empty($value)) {
				$institutionPositionId = $InstitutionStaffTable->get($key)->institution_position_id;
				$staffId = key($staffInCurrentTitleWithDetails[$key]);
				$securityGroupId = $staffInCurrentTitleWithDetails[$key][$staffId];
				$InstitutionStaffTable->addStaffRole($institutionPositionId, $staffId, $securityGroupId);
			}
		}
	}

	public function onGetType(Event $event, Entity $entity) {
		$types = $this->getSelectOptions('Staff.position_types');
		return array_key_exists($entity->type, $types) ? $types[$entity->type] : $entity->type;
	}

	public function onGetSecurityRoleId(Event $event, Entity $entity) {
		$systemRole = $this->systemRolesList;
		return array_key_exists($entity->security_role_id, $systemRole) ? $systemRole[$entity->security_role_id] : $entity->security_role_id;
	}

	public function indexBeforeAction(Event $event) {
		$this->field('type', ['after' => 'name', 'visible' => true]);
		$this->field('security_role_id', ['after' => 'type', 'visible' => true]);
	}
}
