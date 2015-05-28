<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionSiteAssessmentResultsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('assessment_item_results');
		
		$this->hasMany('InstitutionSites', ['className' => 'Institution.InstitutionSites']);
	}
}
