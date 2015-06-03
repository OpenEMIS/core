<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class MembershipsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('staff_memberships');
		parent::initialize($config);
		
		// $this->belongsTo('InstitutionSites', ['className' => 'Institution.InstitutionSites']);
		// $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function beforeAction() {
		
	}
}
