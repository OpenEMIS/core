<?php 
namespace Guardian\Model\Behavior;

use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\Event\Event;

class GuardianBehavior extends Behavior {
	public function initialize(array $config) {
	}


	public function beforeFind(Event $event, Query $query, $options) {
		// todo:mlee wrong SQL - needs to be InstitionSiteGuardians.security_user_id?
		// $query
		// 	->join([
		// 		'table' => 'institution_site_guardians',
		// 		'alias' => 'InstitionSiteGuardians',
		// 		'type' => 'INNER',
		// 		'conditions' => 'Users.id = InstitionSiteGuardians.guardian_id',
		// 	])
		// 	->group('SecurityUsers.id');
	}

}

?>