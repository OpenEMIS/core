<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StudentCategoriesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->hasMany('InstitutionSiteClassStudent', ['className' => 'Institution.InstitutionSiteClassStudent']);
		$this->hasMany('InstitutionSiteSectionStudent', ['className' => 'Institution.InstitutionSiteSectionStudent']);
		
		// todo:mlee - put this when census student is created
		// $this->hasMany('CensusStudent', ['className' => 'User.CensusStudent']);
	}
}
