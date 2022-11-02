<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;
use Cake\Event\Event;

class UserActivitiesTable extends AppTable {
	public function initialize(array $config) {
        parent::initialize($config);

		$this->belongsTo('Users', 		['className' => 'User.Users', 'foreignKey'=>'security_user_id']);
		$this->belongsTo('CreatedUser', ['className' => 'User.Users', 'foreignKey'=>'created_user_id']);
        $this->addBehavior('Activity');
    }

	private function setupTabElements() {
		$options = [
			'userRole' => '',
		];
		$tabElements = [];
		switch ($this->controller->name) {
			case 'Students':
				$options['userRole'] = 'Students';
				break;
			case 'Staff':
				$options['userRole'] = 'Staff';
				break;
			case 'Directories':
			case 'Profiles':
				break;
		}
		$tabElements = $this->controller->getUserTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', 'History');
	}

	public function indexAfterAction(Event $event, $data) {
		$this->setupTabElements();
	}
}
