<?php
namespace AcademicPeriod\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Network\Exception\NotFoundException;
use Cake\Datasource\Exception\RecordNotFoundException;

class AcademicPeriodsTable extends AppTable {
	private $_fieldOrder = ['visible', 'current', 'editable', 'code', 'name', 'start_date', 'end_date', 'academic_period_level_id'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Parents', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Levels', ['className' => 'AcademicPeriod.AcademicPeriodLevels', 'foreignKey' => 'academic_period_level_id']);
		$this->addBehavior('Tree');
	}

	public function validationDefault(Validator $validator) {
		$additionalParameters = ['editable = 1 AND visible > 0'];

		return $validator
 	        ->add('end_date', 'ruleCompareDateReverse', [
		            'rule' => ['compareDateReverse', 'start_date', false]
	    	    ])
 	        ->add('current', 'ruleValidateNeeded', [
				'rule' => ['validateNeeded', 'current', $additionalParameters],
			])
	        ;
	}

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		$entity->start_year = date("Y", strtotime($entity->start_date));
		$entity->end_year = date("Y", strtotime($entity->end_date));
		if ($entity->current == 1) {
			$entity->editable = 1;
			$entity->visible = 1;
			$this->updateAll(['current' => 0], []);
		}
	}
	public function beforeAction (Event $event) {
		$this->ControllerAction->field('academic_period_level_id');
		$this->fields['start_year']['visible'] = false;
		$this->fields['end_year']['visible'] = false;
		$this->fields['school_days']['visible'] = false;
		$this->fields['lft']['visible'] = false;
		$this->fields['rght']['visible'] = false;
	}

	public function afterAction(Event $event) {
		$this->ControllerAction->field('current');
		$this->ControllerAction->field('editable');
		$this->ControllerAction->setFieldOrder($this->_fieldOrder);
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$this->request->data[$this->alias()]['current'] = $entity->current;
		$this->ControllerAction->field('visible');
	}

	public function indexBeforeAction(Event $event) {
		// Add breadcrumb
		$toolbarElements = [
            ['name' => 'AcademicPeriod.breadcrumb', 'data' => [], 'options' => []]
        ];
		$this->controller->set('toolbarElements', $toolbarElements);

		$this->fields['parent_id']['visible'] = false;

		$parentId = !is_null($this->request->query('parent')) ? $this->request->query('parent') : 0;
		if ($parentId != 0) {
			$crumbs = $this
				->find('path', ['for' => $parentId])
				->order([$this->aliasField('lft')])
				->toArray();
			$this->controller->set('crumbs', $crumbs);
		} else {
			$results = $this
				->find('all')
				->select([$this->aliasField('id')])
				->where([$this->aliasField('parent_id') => 0])
				->all();

			if ($results->count() == 1) {
				$parentId = $results
					->first()
					->id;

				$action = $this->ControllerAction->url('index');
				$action['parent'] = $parentId;
				return $this->controller->redirect($action);
			}
		}
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$parentId = !is_null($this->request->query('parent')) ? $this->request->query('parent') : 0;
		$query->where([$this->aliasField('parent_id') => $parentId]);
	}

	public function addEditBeforeAction(Event $event) {
		//Setup fields
		$this->_fieldOrder = ['academic_period_level_id', 'code', 'name'];

		$this->fields['parent_id']['type'] = 'hidden';
		$parentId = $this->request->query('parent');

		if (is_null($parentId)) {
			$this->fields['parent_id']['attr']['value'] = -1;
		} else {
			$this->fields['parent_id']['attr']['value'] = $parentId;
			
			$crumbs = $this
				->find('path', ['for' => $parentId])
				->order([$this->aliasField('lft')])
				->toArray();

			$parentPath = '';
			foreach ($crumbs as $crumb) {
				$parentPath .= $crumb->name;
				$parentPath .= $crumb === end($crumbs) ? '' : ' > ';
			}

			$this->ControllerAction->field('parent', [
				'type' => 'readonly',
				'attr' => ['value' => $parentPath]
			]);

			array_unshift($this->_fieldOrder, "parent");
		}
	}
	
	public function onGetCurrent(Event $event, Entity $entity) {
		return $entity->current == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
	}

	// For PHPOE-1916
	public function onGetEditable(Event $event, Entity $entity) {
		return $entity->editable == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
	}
	// End PHPOE-1916

	public function onGetName(Event $event, Entity $entity) {
		return $event->subject()->Html->link($entity->name, [
			'plugin' => $this->controller->plugin,
			'controller' => $this->controller->name,
			'action' => $this->alias,
			'index',
			'parent' => $entity->id
		]);
	}

	public function onUpdateFieldAcademicPeriodLevelId(Event $event, array $attr, $action, Request $request) {
		$parentId = !is_null($this->request->query('parent')) ? $this->request->query('parent') : 0;
		$results = $this
			->find()
			->select([$this->aliasField('academic_period_level_id')])
			->where([$this->aliasField('id') => $parentId])
			->all();

		$attr['type'] = 'select';
		if (!$results->isEmpty()) {
			$data = $results->first();
			$levelId = $data->academic_period_level_id;

			$levelResults = $this->Levels
				->find()
				->select([$this->Levels->aliasField('level')])
				->where([$this->Levels->aliasField('id') => $levelId])
				->all();

			if (!$levelResults->isEmpty()) {
				$levelData = $levelResults->first();
				$level = $levelData->level;

				$levelOptions = $this->Levels
					->find('list')
					->where([$this->Levels->aliasField('level >') => $level])
					->toArray();
				$attr['options'] = $levelOptions;
			}
		}

		return $attr;
	}

	public function onUpdateFieldCurrent(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = $this->getSelectOptions('general.yesno');
		$attr['onChangeReload'] = true;
		return $attr;
	}

	public function onUpdateFieldEditable(Event $event, array $attr, $action, Request $request) {
		if (isset($request->data[$this->alias()]['current'])) {
			if ($request->data[$this->alias()]['current'] == 1) {
				$attr['type'] = 'hidden';
			}
		}
		$attr['options'] = $this->getSelectOptions('general.yesno');
		return $attr;
	}

	public function onUpdateFieldVisible(Event $event, array $attr, $action, Request $request) {
		if (isset($request->data[$this->alias()]['current'])) {
			if ($request->data[$this->alias()]['current'] == 1) {
				$attr['type'] = 'hidden';
			}
		}
		$attr['options'] = $this->getSelectOptions('general.yesno');
		return $attr;
	}

	public function getYearList() {
		$level = $this->Levels
			->find()
			->order([$this->Levels->aliasField('level ASC')])
			->first();

		$list = $this
			->find('list')
			->find('visible')
			->find('order')
			->where([$this->aliasField('academic_period_level_id') => $level->id])
			->toArray();

		return $list;
	}

	public function getList($params=[]) {

		$withLevels = array_key_exists('withLevels', $params) ? $params['withLevels'] : true;
		$withSelect = array_key_exists('withSelect', $params) ? $params['withSelect'] : false;

		if ( !$withLevels ) {
			$where = [
				$this->aliasField('current') => 1,
				$this->aliasField('parent_id') . ' <> ' => 0
			];

			// get the current period
			$data = $this->find('list')
				->find('visible')
				->find('order')
				->where($where)
				->toArray();
			
			// get all other periods
			$where[$this->aliasField('current')] = 0;
			$data += $this->find('list')
				->find('visible')
				->find('order')
				->where($where)
				->toArray();
		} else {
			$where = [
				$this->aliasField('parent_id') . ' <> ' => 0,
			];

			// get the current period
			$data = $this->find()
				->find('visible')
				->contain(['Levels'])
				->select([
						'id' => $this->aliasField('id'),
						'name' => $this->aliasField('name'),
						'level' => 'Levels.name'
					])
				->where($where)
				->order([$this->aliasField('academic_period_level_id'), $this->aliasField('order')])
				->toArray();

			$levelName = "";
			$list = [];

			foreach ($data as $obj) {
				if ($levelName != $obj->level) {
					$levelName = __($obj->level);
				}
				$list[$levelName][$obj->id] = __($obj->name);
			}

			$data = $list;
		}

		if ( $withSelect ) {
			$data = ['-1' => __('-- Select Period --')] + $data;
		}

		return $data;
	}

	public function getDate($dateObject) {
		if (is_object($dateObject)) {
			return $dateObject->toDateString();
		}
		return false;
	}

	public function getWorkingDaysOfWeek() {
		$weekdays = [
			0 => 'Sunday',
			1 => 'Monday',
			2 => 'Tuesday',
			3 => 'Wednesday',
			4 => 'Thursday',
			5 => 'Friday',
			6 => 'Saturday',
		];
		$ConfigItems = TableRegistry::get('ConfigItems');
		$firstDayOfWeek = $ConfigItems->value('first_day_of_week');
		$daysPerWeek = $ConfigItems->value('days_per_week');
		$lastDayIndex = ($firstDayOfWeek + $daysPerWeek - 1) % 7;
		$week = [];
		for ($i=0; $i<$daysPerWeek; $i++) {
			$week[] = $weekdays[$firstDayOfWeek++];
			$firstDayOfWeek = $firstDayOfWeek % 7;
		}
		return $week;
	}

	public function getAttendanceWeeks($id) {
		// $weekdays = array(
		// 	0 => 'sunday',
		// 	1 => 'monday',
		// 	2 => 'tuesday',
		// 	3 => 'wednesday',
		// 	4 => 'thursday',
		// 	5 => 'friday',
		// 	6 => 'saturday',
		// 	//7 => 'sunday'
		// );

		$period = $this->findById($id)->first();
		$ConfigItems = TableRegistry::get('ConfigItems');
		$firstDayOfWeek = $ConfigItems->value('first_day_of_week');
		$daysPerWeek = $ConfigItems->value('days_per_week');

		$lastDayIndex = ($firstDayOfWeek + $daysPerWeek - 1) % 7;
		$startDate = $period->start_date;

		$weekIndex = 1;
		$weeks = [];
		
		do {
			$endDate = $startDate->copy()->next($lastDayIndex);
			if ($endDate->gt($period->end_date)) {
				$endDate = $period->end_date;
			}
			$weeks[$weekIndex++] = [$startDate, $endDate];
			$startDate = $endDate->copy();
			$startDate->addDay();
		} while ($endDate->lt($period->end_date));
		
		return $weeks;
	}

	public function getEditable($academicPeriodId) {
		try {
			return $this->get($academicPeriodId)->editable;
		} catch (RecordNotFoundException $e) {
			return false;
		}
	}

	public function getAvailableAcademicPeriods($list = true, $order='DESC') {
		if($list) {
			$query = $this->find('list', ['keyField' => 'id', 'valueField' => 'name']);
		} else {
			$query = $this->find();
		}
		$result = $query->where([
						$this->aliasField('editable') => 1,
						$this->aliasField('visible') . ' >' => 0,
						$this->aliasField('parent_id') . ' >' => 0
					])
					->order($this->aliasField('name') . ' ' . $order);
		if ($result) {
			return $result->toArray();
		} else {
			return false;
		}
	}

	public function getCurrent() {
		$query = $this->find()
					->select([$this->aliasField('id')])
					->where([
						$this->aliasField('editable') => 1,
						$this->aliasField('visible').' > 0',
						$this->aliasField('current') => 1,
						$this->aliasField('parent_id').' > 0',
					])
					->order(['start_date DESC']);		
		$countQuery = $query->count();
		if($countQuery > 0) {
			$result = $query->first();
			return $result->id;
		} else {
			$query = $this->find()
					->select([$this->aliasField('id')])
					->where([
						$this->aliasField('editable') => 1,
						$this->aliasField('visible').' > 0',
						$this->aliasField('parent_id').' > 0',
					])
					->order(['start_date DESC']);
			$countQuery = $query->count();
			if($countQuery > 0) {
				$result = $query->first();
				return $result->id;
			} else {
				return 0;
			}
		}
	}

	public function generateMonthsByDates($startDate, $endDate) {
		$result = [];
		$stampStartDay = strtotime($startDate);
		$stampEndDay = strtotime($endDate);
		// $stampToday = strtotime(date('Y-m-d'));
		
		$stampFirstDayOfMonth = strtotime('01-' . date('m', $stampStartDay) . '-' . date('Y', $stampStartDay));
		// while($stampFirstDayOfMonth <= $stampEndDay && $stampFirstDayOfMonth <= $stampToday){
		while($stampFirstDayOfMonth <= $stampEndDay){
			$monthString = date('F', $stampFirstDayOfMonth);
			$monthNumber = date('m', $stampFirstDayOfMonth);
			$year = date('Y', $stampFirstDayOfMonth);
			
			$result[] = [
				'month' => ['inNumber' => $monthNumber, 'inString' => $monthString],
				'year' => $year
			];
			
			$stampFirstDayOfMonth = strtotime('+1 month', $stampFirstDayOfMonth);
		}
		
		return $result;
	}

	public function generateDaysOfMonth($year, $month, $startDate, $endDate){
		$days = [];
		$stampStartDay = strtotime($startDate);
		$stampEndDay = strtotime($endDate);
		// $stampToday = strtotime(date('Y-m-d'));
		
		$stampFirstDayOfMonth = strtotime($year . '-' . $month . '-01');
		$stampFirstDayNextMonth = strtotime('+1 month', $stampFirstDayOfMonth);	
		
		if($stampFirstDayOfMonth <= $stampStartDay){
			$tempStamp = $stampStartDay;
		}else{
			$tempStamp = $stampFirstDayOfMonth;
		}
		
		// while($tempStamp <= $stampEndDay && $tempStamp < $stampFirstDayNextMonth && $tempStamp < $stampToday){
		while($tempStamp <= $stampEndDay && $tempStamp < $stampFirstDayNextMonth){
			$weekDay = date('l', $tempStamp);
			$date = date('Y-m-d', $tempStamp);
			$day = date('d', $tempStamp);
			
			$days[] = [
				'weekDay' => $weekDay,
				'date' => $date,
				'day' => $day
			];
			
			$tempStamp = strtotime('+1 day', $tempStamp);
		}

		return $days;
	}
}
