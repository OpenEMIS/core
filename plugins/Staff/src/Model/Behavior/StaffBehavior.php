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
		$events = [
			'ControllerAction.Model.index.beforeAction' => 'indexBeforeAction'
		];
		return $events;
	}

	public function indexBeforeAction(Event $event) {
		$this->_table->fields['username']['visible']['index'] = false;
		$this->_table->fields['birthplace_area_id']['visible']['index'] = false;
		$this->_table->fields['date_of_death']['visible']['index'] = false;
		$this->_table->fields['super_admin']['visible']['index'] = false;
		$this->_table->fields['status']['visible']['index'] = false;
		$this->_table->fields['last_login']['visible']['index'] = false;
		$this->_table->fields['photo_name']['visible']['index'] = false;
		$this->_table->fields['photo_content']['visible']['index'] = false;

		$indexDashboard = 'Staff.Staff/dashboard';
		$this->_table->controller->set('indexDashboard', $indexDashboard);
	}
}
