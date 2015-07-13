<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class ExtracurricularTypesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->hasMany('InstitutionSiteStudentAbsences', ['className' => 'Institution.InstitutionSiteStudentAbsences', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('InstitutionSiteStudentAbsences', ['className' => 'Institution.InstitutionSiteStudentAbsences', 'dependent' => true, 'cascadeCallbacks' => true]);
	}
}
