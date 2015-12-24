<?php
namespace Security\Model\Table;

use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;

class SecurityGroupAreasTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Areas', ['className' => 'Area.Areas']);
		$this->belongsTo('SecurityGroups', ['className' => 'Security.SecurityGroups']);
	}

	public function getAreasByUser($userId) {
		$SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
		$groupIds = $SecurityGroupUsers
		->find('list', ['keyField' => 'id', 'valueField' => 'security_group_id'])
		->where([$SecurityGroupUsers->aliasField('security_user_id') => $userId])
		->toArray();

		$areas = $this
		->find('all')
		->distinct(['area_id'])
		->innerJoin(['AreaAll' => 'areas'], ['AreaAll.id = '.$this->aliasField('area_id')])
		->innerJoin(['Areas' => 'areas'], [
			'Areas.lft >= AreaAll.lft',
			'Areas.rght <= AreaAll.rght'
		])
		->select(['area_id', 'lft' => 'Areas.lft', 'rght'=>'Areas.rght'])
		->where([$this->aliasField('security_group_id') . ' IN ' => $groupIds])
		->toArray();

		return $areas;
	}
}
