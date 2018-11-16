<?php
namespace App\Model\Table;
use App\Model\Table\ControllerActionTable;

class StudentStatusUpdatesTable extends ControllerActionTable {
	public function initialize(array $config) {
        $this->table('student_status_updates');
        parent::initialize($config);
	}
}
