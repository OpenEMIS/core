<?php
namespace User\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class AdvancedNationalIdSearchBehavior extends Behavior {
	// AdvancedNationalIdSearch Behavior
	public function addNationalIdSearchConditions(Query $query, $options = []) {
		if (array_key_exists('searchTerm', $options)) {
			$search = $options['searchTerm'];
		}

		$alias = $this->_table->alias();
		if (array_key_exists('alias', $options)) {
			$alias = $options['alias'];
		}

		if (!empty($search)) {
			$IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');
			$default_identity_type = $IdentityTypes->getDefaultValue();
			$searchString = '%' . $search . '%';
			if ($this->_table->table()=='institution_students' || $this->_table->table()=='institution_staff') {
				if (array_key_exists('student_id', $this->_table->fields)) {
					$securityUserFieldName = 'student_id';
				} else {
					$securityUserFieldName = 'staff_id';
				}
				$query
					->join([
						[
							'type' => 'INNER',
							'table' => 'security_users',
							'alias' => 'SecurityUsers',
							'conditions' => [
								'SecurityUsers.id = '. $securityUserFieldName
							]
						]
					])->join([
						[
							'type' => 'LEFT',
							'table' => 'user_identities',
							'alias' => 'Identities',
							'conditions' => [
								'Identities.security_user_id = SecurityUsers.id',
								'Identities.identity_type_id = '. $default_identity_type
							]
						]
					]);
			} else {
				$query->join([
						[
							'type' => 'LEFT',
							'table' => 'user_identities',
							'alias' => 'Identities',
							'conditions' => [
								'Identities.security_user_id = '. $alias . '.id',
								'Identities.identity_type_id = '. $default_identity_type
							]
						]
					]);
			}
			$query->orWhere(['Identities.number LIKE' =>  $searchString]);
		}
		return $query;
	}	

}
