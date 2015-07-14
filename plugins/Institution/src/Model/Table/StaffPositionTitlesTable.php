<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StaffPositionTitlesTable extends AppTable {
	public function initialize(array $config) {
        $this->addBehavior('ControllerAction.FieldOption');
        parent::initialize($config);
	}
}
