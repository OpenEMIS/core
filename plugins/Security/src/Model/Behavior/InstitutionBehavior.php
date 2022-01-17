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
		$apiSecuritiesScopes = TableRegistry::get('AcademicPeriod.ApiSecuritiesScopes');
        $apiSecurities = TableRegistry::get('AcademicPeriod.ApiSecurities');
        $apiSecuritiesData = $apiSecurities->find('all')
        ->select([
            'ApiSecurities.id','ApiSecurities.name','ApiSecurities.execute'
        ])
        ->where([
            'ApiSecurities.name' => 'Institutions',
            'ApiSecurities.model' => 'Institution.Institutions'
        ])
        ->first();
        $apiSecuritiesScopesData = $apiSecuritiesScopes->find('all')
        ->where([
            'ApiSecuritiesScopes.api_security_id' => $apiSecuritiesData->id
        ])
        ->first();

	$userId = (!empty($options['userId']))?$options['userId']:$options['user']['id'];
		
	if ((isset($options["super_admin"]) && $options["super_admin"] && $apiSecuritiesScopesData->view == 1 && $apiSecuritiesScopesData->index == 1)
        ||
       (isset($options['user']["super_admin"]) && $options['user']["super_admin"] && $apiSecuritiesScopesData->view == 1 && $apiSecuritiesScopesData->index == 1)
       ) {
       		$Types = TableRegistry::get('Institution.Types');
       		$Areas = TableRegistry::get('Area.Areas');
       		$Institutions = TableRegistry::get('Institution.Institutions');
       		if (isset($options['_controller']->request->query['_order'])) {
            	return $query;
       		}
       		else{
       			return $query
				->select([
					  'id' => $Institutions->aliasField('id'),$Institutions->aliasField('name'),$Institutions->aliasField('alternative_name'),$Institutions->aliasField('code'),$Institutions->aliasField('postal_code'), $Institutions->aliasField('contact_person'),$Institutions->aliasField('telephone'),$Institutions->aliasField('fax'),$Institutions->aliasField('email'),$Institutions->aliasField('website'),$Institutions->aliasField('date_opened'),$Institutions->aliasField('date_closed'),$Institutions->aliasField('year_closed'),$Institutions->aliasField('longitude'),$Institutions->aliasField('latitude'),
						$Institutions->aliasField('logo_name'),$Institutions->aliasField('logo_content'),$Institutions->aliasField('shift_type'),$Institutions->aliasField('classification'),
						'area_name' => $Areas->aliasField('name'),
						$Institutions->aliasField('area_id'),$Institutions->aliasField('area_administrative_id'),$Institutions->aliasField('institution_locality_id'),$Institutions->aliasField('institution_type_id'),
						'institution_type_name' => $Types->aliasField('name'),
						$Institutions->aliasField('institution_ownership_id'),$Institutions->aliasField('institution_status_id'),$Institutions->aliasField('institution_sector_id'),$Institutions->aliasField('institution_provider_id'),
						$Institutions->aliasField('institution_gender_id'),$Institutions->aliasField('security_group_id'),$Institutions->aliasField('modified_user_id'),$Institutions->aliasField('modified'),
						$Institutions->aliasField('created_user_id'),$Institutions->aliasField('created')
				])
				->innerJoin([$Areas->alias() => $Areas->table()], [
					$Institutions->aliasField('area_id = ') . $Areas->aliasField('id'),
				])
				->innerJoin([$Types->alias() => $Types->table()], [
					$Institutions->aliasField('institution_type_id = ') . $Types->aliasField('id'),
				]);
       		}                
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
