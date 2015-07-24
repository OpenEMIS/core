<?php
namespace AcademicPeriod\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Network\Exception\NotFoundException;

class AcademicPeriodsTable extends AppTable {
	private $_fieldOrder = ['visible', 'current', 'code', 'name', 'start_date', 'end_date', 'academic_period_level_id'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Parents', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Levels', ['className' => 'AcademicPeriod.AcademicPeriodLevels', 'foreignKey' => 'academic_period_level_id']);
		$this->addBehavior('Tree');
	}

	public function validationDefault(Validator $validator) {
		$additionalParameters = ['available = 1 AND visible > 0'];

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
		parent::beforeSave($event, $entity, $options);
		$entity->start_year = date("Y", strtotime($entity->start_date));
		$entity->end_year = date("Y", strtotime($entity->end_date));
		if ($entity->current == 1) {
			$this->updateAll(['current' => 0], []);
		}
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('academic_period_level_id');
		$this->ControllerAction->field('current');

		$this->fields['start_year']['visible'] = false;
		$this->fields['end_year']['visible'] = false;
		$this->fields['school_days']['visible'] = false;
		$this->fields['available']['visible'] = false;
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
		return $attr;
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

	public function getCurrent() {
		$result = $this->find()
			->select([$this->aliasField('id')])
			->where([
				$this->aliasField('available') => 1,
				$this->aliasField('visible').' > 0',
				$this->aliasField('current') => 1
			])
			->first();
		if(!empty($result)) {
			$result->toArray();
			return (!empty($result['id'])) ? $result['id'] : '';
		}	else {
			return $this->patchCurrentPeriod();
		}
	}

	public function patchCurrentPeriod(){
		$today_date = date('Y-m-d');
		$selectedCurrentIdThroughPatch = 0;

		//clear any other academic period whose current is set to 1, while being available = 1 and visible = 0
		$academicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$academicPeriods =  $this->find();
		foreach($academicPeriods as $academicPeriod) {
			$academicPeriod->current = 0;
			$academicPeriodTable->save($academicPeriod);
		}

		//patch data to contain at least 1 academic record set to current
		//pick period that current date falls between start and end dates
		$query = $this->find()
					->select([$this->aliasField('id')])
					->where([
						$this->aliasField('available') => 1,
						$this->aliasField('visible').' > 0',
					]);

		$result = $query
					->andWhere(['"'.$today_date.'" BETWEEN start_date AND end_date'])
					->order(['start_date DESC'])
					->first();		

		if(!empty($result))	{
			$result->current = 1;
			if($academicPeriodTable->save($result))
				$selectedCurrentIdThroughPatch = $result->id;
		} else {
			//pick the latest academic period
			$latestAcademicPeriod = $query
									->order(['start_date DESC'])
									->first();
			if(!empty($latestAcademicPeriod)) {
				$latestAcademicPeriod->current = 1;
				$academicPeriodTable->save($latestAcademicPeriod);
				$selectedCurrentIdThroughPatch = $latestAcademicPeriod->id;
			} else {
				//no visible nor available academic period
				$anyAvailablePeriod = $this->find()
										->select([$this->aliasField('id')])
										->order(['start_date DESC'])
										->first();
				if(!empty($anyAvailablePeriod)) {
					$anyAvailablePeriod->current = 1;
					$academicPeriodTable->save($anyAvailablePeriod);
					$selectedCurrentIdThroughPatch = $anyAvailablePeriod->id;
				} else {
					//nothing found in database
					throw new NotFoundException('AcademicPeriod table is empty. kindly approach Administrator.');
				}
											
			}						

		}
		return $selectedCurrentIdThroughPatch;
	}

}
