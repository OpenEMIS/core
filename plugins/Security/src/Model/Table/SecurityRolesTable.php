<?php
namespace Security\Model\Table;

use Cake\ORM\Query;
use App\Model\Table\AppTable;


class SecurityRolesTable extends AppTable {
	public function initialize(array $config) {
		$this->belongsTo('SecurityGroups', ['className' => 'Security.SecurityGroups']);

		$this->belongsToMany('SecurityFunctions', [
			'className' => 'Security.SecurityFunctions',
			'through' => 'Security.SecurityRoleFunctions'
		]);
	}


	public function findByInstitution(Query $query, $options) {
		pr($options);

		$ids = [-1, 0];
		if (array_key_exists('id', $options)) {
			// need to get the security_group_id of the institution
			$Institution = TableRegistry::get('Institution.Institutions');
			$Institution			
				->where($Institution->aliasField($Institution->primaryKey()))
				;
		} 

		return $query->where([$this->aliasField('security_group_id').' IN' => $ids]);
		// return $query->where([$this->aliasField('super_admin') => 0]);
	}

}
