<?php
namespace Report\Model\Behavior;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;

class InstitutionSecurityBehavior extends Behavior {
	public function implementedEvents()
	{
		$eventMap = parent::implementedEvents();
		$eventMap['Model.excel.onExcelBeforeQuery'] = ['callable' => 'onExcelBeforeQuery', 'priority' => 15];
		return $eventMap;
	}

	public function findByAccess(Query $query, array $options) {
		$userId = $options['user_id'];
		$institutionIdFieldAlias = $options['institution_field_alias'];

		// The cloning of the table registry object is just in case in the main model, the table registry object is
		// use on the same model which might cause the alias to be different
		$institutionTableClone1 = clone TableRegistry::get('Institution.Institutions');
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
		
		$institutionTableClone2 = clone TableRegistry::get('Institution.Institutions');
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
				['EXISTS ('.$institutionsSecurityArea->where([$institutionTableClone1->aliasField('id').'='.$institutionIdFieldAlias]).')'],
				['EXISTS ('.$institutionSecurity->where([$institutionTableClone2->aliasField('id').'='.$institutionIdFieldAlias]).')']
			]
		]);

		return $query;
	}

	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
	{
		$requestData = json_decode($settings['process']['params']);
		$superAdmin = $requestData->super_admin;
		$userId = $requestData->user_id;
		if (!$superAdmin) {
			$model = $this->_table;
			if (!is_null($model->association('Institutions'))) {
				$query->find('ByAccess', ['user_id' => $userId, 'institution_field_alias' => $model->aliasField($model->association('Institutions')->foreignKey())]);
			}
		}
	}
}