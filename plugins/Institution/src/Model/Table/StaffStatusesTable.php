<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;

class StaffStatusesTable extends ControllerActionTable {

	public function initialize(array $config) {
		$this->table('staff_statuses');
		parent::initialize($config);
	}
}
