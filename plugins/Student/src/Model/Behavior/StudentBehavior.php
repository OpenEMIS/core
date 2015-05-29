<?php 
namespace Student\Model\Behavior;

use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\Event\Event;

class StudentBehavior extends Behavior {
	public function initialize(array $config) {
	}


	public function beforeFind(Event $event, Query $query, $options) {
		// todo:mlee wrong SQL - needs to be InstitionSiteStudents.security_user_id
		$query
			->join([
				'table' => 'institution_site_students',
				'alias' => 'InstitionSiteStudents',
				'type' => 'INNER',
				'conditions' => 'Users.id = InstitionSiteStudents.security_user_id',
			])
			->group('Users.id');
	}

}

?>