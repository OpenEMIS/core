<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class ExtracurricularTypesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->hasMany('InstitutionSiteStudentAbsences', ['className' => 'Institution.InstitutionSiteStudentAbsences']);
		$this->hasMany('InstitutionSiteStudentAbsences', ['className' => 'Institution.InstitutionSiteStudentAbsences']);
	}
}
