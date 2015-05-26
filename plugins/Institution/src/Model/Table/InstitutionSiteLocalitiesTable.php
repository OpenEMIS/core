<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionSiteLocalitiesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('field_option_values');
		
		$this->hasMany('InstitutionSites', ['className' => 'Institution.InstitutionSites']);
	}
}
