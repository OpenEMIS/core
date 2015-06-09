<?php
namespace Institution\Model\Table;

// use App\Model\Table\AppTable;
// use Cake\Validation\Validator;

use Cake\Event\Event;
use App\Model\Table\AppTable;
// use Institution\Model\Table\Query;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;


class InstitutionSiteStaffTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('Users', 			['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('Institutions', 	['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
		$this->belongsTo('Positions', 		['className' => 'Institution.InstitutionSitePositions', 'foreignKey' => 'institution_site_position_id']);
		$this->belongsTo('Types', 			['className' => 'Institution.StaffTypes', 'foreignKey' => 'staff_type_id']);
		$this->belongsTo('Statuses', 		['className' => 'Institution.StaffStatuses', 'foreignKey' => 'staff_status_id']);

		// $this->hasMany('StudentFees', ['className' => 'Institution.StudentFees']);
	}

	public function findByPosition(Query $query, array $options) {
		if (array_key_exists('InstitutionSitePositions.id', $options)) {
			return $query->where([$this->aliasField('institution_site_position_id') => $options['InstitutionSitePositions.id']]);
		} else {
			return $query;
		}
	}

	public function findByInstitution(Query $query, array $options) {
		if (array_key_exists('Institutions.id', $options)) {
			return $query->where([$this->aliasField('institution_site_id') => $options['Institutions.id']]);
		} else {
			return $query;
		}
	}

	public function findWithBelongsTo(Query $query, array $options) {
		return $query
			->contain(['Users', 'Institutions', 'Positions', 'Types', 'Statuses']);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function beforeAction() {
		return true;
	}

	public function afterAction() {
		return true;
	}
}
