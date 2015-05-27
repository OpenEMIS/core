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
		// todo:mlee wrong SQL - needs to be InstitionSiteStaff.security_user_id
		$query
			->join([
				'table' => 'institution_site_staff',
				'alias' => 'InstitionSiteStaff',
				'type' => 'INNER',
				'conditions' => 'SecurityUsers.id = InstitionSiteStaff.staff_id',
			])
			->group('SecurityUsers.id');
	}

}

?>