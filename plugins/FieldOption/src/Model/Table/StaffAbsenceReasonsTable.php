<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StaffAbsenceReasonsTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->hasMany('InstitutionSiteStaffAbsences', ['className' => 'InstitutionSite.InstitutionSiteStaffAbsences', 'dependent' => true, 'cascadeCallbacks' => true]);
	}
}
