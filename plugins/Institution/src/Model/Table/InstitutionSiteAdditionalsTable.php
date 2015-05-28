<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionSiteAdditionalsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_custom_values');
		
		$this->hasMany('InstitutionSites', ['className' => 'Institution.InstitutionSites']);
	}

	public function validationDefault(Validator $validator) {
		
		return $validator;
	}

	public function beforeAction() {
		
	}

}
