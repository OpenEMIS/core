<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StudentCategoriesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		// jeff: is there a relationship to site class student?
		$this->hasMany('InstitutionSiteClassStudent', ['className' => 'Institution.InstitutionSiteClassStudent', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('InstitutionSiteSectionStudent', ['className' => 'Institution.InstitutionSiteSectionStudent', 'dependent' => true, 'cascadeCallbacks' => true]);
		
		// todo:mlee - put this when census student is created
		// $this->hasMany('CensusStudent', ['className' => 'User.CensusStudent']);
	}
}
