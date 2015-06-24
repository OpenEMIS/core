<?php 
namespace Staff\Model\Behavior;

use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\Event\Event;

class StaffBehavior extends Behavior {
	public function initialize(array $config) {
	}

	public function beforeFind(Event $event, Query $query, $options) {
		$query
			->join([
				'table' => 'institution_site_staff',
				'alias' => 'InstitionSiteStaff',
				'type' => 'INNER',
				'conditions' => 'Users.id = InstitionSiteStaff.security_user_id',
			])
			->group('Users.id');
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'ControllerAction.Model.beforeAction' => 'beforeAction',
			'ControllerAction.Model.index.beforeAction' => 'indexBeforeAction'
		];
		$events = array_merge($events,$newEvent);
		return $events;
	}

	public function beforeAction(Event $event) {
		$this->_table->ControllerAction->field('photo_content', [
			'type' => 'element',
			'element' => 'Staff.Staff/picture'
		]);
		$this->_table->fields['super_admin']['visible'] = false;
		$this->_table->fields['status']['visible'] = false;
		$this->_table->fields['date_of_death']['visible'] = false;
		$this->_table->fields['last_login']['visible'] = false;
		$this->_table->fields['photo_name']['visible'] = false;
	}

	public function indexBeforeAction(Event $event) {
		$this->_table->ControllerAction->addField('photo_content', [
			'type' => 'image',
		]);
		$this->_table->fields['username']['visible']['index'] = false;
		$this->_table->fields['birthplace_area_id']['visible']['index'] = false;
		$this->_table->fields['photo_content']['visible']['index'] = true;
		$this->_table->ControllerAction->setFieldOrder(['photo_content']);

		$indexDashboard = 'Staff.Staff/dashboard';
		$this->_table->controller->set('indexDashboard', $indexDashboard);
	}
}
