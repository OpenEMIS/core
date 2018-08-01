<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class InstitutionStaffAttendancesTable extends ControllerActionTable {
	public function initialize(array $config) {
		$this->table('institution_staff_attendances');
		parent::initialize($config);
	}
}
