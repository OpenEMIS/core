<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class QualificationsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('staff_qualifications');
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('QualificationLevels', ['className' => 'FieldOption.QualificationLevels']);
		$this->belongsTo('QualificationInstitutions', ['className' => 'FieldOption.QualificationInstitutions']);
		$this->belongsTo('QualificationSpecialisation', ['className' => 'FieldOption.QualificationSpecialisation']);
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
