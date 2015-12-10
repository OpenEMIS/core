<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;
use Cake\Event\Event;

class StaffActivitiesTable extends AppTable {
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

		switch ($this->controller->name) {
			case 'Students':
				$options['userRole'] = 'Students';
				break;
			case 'Staff':
				$options['userRole'] = 'Staff';
				break;
		}
		if ($this->controller->name == 'Directories') {
			$options['type'] = 'staff';
			$tabElements = $this->controller->getStaffGeneralTabElements($options);
		} else {
			$tabElements = $this->controller->getUserTabElements($options);		
		}
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', 'History');
	}

	public function indexAfterAction(Event $event, $data) {
		$this->setupTabElements();
	}
}
