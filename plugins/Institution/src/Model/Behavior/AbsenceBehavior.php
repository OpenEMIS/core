<?php 
namespace Institution\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;

class AbsenceBehavior extends Behavior {
	public function initialize(array $config) {
		$this->_table->addBehavior('User.AdvancedNameSearch');
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.index.beforePaginate'] = ['callable' => 'indexBeforePaginate', 'priority' => 5];
		return $events;
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$options['auto_search'] = false;
		$search = $this->_table->ControllerAction->getSearchKey();
		if (!empty($search)) {
			// function from AdvancedNameSearchBehavior
			$query = $this->_table->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $search]);
		}
	}

	public function getAbsenceDaysBySettings($firstDateAbsent, $lastDateAbsent, $settingWeekdays){
		$stampFirstDateAbsent = strtotime($firstDateAbsent);
		$stampLastDateAbsent = strtotime($lastDateAbsent);
		
		$totalWeekdays = 0;
		while($stampFirstDateAbsent <= $stampLastDateAbsent){
			$weekday = strtolower(date('l', $stampFirstDateAbsent));
			if(in_array($weekday, $settingWeekdays)){
				$totalWeekdays++;
			}
			
			$stampFirstDateAbsent = strtotime('+1 day', $stampFirstDateAbsent);
		}
		
		return $totalWeekdays;
	}

	public function getWeekdaysBySetting(){
		$weekdaysArr = array(
			1 => 'monday',
			2 => 'tuesday',
			3 => 'wednesday',
			4 => 'thursday',
			5 => 'friday',
			6 => 'saturday',
			7 => 'sunday'
		);
		
		$ConfigItems = TableRegistry::get('Configuration.ConfigItems');

		$settingFirstWeekDay = $ConfigItems->value('first_day_of_week');
		if(empty($settingFirstWeekDay) || !in_array($settingFirstWeekDay, $weekdaysArr)){
			$settingFirstWeekDay = 'monday';
		}
		
		$settingDaysPerWek = intval($ConfigItems->value('days_per_week'));
		if(empty($settingDaysPerWek)){
			$settingDaysPerWek = 5;
		}
		
		foreach($weekdaysArr AS $index => $weekday){
			if($weekday == $settingFirstWeekDay){
				$firstWeekdayIndex = $index;
				break;
			}
		}
		
		$newIndex = $firstWeekdayIndex + $settingDaysPerWek;
		
		$weekdays = array();
		for($i=$firstWeekdayIndex; $i<$newIndex; $i++){
			if($i<=7){
				$weekdays[] = $weekdaysArr[$i];
			}else{
				$weekdays[] = $weekdaysArr[$i%7];
			}
		}
		
		return $weekdays;
	}

	// public function beforeFind(Event $event, Query $query, $options) {
	// 	$query
	// 		->join([
	// 			'table' => 'institution_students',
	// 			'alias' => 'InstitionStudents',
	// 			'type' => 'INNER',
	// 			'conditions' => 'Users.id = InstitionStudents.security_user_id',
	// 		])
	// 		->group('Users.id');
	// }

	// public function implementedEvents() {
	// 	$events = parent::implementedEvents();
	// 	$newEvent = [
	// 		'ControllerAction.Model.beforeAction' => 'beforeAction',
	// 		'ControllerAction.Model.index.beforeAction' => 'indexBeforeAction'
	// 	];
	// 	$events = array_merge($events,$newEvent);
	// 	return $events;
	// }

	// public function beforeAction(Event $event) {
	// 	$this->_table->fields['super_admin']['visible'] = false;
	// 	$this->_table->fields['status']['visible'] = false;
	// 	$this->_table->fields['date_of_death']['visible'] = false;
	// 	$this->_table->fields['last_login']['visible'] = false;
	// 	$this->_table->fields['photo_name']['visible'] = false;
	// }

	// public function indexBeforeAction(Event $event) {
	// 	$this->_table->ControllerAction->addField('Picture', [
	// 		'type' => 'element',
	// 		'element' => 'Student.Students/picture'
	// 	]);
	// 	$this->_table->fields['username']['visible']['index'] = false;
	// 	$this->_table->fields['birthplace_area_id']['visible']['index'] = false;
	// 	$this->_table->fields['photo_content']['visible']['index'] = false;

	// 	$indexDashboard = 'Student.Students/dashboard';
	// 	$this->_table->controller->set('indexDashboard', $indexDashboard);
	// }



}
