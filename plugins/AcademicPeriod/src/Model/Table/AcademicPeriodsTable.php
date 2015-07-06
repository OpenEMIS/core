<?php
namespace AcademicPeriod\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

class AcademicPeriodsTable extends AppTable {
	private $_fieldOrder = ['visible', 'current', 'code', 'name', 'start_date', 'end_date', 'academic_period_level_id'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Parents', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Levels', ['className' => 'AcademicPeriod.AcademicPeriodLevels', 'foreignKey' => 'academic_period_level_id']);
		$this->addBehavior('Tree');
	}

	public function beforeAction(Event $event) {
		// $this->ControllerAction->field('area_level_id');

		$this->fields['start_year']['visible'] = false;
		$this->fields['end_year']['visible'] = false;
		$this->fields['school_days']['visible'] = false;
		$this->fields['available']['visible'] = false;
		$this->fields['school_days']['visible'] = false;
		

		$this->fields['lft']['visible'] = false;
		$this->fields['lft']['visible'] = false;
		$this->fields['rght']['visible'] = false;
	}

	public function afterAction(Event $event) {
		$this->ControllerAction->setFieldOrder($this->_fieldOrder);
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

				$action = $this->ControllerAction->buttons['index']['url'];
				$action['parent'] = $parentId;
				return $this->controller->redirect($action);
			}
		}
	}

	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $options) {
		$parentId = !is_null($this->request->query('parent')) ? $this->request->query('parent') : 0;

		$options['conditions'][] = [
        	$this->aliasField('parent_id') => $parentId
        ];
	}

	public function onGetCurrent(Event $event, Entity $entity) {
		return $entity->current == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
	}

	public function onGetName(Event $event, Entity $entity) {
		return $event->subject()->Html->link($entity->name, [
			'plugin' => $this->controller->plugin,
			'controller' => $this->controller->name,
			'action' => $this->alias,
			'index',
			'parent' => $entity->id
		]);
	}

	public function getList($query = NULL) {
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
		
		return $data;
	}

	public function getDate($dateObject) {
		if (is_object($dateObject)) {
			return $dateObject->toDateString();
		}
		return false;
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

	public function getAvailableAcademicPeriods($list = true, $order='DESC') {
		if($list) {
			$query = $this->find('list', ['keyField' => 'id', 'valueField' => 'name']);
		} else {
			$query = $this->find();
		}
		$result = $query->where([
						$this->aliasField('available') => 1,
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

}
