<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class UserNationalitiesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('Countries', ['className' => 'FieldOption.Countries']);
	}

	public function beforeAction($event) {
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
