<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\Utility\Inflector;

class StandardReportsTable extends ReportsTable  {

	public function initialize(array $config) {
		parent::initialize($config);
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		
	}

	public function addEditBeforeAction(Event $event, ArrayObject $extra) {
		$this->field('name');
		$this->field('target', ['type' => 'hidden']);
		$this->field('query');

		// $query = '{"from":["`institution_students` AS `InstitutionStudents`"], 
		// "join":[{"table":"security_users","type":"INNER","alias":"SecurityUsers","conditions":["`SecurityUsers`.`id` = `InstitutionStudents`.`student_id`"]}], "select":["`InstitutionStudents`.`id` AS `student name`"],
		// "where":["`InstitutionStudents`.`academic_period_id` = 10"],
		// "having":["COUNT(`InstitutionStudents`.`student_status_id`) > 0"],
		// "group":["`InstitutionStudents`.`student_status_id`"]
		// }';
		// $entity = $this->newEntity();
		// $entity->query = $query;
		// $this->setupValues($entity);
		// pr($entity);die;
		
	}
}