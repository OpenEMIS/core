<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class InstitutionSiteLocalitiesTable extends Table {
	public function initialize(array $config) {
		$this->table('field_option_values');
		
		$this->hasMany('InstitutionSites');
	}
}
