<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Session;
use ControllerAction\Model\Traits\UtilityTrait;
use App\Model\Table\ControllerActionTable;

class StaffPositionTitlesTable extends ControllerActionTable {
	use UtilityTrait;

	public function initialize(array $config) {
        $this->addBehavior('ControllerAction.FieldOption');
        $this->table('staff_position_titles');
        parent::initialize($config);
        $this->hasMany('Titles', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'staff_position_title_id']);
        $this->hasMany('TrainingCoursesTargetPopulations', ['className' => 'Training.TrainingCoursesTargetPopulations', 'foreignKey' => 'target_population_id']);
        $this->belongsTo('SecurityRoles', ['className' => 'Security.SecurityRoles']);

		$this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		$this->field('type', [
			'visible' => true,
			'options' => $this->getSelectOptions('Staff.position_types'),
			'after' => 'name'
		]);
		$systemRolesList = ['' => '-- '.__('Select Role').' --'] + $this->SecurityRoles->getSystemRolesList();
		$selected = '';
		$this->advancedSelectOptions($systemRolesList, $selected);
		$extra['roleList'] = $systemRolesList;
		$this->field('security_role_id', ['after' => 'type', 'options' => $extra['roleList']]);
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra) {
		$this->field('type', ['after' => 'name']);
		$this->field('security_role_id', ['after' => 'type']);
	}

	public function onGetType(Event $event, Entity $entity) {
		$types = $this->getSelectOptions('Staff.position_types');
		return array_key_exists($entity->type, $types) ? $types[$entity->type] : $entity->type;
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		if (!$entity->isNew() && $entity->dirty('security_role_id')) {
			$oldRoleId = $entity->getOriginal('security_role_id');
			$newRoleId = $entity->security_role_id;
			$titleId = $entity->id;

			$this->securityRolesUpdates($oldRoleId, $newRoleId, $titleId);
			$this->securityRolesDeletes();
			$this->securityRolesInserts();
		}
	}

	private function securityRolesUpdates($oldRoleId, $newRoleId, $titleId) {
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
			['security_role_id' => $newRoleId],
			['id IN ' => $securityGroupUserIdQuery]
		);
	}

	private function securityRolesDeletes() {
		$SecurityGroupUsersTable = TableRegistry::get('Security.SecurityGroupUsers');

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
	}

	private function securityRolesInserts() {
		$SecurityGroupUsersTable = TableRegistry::get('Security.SecurityGroupUsers');
		$InstitutionStaffTable = TableRegistry::get('Institution.Staff');
		$session = new Session();
		$userId = $session->read('Auth.User.id');

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
				'created_user_id' => intval($userId),
				'created' => $InstitutionStaffTable->find()->func()->now()
			]);

		$SecurityGroupUsersTable->query()
			->insert(['id', 'security_user_id', 'security_role_id', 'security_group_id', 'created_user_id', 'created'])
			->values($insertMissingRecords)
			->execute();
	}
}
