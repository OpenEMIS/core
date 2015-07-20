<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StaffStatusesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->hasMany('InstitutionSiteStaff', ['className' => 'InstitutionSite.InstitutionSiteStaff', 'dependent' => true, 'cascadeCallbacks' => true]);
	}
}
