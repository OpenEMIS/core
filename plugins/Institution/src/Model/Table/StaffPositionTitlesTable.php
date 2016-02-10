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
			->select([
				'institution_staff_id' => $InstitutionStaffTable->aliasField('id'),
				'security_group_users_id' => 'SecurityGroupUsers.id',
				'staff_id' => $InstitutionStaffTable->aliasField('staff_id'),
				'security_group_id' => 'Institutions.security_group_id',
				'institution_position_id' => $InstitutionStaffTable->aliasField('institution_position_id')
			])
			->hydrate(false)
			->toArray();
	}

	public function editAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$SecurityGroupUsersTable = TableRegistry::get('Security.SecurityGroupUsers');
		$titleId = $entity->id;
		$newSecurityRoleId = $entity->security_role_id;
		$originalSecurityRoleId = $entity->getOriginal('security_role_id');
		$staffInCurrentTitle = $data['staffWithCurrentRole'];

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
		$staffBelongingToNewRole = $cloneQuery
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
		
		// staff in current title security group user id (A)
		$staffInCurrentTitleSGUId = $this->array_column($staffInCurrentTitle, 'security_group_users_id');
		// staff in existing role but does not belong to the same title's security group user id (B)
		$staffWithCurrentRoleSGUId = $this->array_column($staffWithCurrentRole, 'security_group_users_id');
		// staff in new roles's security group user id (C)
		$staffBelongingToNewRoleIdSGUId = $this->array_column($staffBelongingToNewRole, 'security_group_users_id');

		// Records that may be either updated or deleted (D)
		$recordsToBeProcessed = array_diff($staffInCurrentTitleSGUId, $staffWithCurrentRoleSGUId);
		$recordsToBeProcessed = array_intersect_key($staffInCurrentTitle, $recordsToBeProcessed);

		// Records that may be inserted (E)
		$recordsToBeInserted = array_intersect($staffInCurrentTitleSGUId, $staffWithCurrentRoleSGUId);

		// Records that has been check against existing new role id and are ready to be inserted (F)
		$recordsToBeInserted = array_diff($recordsToBeInserted, $staffBelongingToNewRoleIdSGUId);
		$recordsToBeInserted = array_intersect_key($staffInCurrentTitle, $recordsToBeInserted);

		// Logic to handle insertion of security group user records
		foreach ($recordsToBeInserted as $value) {
			$staffId = $value['staff_id'];
			$securityGroupId = $value['security_group_id'];
			$obj = [
				'security_group_id' => $securityGroupId, 
				'security_role_id' => $newSecurityRoleId, 
				'security_user_id' => $staffId
			];
			$entity = $SecurityGroupUsersTable->newEntity($obj);
			$SecurityGroupUsersTable->save($entity);
		}

		// staff with new role's institution staff id
		$staffBelongingToNewRoleISId = $this->array_column($staffBelongingToNewRole, 'institution_staff_id');

		// staff records to be processed's institution staff id
		$recordsToBeProcessedISId = $this->array_column($recordsToBeProcessed, 'institution_staff_id');

		// Records that has been check against existing new role id and are ready to be updated (G)
		$recordsToBeUpdated = array_diff($recordsToBeProcessedISId, $staffBelongingToNewRoleISId);

		// Records remaining in the records to be processed will be send for deletion (H)
		$recordsToBeDeleted = array_diff($recordsToBeProcessedISId, $recordsToBeUpdated);

		// Logic to handle update of security group user records
		$recordsToBePatched = array_intersect_key($recordsToBeProcessed, $recordsToBeUpdated);
		$recordsToBeUpdated = $this->array_column($recordsToBePatched, 'security_group_users_id');
		$recordsToBeUpdated = array_filter($recordsToBeUpdated);
		foreach ($recordsToBeUpdated as $value) {
			$groupRecord = $SecurityGroupUsersTable->get($value);
			$groupRecord->security_role_id = $newSecurityRoleId;
			$SecurityGroupUsersTable->save($groupRecord);
		}

		// Logic to handle unassigned security role position
		$recordToInsert = array_diff_key($recordsToBePatched, $recordsToBeUpdated);
		foreach ($recordToInsert as $value) {
			$staffId = $value['staff_id'];
			$securityGroupId = $value['security_group_id'];
			$obj = [
				'security_group_id' => $securityGroupId, 
				'security_role_id' => $newSecurityRoleId, 
				'security_user_id' => $staffId
			];
			$entity = $SecurityGroupUsersTable->newEntity($obj);
			$SecurityGroupUsersTable->save($entity);
		}

		// Logic to handle delete of security group user records
		$recordsToBeDeleted = array_intersect_key($recordsToBeProcessed, $recordsToBeDeleted);
		$recordsToBeDeleted = $this->array_column($recordsToBeDeleted, 'security_group_users_id');
		$recordsToBeDeleted = array_filter($recordsToBeDeleted);
		foreach ($recordsToBeDeleted as $value) {
			$groupRecord = $SecurityGroupUsersTable->get($value);
			$SecurityGroupUsersTable->delete($groupRecord);
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
