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

class StudentAttendanceTable extends AppTable {
	use OptionsTrait;
	use MessagesTrait;

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

	// Event: ControllerAction.Model.beforeAction
	public function beforeAction(Event $event) {
		$this->ControllerAction->field('openemis_no');
		$this->ControllerAction->field('security_user_id', ['order' => 2]);
		
		$this->ControllerAction->field('institution_site_section_id', ['visible' => false]);
		$this->ControllerAction->field('education_grade_id', ['visible' => false]);
		$this->ControllerAction->field('student_category_id', ['visible' => false]);
		$this->ControllerAction->field('status', ['visible' => false]);

		$tabElements = [
			'Attendance' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentAttendance'],
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

	// Event: ControllerAction.Model.onGetOpenemisNo
	public function onGetOpenemisNo(Event $event, Entity $entity) {
		return $entity->user->openemis_no;
	}

	// Event: ControllerAction.Model.onGetType
	public function onGetType(Event $event, Entity $entity) {
		$types = $this->getSelectOptions('Absence.types');
		
		$type = $types['EXCUSED'];
		if (empty($entity->InstitutionSiteStudentAbsence['student_absence_reason_id'])) {
			$type = $types['UNEXCUSED'];
		}
		return $type;
	}

	// Event: ControllerAction.Model.onGetReason
	public function onGetReason(Event $event, Entity $entity) {
		$reasonId = $entity->InstitutionSiteStudentAbsence['student_absence_reason_id'];
		$StudentAbsenceReasons = TableRegistry::get('FieldOption.StudentAbsenceReasons');

		if (!empty($reasonId)) {
			$obj = $StudentAbsenceReasons->findById($reasonId)->first();
			return $obj['name'];
		} else {
			return '-';
		}
	}

	// Event: ControllerAction.Model.index.beforeAction
	public function indexBeforeAction(Event $event) {
		$toolbarElements = [
			['name' => 'Institution.Attendance/controls', 'data' => [], 'options' => []]
		];
		$this->controller->set('toolbarElements', $toolbarElements);

		// Setup period options
		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$periodOptions = $AcademicPeriod->getList();
		
		$Sections = TableRegistry::get('Institution.InstitutionSiteSections');
		$institutionId = $this->Session->read('Institutions.id');
		$selectedPeriod = $this->queryString('academic_period_id', $periodOptions);

		$this->advancedSelectOptions($periodOptions, $selectedPeriod, [
			'message' => '{{label}} - ' . $this->getMessage('general.noSections'),
			'callable' => function($id) use ($Sections, $institutionId) {
				return $Sections->findByInstitutionSiteIdAndAcademicPeriodId($institutionId, $id)->count();
			}
		]);
		$this->controller->set(compact('periodOptions'));
		// End setup periods

		// Setup week options
		$weeks = $AcademicPeriod->getAttendanceWeeks($selectedPeriod);
		$weekStr = 'Week %d (%s - %s)';
		$weekOptions = [];
		foreach ($weeks as $index => $dates) {
			$weekOptions[$index] = sprintf($weekStr, $index, $this->formatDate($dates[0]), $this->formatDate($dates[1]));
		}
		$selectedWeek = $this->queryString('week', $weekOptions);
		$this->advancedSelectOptions($weekOptions, $selectedWeek);
		$this->controller->set(compact('weekOptions'));
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
				$this->request->query['date'] = $currentDay->next($selectedDay);
			} else {
				$this->request->query['date'] = $currentDay;
			}
		} else {
			$this->request->query['date'] = $week;
		}
		$this->controller->set(compact('dayOptions'));
		// End setup days

		// Setup section options
		$sectionOptions = $Sections
			->find('list')
			->where([
				$Sections->aliasField('institution_site_id') => $institutionId, 
				$Sections->aliasField('academic_period_id') => $selectedPeriod
			])
			->toArray();

		$selectedSection = $this->queryString('section_id', $sectionOptions);
		$this->advancedSelectOptions($sectionOptions, $selectedSection);
		$this->controller->set(compact('sectionOptions'));
		// End setup sections
	}

	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $options) {
		$sectionId = $request->query('section_id');
		$week = $request->query('week');
		$day = $request->query('day');
		$date = $request->query('date');

		$options['contain'] = ['Users'];
		$options['finder'] = ['withAbsence' => ['date' => $date]];
		$options['conditions'][$this->aliasField('institution_site_section_id')] = $sectionId;

		if ($day == -1) {
			$this->ControllerAction->field('monday');
			
		} else {
			$this->ControllerAction->field('type');
			$this->ControllerAction->field('reason');
		}

		return $options;
	}

	public function indexAfterPaginate(Event $event, $data) {
		foreach ($data as $row) {
			// pr($row);
		}
	}

	public function findWithAbsence(Query $query, array $options) {
		$date = $options['date'];

		$conditions = ['InstitutionSiteStudentAbsence.security_user_id = StudentAttendance.security_user_id'];
		if (is_array($date)) {
			$conditions['InstitutionSiteStudentAbsence.start_date >= '] = $date[0]->format('Y-m-d');
			$conditions['InstitutionSiteStudentAbsence.start_date <= '] = $date[1]->format('Y-m-d');
		} else {
			$conditions['InstitutionSiteStudentAbsence.start_date <= '] = $date->format('Y-m-d');
			$conditions['InstitutionSiteStudentAbsence.end_date >= '] = $date->format('Y-m-d');
		}
    	return $query
    		->select([
    			$this->aliasField('security_user_id'), 
    			'Users.openemis_no', 'Users.first_name', 'Users.last_name',
    			'InstitutionSiteStudentAbsence.start_date',
    			'InstitutionSiteStudentAbsence.end_date',
    			'InstitutionSiteStudentAbsence.student_absence_reason_id'
    		])
			->join([
				[
					'table' => 'institution_site_student_absences',
					'alias' => 'InstitutionSiteStudentAbsence',
					'type' => 'LEFT',
					'conditions' => $conditions
				]
			])
			->order(['Users.openemis_no'])
			;
    }
}
