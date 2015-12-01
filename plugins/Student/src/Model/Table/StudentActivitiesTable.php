<?php
namespace Student\Model\Table;

use App\Model\Table\AppTable;
use Cake\Event\Event;

class StudentActivitiesTable extends AppTable {
	public function initialize(array $config) {
        parent::initialize($config);

		$this->belongsTo('Users', 		['className' => 'User.Users', 'foreignKey'=>'security_user_id']);
		$this->belongsTo('CreatedUser', ['className' => 'User.Users', 'foreignKey'=>'created_user_id']);

        $this->addBehavior('Activity');
    }

    private function setupTabElements() {
		$tabElements = $this->controller->getUserTabElements();
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', 'History');
	}

	public function indexAfterAction(Event $event, $data) {
		if ($this->controller->name == 'Students') {
			$this->setupTabElements();
		}
	}
}
