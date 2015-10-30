<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;

class StudentTransferReasonsTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		parent::initialize($config);
		$this->hasMany('TransferRequests', ['className' => 'Institution.TransferRequests', 'foreignKey' => 'student_transfer_reason_id']);
		$this->hasMany('TransferApprovals', ['className' => 'Institution.TransferApprovals', 'foreignKey' => 'student_transfer_reason_id']);
	}
}
