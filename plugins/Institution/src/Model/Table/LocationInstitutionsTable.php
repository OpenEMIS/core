<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class LocationInstitutionsTable extends AppTable {
	public function initialize(array $config) {
        // $this->addBehavior('ControllerAction.FieldOption');
		$this->table('institutions');
        parent::initialize($config);
				
		$this->hasMany('InstitutionShifts', ['className' => 'Institution.InstitutionShifts', 'dependent' => true, 'cascadeCallbacks' => true]);
	}
}
