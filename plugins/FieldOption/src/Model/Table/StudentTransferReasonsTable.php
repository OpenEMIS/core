<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;

class StudentTransferReasonsTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->hasMany('TransferRequests', ['className' => 'Institution.TransferRequests', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('TransferApprovals', ['className' => 'Institution.TransferApprovals', 'dependent' => true, 'cascadeCallbacks' => true]);
	}
}
