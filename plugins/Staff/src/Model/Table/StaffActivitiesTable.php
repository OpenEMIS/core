<?php
namespace Staff\Model\Table;

use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StaffActivitiesTable extends AppTable {
	public function initialize(array $config) {
        parent::initialize($config);

		$this->belongsTo('Users', 		['className' => 'User.Users', 'foreignKey'=>'security_user_id']);
		$this->belongsTo('CreatedUser', ['className' => 'User.Users', 'foreignKey'=>'created_user_id']);
    }

	public function beforeAction(Event $event) {
		$this->fields['operation']['visible'] = false;
		$this->fields['model_reference']['visible'] = false;
		$this->fields['created_user_id']['visible'] = true;
		$this->fields['created']['visible'] = true; 
	}
}
