<?php
namespace Security\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\Event\Event;

class UserBehavior extends Behavior {
	public function initialize(array $config) {
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'ControllerAction.Model.index.beforeAction' => 'indexBeforeAction',
			'ControllerAction.Model.index.beforePaginate' => 'indexBeforePaginate',
		];
		$events = array_merge($events,$newEvent);
		return $events;
	}

	public function indexBeforeAction(Event $event) {
		$this->_table->fields['photo_content']['visible'] = false;
		$this->_table->fields['first_name']['visible'] = false;
		$this->_table->fields['middle_name']['visible'] = false;
		$this->_table->fields['third_name']['visible'] = false;
		$this->_table->fields['preferred_name']['visible'] = false;
		$this->_table->fields['last_name']['visible'] = false;
		$this->_table->fields['gender_id']['visible'] = false;
		$this->_table->fields['date_of_birth']['visible'] = false;

		$this->_table->fields['username']['visible'] = true;

		$this->_table->ControllerAction->field('name', []);

		$this->_table->ControllerAction->setFieldOrder(['photo_content', 'openemis_no', 
			'username', 'name', 'last_login', 'status']);
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$search = $this->_table->ControllerAction->getSearchKey();
		$searchParams = explode(' ', $search);
		foreach ($searchParams as $key => $value) {
			if (empty($searchParams[$key])) {
				unset($searchParams[$key]);
			}
		}

		if (!empty($search)) {
			$firstFlag = true;
			foreach ($searchParams as $key => $value) {
				$searchString = '%' . $value . '%';
				if ($firstFlag) {
					$query->where(['Users.openemis_no LIKE' => $searchString]);
				} else {
					$query->orWhere(['Users.openemis_no LIKE' => $searchString]);
				}
				$firstFlag = false;
				$query->orWhere(['Users.first_name LIKE' => $searchString]);
				$query->orWhere(['Users.middle_name LIKE' => $searchString]);
				$query->orWhere(['Users.third_name LIKE' => $searchString]);
				$query->orWhere(['Users.last_name LIKE' => $searchString]);
			}
		}
	}


}