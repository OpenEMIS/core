<?php
namespace Guardian\Model\Table;

use Cake\Event\Event;
use App\Model\Table\AppTable;

class GuardianActivitiesTable extends AppTable {
	public function initialize(array $config) {
        parent::initialize($config);

		$this->belongsTo('Users', 		['className' => 'User.Users', 'foreignKey'=>'guardian_id']);
		$this->belongsTo('CreatedUser', ['className' => 'User.Users', 'foreignKey'=>'created_user_id']);
		
        $this->addBehavior('Activity');
    }

    private function setupTabElements() {
		$options = [
			'userRole' => '',
		];
		$controllerName = $this->controller->name;
		switch ($controllerName) {
			case 'Students':
				$options['userRole'] = 'Students';
				break;
			case 'Staff':
				$options['userRole'] = 'Staff';
				break;
		}
		if ($controllerName == 'Directories') {
			$options['type'] = 'guardian';
			$tabElements = $this->controller->getGuardianGeneralTabElements($options);
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
