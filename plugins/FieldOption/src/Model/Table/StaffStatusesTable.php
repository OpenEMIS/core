<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StaffStatusesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->hasMany('Staff', ['className' => 'Institution.Staff', 'dependent' => true, 'cascadeCallbacks' => true]);
	}
}
