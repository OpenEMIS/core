<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class LicenseTypesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->hasMany('Licenses', ['className' => 'Staff.Licenses', 'dependent' => true, 'cascadeCallbacks' => true]);
	}
}
