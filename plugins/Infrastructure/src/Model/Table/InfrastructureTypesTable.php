<?php
namespace Infrastructure\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InfrastructureTypesTable extends AppTable {
	public function initialize(array $config) {

		$this->belongsTo('InfrastructureLevels', ['className' => 'Infrastructure.InfrastructureLevels']);
		$this->hasMany('InfrastructureCustomFields', ['className' => 'Infrastructure.InfrastructureCustomFields']);
		// $this->hasMany('Institutions', ['className' => 'Institution.Institutions']);

	}

	public function validationDefault(Validator $validator) {
		
		return $validator;
	}

	public function beforeAction() {
		
	}

}
