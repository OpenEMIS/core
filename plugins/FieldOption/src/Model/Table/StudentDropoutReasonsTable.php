<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;

class StudentDropoutReasonsTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->hasMany('DropoutRequests', ['className' => 'Institution.DropoutRequests', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('DropoutApprovals', ['className' => 'Institution.DropoutApprovals', 'dependent' => true, 'cascadeCallbacks' => true]);
	}
}
