<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;

class StudentTransferReasonsTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->hasMany('StudentDropouts', ['className' => 'Institution.StudentDropouts', 'dependent' => true, 'cascadeCallbacks' => true]);
	}
}
