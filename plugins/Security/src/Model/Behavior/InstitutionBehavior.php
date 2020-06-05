<?php
namespace Security\Model\Behavior;

use ArrayObject;

use Cake\Core\Configure;
use Cake\ORM\Query;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;

class InstitutionBehavior extends Behavior
{
	public function implementedEvents()
	{
		$events = parent::implementedEvents();

		// priority has to be set at 100 so that Institutions->indexBeforePaginate will be triggered first
		$events['ControllerAction.Model.index.beforeQuery'] = 'indexBeforeQuery';
		return $events;
	}

	public function beforeFind(Event $event, Query $query, ArrayObject $options, $primary)
	{
		if (Configure::read('schoolMode')) {
			$query->limit(1);
		}
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
	{
		if ($this->_table->Auth->user('super_admin') != 1) { // if user is not super admin, the list will be filtered
			$userId = $this->_table->Auth->user('id');
			$query->find('byAccess', ['userId' => $userId]);
		}
	}

	public function findByAccess(Query $query, array $options)
	{

	$userId = (!empty($options['userId']))?$options['userId']:$options['user']['id'];
		
	if ((isset($options["super_admin"]) && $options["super_admin"])
		||
	   (isset($options['user']["super_admin"]) && $options['user']["super_admin"])
	   ) {
			return $query;
		}

		$institutionTableClone1 = clone $this->_table;
		$institutionTableClone1->alias('InstitutionSecurityArea');
		// find from security areas
		$institutionsSecurityArea = $institutionTableClone1->find()
			->innerJoin(['Areas' => 'areas'], [
				'Areas.id = '. $institutionTableClone1->aliasField('area_id')
			])
			->innerJoin(['AreaAll' => 'areas'], [
				'AreaAll.lft <= Areas.lft',
				'AreaAll.rght >= Areas.rght'
			])
			->innerJoin(['SecurityGroupArea' => 'security_group_areas'], [
				'SecurityGroupArea.area_id = AreaAll.id'
			])
			->innerJoin(['SecurityGroupUser' => 'security_group_users'], [
				'SecurityGroupUser.security_group_id = SecurityGroupArea.security_group_id',
				'SecurityGroupUser.security_user_id ='.$userId
			])
			->select(['id' => $institutionTableClone1->aliasField('id')]);

		$institutionTableClone2 = clone $this->_table;
		$institutionTableClone2->alias('InstitutionSecurity');

		// find from security group institutions
		$institutionSecurity = $institutionTableClone2->find()
			->select(['id' => $institutionTableClone2->aliasField('id')])
			->innerJoin(['SecurityGroupInstitution' => 'security_group_institutions'], [
				'SecurityGroupInstitution.institution_id = ' . $institutionTableClone2->aliasField('id')
			])
			->innerJoin(['SecurityGroupUser' => 'security_group_users'], [
				'SecurityGroupUser.security_group_id = SecurityGroupInstitution.security_group_id',
				'SecurityGroupUser.security_user_id ='.$userId
			]);

		$query->where([
			'OR' => [
				['EXISTS ('.$institutionsSecurityArea->where([$institutionTableClone1->aliasField('id').'='.$this->_table->aliasField('id')]).')'],
				['EXISTS ('.$institutionSecurity->where([$institutionTableClone2->aliasField('id').'='.$this->_table->aliasField('id')]).')']
			]
		]);

		return $query;
	}
}
