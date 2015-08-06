<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\MessagesTrait;

class StudentAttendancesTable extends AppTable {
	use OptionsTrait;
	use MessagesTrait;
	private $allDayOptions = [];

	public function initialize(array $config) {
		$this->table('institution_site_section_students');
		$config['Modified'] = false;
		$config['Created'] = false;
		parent::initialize($config);

		$this->belongsTo('StudentCategories', ['className' => 'FieldOption.StudentCategories']);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'security_user_id']);
		$this->belongsTo('InstitutionSiteSections', ['className' => 'Institution.InstitutionSiteSections']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	/* Thed: to be completed
	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }
    */

	// Event: ControllerAction.Model.beforeAction
	public function beforeAction(Event $event) {
		$this->ControllerAction->field('openemis_no');
		$this->ControllerAction->field('security_user_id', ['order' => 2]);
		
		$this->ControllerAction->field('institution_site_section_id', ['visible' => false]);
		$this->ControllerAction->field('education_grade_id', ['visible' => false]);
		$this->ControllerAction->field('status', ['visible' => false]);

		$tabElements = [
			'Attendance' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentAttendances'],
				'text' => __('Attendance')
			],
			'Absence' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentAbsences'],
				'text' => __('Absence')
			]
		];

		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', 'Attendance');
	}

	// Event: ControllerAction.Model.afterAction
	/* Thed: to be completed
	public function afterAction(Event $event, ArrayObject $config) {
		if ($this->request->query('mode') == 'edit') {
			$config['form'] = true;
			$config['formButtons'] = true;
			$config['url'] = $config['buttons']['index']['url'];
			$config['url'][0] = 'indexEdit';
		}
	}
	*/

	// Event: ControllerAction.Model.onGetOpenemisNo
	public function onGetOpenemisNo(Event $event, Entity $entity) {
		return $entity->user->openemis_no;
	}

	// Event: ControllerAction.Model.onGetType
	public function onGetType(Event $event, Entity $entity) {
		if (!is_null($this->request->query('mode'))) {
			/* Thed: to be completed
			$types = [
				'0' => __('Present'),
				'1' => __('Absent - Excused'),
				'2' => __('Absent - Unexcused')
			];

			$form = $event->subject()->Form;
			$options = [];
			$options['label'] = false;
			$options['type'] = 'select';
            // $options['default'] = $attr['value'];
			$options['options'] = $types;
			// $options['onchange'] = "$('#reload').click()";

			$type = $form->input("InstitutionSiteStudentAbsence.".$entity->security_user_id.".type", $options);
			$type .= $form->hidden("InstitutionSiteStudentAbsence.".$entity->security_user_id.".security_user_id", ['value' => $entity->security_user_id]);
			if (isset($entity->InstitutionSiteStudentAbsence['id'])) {
				$type .= $form->hidden("InstitutionSiteStudentAbsence.".$entity->security_user_id.".id", ['value' => $entity->InstitutionSiteStudentAbsence['id']]);
			}
			*/
		} else {
			$types = $this->getSelectOptions('Absence.types');
			$type = $types['EXCUSED'];

			if (empty($entity->StudentAbsences['id'])) {
				$type = '<i class="fa fa-check"></i>';
			} else {
				if (empty($entity->StudentAbsences['student_absence_reason_id'])) {
					$type = $types['UNEXCUSED'];
				}
			}
		}

		return $type;
	}

	// Event: ControllerAction.Model.onGetReason
	public function onGetReason(Event $event, Entity $entity) {
		$reasonId = $entity->StudentAbsences['student_absence_reason_id'];
		$StudentAbsenceReasons = TableRegistry::get('FieldOption.StudentAbsenceReasons');

		if (!empty($reasonId)) {
			$obj = $StudentAbsenceReasons->findById($reasonId)->first();
			return $obj['name'];
		} else {
			return '-';
		}
	}

	public function onGetSunday(Event $event, Entity $entity) {
		return $this->getAbsenceData($event, $entity, 'sunday');
	}

	public function onGetMonday(Event $event, Entity $entity) {
		return $this->getAbsenceData($event, $entity, 'monday');
	}

	public function onGetTuesday(Event $event, Entity $entity) {
		return $this->getAbsenceData($event, $entity, 'tuesday');
	}

	public function onGetWednesday(Event $event, Entity $entity) {
		return $this->getAbsenceData($event, $entity, 'wednesday');
	}

	public function onGetThursday(Event $event, Entity $entity) {
		return $this->getAbsenceData($event, $entity, 'thursday');
	}

	public function onGetFriday(Event $event, Entity $entity) {
		return $this->getAbsenceData($event, $entity, 'friday');
	}

	public function onGetSaturday(Event $event, Entity $entity) {
		return $this->getAbsenceData($event, $entity, 'saturday');
	}

	public function getAbsenceData(Event $event, Entity $entity, $key) {
		$value = '<i class="fa fa-check"></i>';

		if (isset($entity->StudentAbsences['id'])) {
			$startDate = $entity->StudentAbsences['start_date'];
			$endDate = $entity->StudentAbsences['end_date'];
			$currentDay = $this->allDayOptions[$key]['date'];
			if ($currentDay >= $startDate && $currentDay <= $endDate) {
				$InstitutionSiteStudentAbsences = TableRegistry::get('Institution.InstitutionSiteStudentAbsences');
				$absenceQuery = $InstitutionSiteStudentAbsences
					->findById($entity->StudentAbsences['id'])
					->contain('StudentAbsenceReasons');
				$absenceResult = $absenceQuery->first();

				$typeOptions = $this->getSelectOptions('Absence.types');
				if (empty($absenceResult->student_absence_reason)) {
					$absenceType = 'UNEXCUSED';
				} else {
					$absenceType = 'EXCUSED';
				}
				if ($absenceResult->full_day == 0) {
					$urlLink = sprintf(__('Absent') . ' - ' . $typeOptions[$absenceType]. ' (%s - %s)' , $absenceResult->start_time, $absenceResult->end_time);
				} else {
					$urlLink = __('Absent') . ' - ' . $typeOptions[$absenceType]. ' (full day)';
				}

				$StudentAbsences = TableRegistry::get('Institution.StudentAbsences');
				$value = $event->subject()->Html->link($urlLink, [
					'plugin' => $this->controller->plugin,
					'controller' => $this->controller->name,
					'action' => $StudentAbsences->alias(),
					'view',
					$entity->StudentAbsences['id']
				]);
			}
		}

		return $value;
	}

	// Event: ControllerAction.Model.index.beforeAction
	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$toolbarElements = [
			['name' => 'Institution.Attendance/controls', 'data' => [], 'options' => []]
		];
		$this->controller->set('toolbarElements', $toolbarElements);

		// Setup period options
		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$periodOptions = $AcademicPeriod->getList();
		
		$Sections = TableRegistry::get('Institution.InstitutionSiteSections');
		$institutionId = $this->Session->read('Institutions.id');
		$selectedPeriod = $this->queryString('period_id', $periodOptions);

		$this->advancedSelectOptions($periodOptions, $selectedPeriod, [
			'message' => '{{label}} - ' . $this->getMessage('general.noSections'),
			'callable' => function($id) use ($Sections, $institutionId) {
				return $Sections->findByInstitutionSiteIdAndAcademicPeriodId($institutionId, $id)->count();
			}
		]);
		// End setup periods

		if ($selectedPeriod != 0) {
			$todayDate = date("Y-m-d");
			$this->controller->set(compact('periodOptions', 'selectedPeriod'));

			// Setup week options
			$weeks = $AcademicPeriod->getAttendanceWeeks($selectedPeriod);
			$weekStr = 'Week %d (%s - %s)';
			$weekOptions = [];
			$currentWeek = null;
			foreach ($weeks as $index => $dates) {
				if ($todayDate >= $dates[0]->format('Y-m-d') && $todayDate <= $dates[1]->format('Y-m-d')) {
					$weekStr = __('Current Week') . ' %d (%s - %s)';
					$currentWeek = $index;
				} else {
					$weekStr = 'Week %d (%s - %s)';
				}
				$weekOptions[$index] = sprintf($weekStr, $index, $this->formatDate($dates[0]), $this->formatDate($dates[1]));
			}
			$selectedYear = $AcademicPeriod->get($selectedPeriod)->start_year;
			if ($selectedYear == date("Y") && !is_null($currentWeek)) {
				$selectedWeek = !is_null($this->request->query('week')) ? $this->request->query('week') : $currentWeek;
			} else {
				$selectedWeek = $this->queryString('week', $weekOptions);
			}
			$this->advancedSelectOptions($weekOptions, $selectedWeek);
			$this->controller->set(compact('weekOptions', 'selectedWeek'));
			// end setup weeks

			// Setup day options
			$ConfigItems = TableRegistry::get('ConfigItems');
			$firstDayOfWeek = $ConfigItems->value('first_day_of_week');
			$daysPerWeek = $ConfigItems->value('days_per_week');
			$schooldays = [];

			for($i=0; $i<$daysPerWeek; $i++) {
				$schooldays[] = ($firstDayOfWeek + $i) % 7;
			}

			$week = $weeks[$selectedWeek];
			$dayOptions = [-1 => ['value' => -1, 'text' => __('All Days')]];
			$firstDayOfWeek = $week[0]->copy();
			$firstDay = -1;
			$today = null;

			do {
				if (in_array($firstDayOfWeek->dayOfWeek, $schooldays)) {
					if ($firstDay == -1) {
						$firstDay = $firstDayOfWeek->dayOfWeek;
					}
					if ($firstDayOfWeek->isToday()) {
						$today = $firstDayOfWeek->dayOfWeek;
					}
					$dayOptions[$firstDayOfWeek->dayOfWeek] = [
						'value' => $firstDayOfWeek->dayOfWeek,
						'text' => __($firstDayOfWeek->format('l')) . ' (' . $this->formatDate($firstDayOfWeek) . ')',
					];
					$this->allDayOptions[strtolower($firstDayOfWeek->format('l'))] = [
						'date' => $firstDayOfWeek->format('Y-m-d'),
						'text' => __($firstDayOfWeek->format('l'))
					];
				}
				$firstDayOfWeek->addDay();
			} while($firstDayOfWeek->lte($week[1]));

			$selectedDay = -1;
			if (isset($this->request->query['day'])) {
				$selectedDay = $this->request->query('day');
				if (!array_key_exists($selectedDay, $dayOptions)) {
					$selectedDay = $firstDay;
				}
			} else {
				if (!is_null($today)) {
					$selectedDay = $today;
				} else {
					$selectedDay = $firstDay;
				}
			}
			$dayOptions[$selectedDay][] = 'selected';
			
			$currentDay = $week[0]->copy();
			if ($selectedDay != -1) {
				if ($currentDay->dayOfWeek != $selectedDay) {
					$selectedDate = $currentDay->next($selectedDay);
				} else {
					$selectedDate = $currentDay;
				}
			} else {
				$selectedDate = $week;
			}
			$this->controller->set(compact('dayOptions', 'selectedDay'));
			// End setup days

			// Setup section options
			$userId = $this->Auth->user('id');
			$AccessControl = $this->AccessControl;
			$sectionOptions = $Sections
				->find('list')
				->find('byAccess', ['userId' => $userId, 'accessControl' => $AccessControl]) // restrict user to see own class if permission is set
				->where([
					$Sections->aliasField('institution_site_id') => $institutionId, 
					$Sections->aliasField('academic_period_id') => $selectedPeriod
				])
				->toArray();

			$selectedSection = $this->queryString('section_id', $sectionOptions);
			$this->advancedSelectOptions($sectionOptions, $selectedSection);
			$this->controller->set(compact('sectionOptions', 'selectedSection'));
			// End setup sections

			$settings['pagination'] = false;
			$query
				->contain(['Users'])
				->find('withAbsence', ['date' => $selectedDate])
				->where([$this->aliasField('institution_site_section_id') => $selectedSection]);

			if ($selectedDay == -1) {
				foreach ($this->allDayOptions as $key => $obj) {
					$this->ControllerAction->addField($key);
				}
			} else {
				$this->ControllerAction->field('type');
				$this->ControllerAction->field('reason');
			}
		} else {
			//
			$settings['pagination'] = false;
			$query
				->where([$this->aliasField('security_user_id') => 0]);

			$this->ControllerAction->field('type');
			$this->ControllerAction->field('reason');

			$this->Alert->warning('StudentAttendances.noSections');
		}
	}

	public function findWithAbsence(Query $query, array $options) {
		$date = $options['date'];

		$conditions = ['StudentAbsences.security_user_id = StudentAttendances.security_user_id'];
		if (is_array($date)) {
			$startDate = $date[0]->format('Y-m-d');
			$endDate = $date[1]->format('Y-m-d');

			$conditions['OR'] = [
				'OR' => [
					[
						'StudentAbsences.end_date IS NOT NULL',
						'StudentAbsences.start_date >=' => $startDate,
						'StudentAbsences.start_date <=' => $endDate
					],
					[
						'StudentAbsences.end_date IS NOT NULL',
						'StudentAbsences.start_date <=' => $startDate,
						'StudentAbsences.end_date >=' => $startDate
					],
					[
						'StudentAbsences.end_date IS NOT NULL',
						'StudentAbsences.start_date <=' => $endDate,
						'StudentAbsences.end_date >=' => $endDate
					],
					[
						'StudentAbsences.end_date IS NOT NULL',
						'StudentAbsences.start_date >=' => $startDate,
						'StudentAbsences.end_date <=' => $startDate
					]
				],
				[
					'StudentAbsences.end_date IS NULL',
					'StudentAbsences.start_date <=' => $endDate
				]
			];
		} else {
			$conditions['StudentAbsences.start_date <= '] = $date->format('Y-m-d');
			$conditions['StudentAbsences.end_date >= '] = $date->format('Y-m-d');
		}
    	return $query
    		->select([
    			$this->aliasField('security_user_id'), 
    			'Users.openemis_no', 'Users.first_name', 'Users.last_name',
    			'StudentAbsences.id',
    			'StudentAbsences.start_date',
    			'StudentAbsences.end_date',
    			'StudentAbsences.student_absence_reason_id'
    		])
			->join([
				[
					'table' => 'institution_site_student_absences',
					'alias' => 'StudentAbsences',
					'type' => 'LEFT',
					'conditions' => $conditions
				]
			])
			->order(['Users.openemis_no'])
			;
    }

	/* Thed: to be completed
    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {    	
    	if ($this->request->query('day') != -1) {
			if (is_null($this->request->query('mode'))) {
				$toolbarButtons['edit'] = $buttons['index'];
		    	$toolbarButtons['edit']['url'][0] = 'index';
				$toolbarButtons['edit']['url']['mode'] = 'edit';
				$toolbarButtons['edit']['type'] = 'button';
				$toolbarButtons['edit']['label'] = '<i class="fa kd-edit"></i>';
				$toolbarButtons['edit']['attr'] = $attr;
				$toolbarButtons['edit']['attr']['title'] = __('Edit');

				$toolbarButtons['back'] = $buttons['back'];
				$toolbarButtons['back']['type'] = null;
			} else {
				$toolbarButtons['back'] = $buttons['back'];
				if ($toolbarButtons['back']['url']['mode']) {
					unset($toolbarButtons['back']['url']['mode']);
				}
				$toolbarButtons['back']['type'] = 'button';
				$toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
				$toolbarButtons['back']['attr'] = $attr;
				$toolbarButtons['back']['attr']['title'] = __('Back');
			}
		}
    }

	public function indexEdit() {
		if ($this->request->is(['post', 'put'])) {
			// pr($this->request->data);die;
		}
		$action = $this->ControllerAction->buttons['index']['url'];
		if (isset($action['mode'])) {
			unset($action['mode']);
		}
		return $this->controller->redirect($action);
	}
	*/
}
