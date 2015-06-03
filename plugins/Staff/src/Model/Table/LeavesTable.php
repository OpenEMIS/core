<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class LeavesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('staff_leaves');
		parent::initialize($config);
		
		// $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		// $this->belongsTo('SpecialNeedTypes', ['className' => 'User.SpecialNeedTypes']);
	}

	public function beforeAction($event) {
		// $this->fields['special_need_type_id']['type'] = 'select';
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.beforeAction'] = 'beforeAction';
		// $events['ControllerAction.afterAction'] = 'afterAction';
		// $events['ControllerAction.beforePaginate'] = 'beforePaginate';
		// $events['ControllerAction.beforeAdd'] = 'beforeAdd';
		// $events['ControllerAction.beforeView'] = 'beforeView';
		return $events;
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

}
