<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;
use Cake\Event\Event;

class UserActivitiesTable extends AppTable {
	public function initialize(array $config): void {
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
		switch ($this->controller->getName()) {
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

	public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'model') {
            return __('Model');
        } elseif ($field == 'field') {
            return __('Field');
        }elseif ($field == 'old_value') {
            return __('Old Value');
        }elseif ($field == 'new_value') {
            return __('New Value');
        }elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        }else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
