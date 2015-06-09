<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StaffStatusesTable extends AppTable {
	public function initialize(array $config) {
        $this->addBehavior('ControllerAction.FieldOption');
        parent::initialize($config);
		
		$this->hasMany('Staff', ['className' => 'Institution.InstitutionSiteStaff']);
	}
}
