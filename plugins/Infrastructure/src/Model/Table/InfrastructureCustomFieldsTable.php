<?php
namespace Infrastructure\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InfrastructureCustomFieldsTable extends AppTable {
	public function initialize(array $config) {

		$this->belongsTo('InfrastructureTypes', ['className' => 'Infrastructure.InfrastructureTypes']);

		// $this->hasMany('AreaAdministratives', ['className' => 'Area.AreaAdministratives']);

	}

	public function validationDefault(Validator $validator) {
		
		return $validator;
	}

	public function beforeAction() {
		
	}

}
