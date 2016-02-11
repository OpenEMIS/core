<?php
namespace Institution\Model\Table;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;

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
		$oldRoleId = $entity->getOriginal('security_role_id');
		$data['oldRoleId'] = intval($oldRoleId);
	}

	public function editAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$titleId = $entity->id;
		$newRole = $entity->security_role_id;
		$oldRoleId = $data['oldRoleId'];

		$SecurityGroupUsersTable = TableRegistry::get('Security.SecurityGroupUsers');

		$InstitutionStaffTable = TableRegistry::get('Institution.Staff');
		$subQuery = $InstitutionStaffTable->find()
			->select([
				'security_group_user_id' => 'SecurityGroupUsers.id'
			])
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
			->innerJoin(['SecurityGroupUsers' => 'security_group_users'], [
				'SecurityGroupUsers.security_user_id = '.$InstitutionStaffTable->aliasField('staff_id'),
				'SecurityGroupUsers.security_role_id' => $oldRoleId,
				'SecurityGroupUsers.security_group_id = Institutions.security_group_id'
			]);

		// Query to update security role id
		$securityGroupUserIdQuery = $this->query()
			->select(['security_group_user_id' => 'GroupUsers.security_group_user_id'])
			->from(['GroupUsers' => $subQuery]);

		$SecurityGroupUsersTable->updateAll(
			['security_role_id' => $newRole],
			['id IN ' => $securityGroupUserIdQuery]
		);

		// Query to delete duplicate records
		$duplicateRecordQuery = $SecurityGroupUsersTable->find()
			->select([
				'id' => $SecurityGroupUsersTable->aliasField('id'),
				'counter' => 1
			])
			->group([
				$SecurityGroupUsersTable->aliasField('security_user_id'),
				$SecurityGroupUsersTable->aliasField('security_group_id'),
				$SecurityGroupUsersTable->aliasField('security_role_id')
			])
			->having(['COUNT(counter) > 1'])
			;

		$deleteDuplicateQuery = $this->query()
			->select(['security_group_user_id' => 'GroupUsers.id'])
			->from(['GroupUsers' => $duplicateRecordQuery]);

		$SecurityGroupUsersTable->deleteAll(['id IN ' => $deleteDuplicateQuery]);

		$InstitutionStaffTable = TableRegistry::get('Institution.Staff');

		// Query to delete the wrong records
		$deleteWrongRecordsQuery = $InstitutionStaffTable->find()
			->innerJoin(['Institutions' => 'institutions'], [
				'Institutions.id = '.$InstitutionStaffTable->aliasField('institution_id')
			])
			->innerJoin(['Positions' => 'institution_positions'], [
				'Positions.id = '.$InstitutionStaffTable->aliasField('institution_position_id')
			])
			->innerJoin(['StaffPositionTitles' => 'staff_position_titles'], [
				'StaffPositionTitles.id = Positions.staff_position_title_id',
			])
			->innerJoin(['SecurityGroupUsers' => 'security_group_users'], [
				'SecurityGroupUsers.security_user_id = Staff.staff_id',
				'SecurityGroupUsers.security_role_id = StaffPositionTitles.security_role_id',
				'SecurityGroupUsers.security_group_id = Institutions.security_group_id'
			])
			->select([
				'security_group_user_id' => 'SecurityGroupUsers.id'
			]);

		$deleteQuery = $this->query()
			->select(['security_group_user_id' => 'GroupUsers.security_group_user_id'])
			->from(['GroupUsers' => $deleteWrongRecordsQuery]);
		$SecurityGroupUsersTable->deleteAll(['id NOT IN' => $deleteQuery]);

		// Query to insert missing security role records
		$insertMissingRecords = $InstitutionStaffTable->find()
			->innerJoin(['Institutions' => 'institutions'], [
				'Institutions.id = '.$InstitutionStaffTable->aliasField('institution_id')
			])
			->innerJoin(['Positions' => 'institution_positions'], [
				'Positions.id = '.$InstitutionStaffTable->aliasField('institution_position_id')
			])
			->innerJoin(['StaffPositionTitles' => 'staff_position_titles'], [
				'StaffPositionTitles.id = Positions.staff_position_title_id',
				'StaffPositionTitles.security_role_id <> ' => 0
			])
			->where([
				'NOT EXISTS('.
					$SecurityGroupUsersTable->find()
						->where([
							$SecurityGroupUsersTable->aliasField('security_user_id').' = '.$InstitutionStaffTable->aliasField('staff_id'),
							$SecurityGroupUsersTable->aliasField('security_group_id').' = Institutions.security_group_id',
							$SecurityGroupUsersTable->aliasField('security_role_id').' = StaffPositionTitles.security_role_id'
						])
				.')'
			])
			->select([
				'security_group_user_id' => 'uuid()',
				'security_user_id' => $InstitutionStaffTable->aliasField('staff_id'), 
				'security_role_id' => 'StaffPositionTitles.security_role_id',
				'security_group_id' => 'Institutions.security_group_id',
				'created_user_id' => intval($this->Auth->user()),
				'created' => $InstitutionStaffTable->find()->func()->now()
			]);

		$SecurityGroupUsersTable->query()
			->insert(['id', 'security_user_id', 'security_role_id', 'security_group_id', 'created_user_id', 'created'])
			->values($insertMissingRecords)
			->execute();

		
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
