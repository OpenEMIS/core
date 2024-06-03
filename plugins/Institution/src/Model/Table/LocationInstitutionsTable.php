<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class LocationInstitutionsTable extends ControllerActionTable {
	public function initialize(array $config): void
    {
        // $this->addBehavior('FieldOption.FieldOption');
		$this->setTable('institutions');
        parent::initialize($config);

		$this->hasMany('InstitutionShifts', ['className' => 'Institution.InstitutionShifts', 'dependent' => true, 'cascadeCallbacks' => true]);
	}
}
