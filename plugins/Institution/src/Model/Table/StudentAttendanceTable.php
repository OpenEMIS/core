<?php
namespace Institution\Model\Table;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class StudentAttendanceTable extends AppTable {
	use OptionsTrait;

	public function initialize(array $config) {
		$this->table('institution_site_student_absences');
		parent::initialize($config);
		//$this->addBehavior('Institution.Absence');
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'security_user_id']);
		// $this->belongsTo('InstitutionSiteSections', ['className' => 'Institution.InstitutionSiteSections']);
		$this->belongsTo('StudentAbsenceReasons', ['className' => 'FieldOption.StudentAbsenceReasons']);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function beforeAction(Event $event) {
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

		foreach ($periodOptions as $periodId => $period) {
			$count = $Sections->findByInstitutionSiteIdAndAcademicPeriodId($institutionId, $periodId)->count();
			if ($count == 0) {
				$periodOptions[$periodId] = ['value' => $periodId, 'text' => $period . ' - ' . __('No Sections'), 'disabled'];
			} else {
				if ($selectedPeriod == 0) {
					$periodOptions[$periodId] = ['value' => $periodId, 'text' => $period, 'selected'];
					$selectedPeriod = $periodId;
				} else if ($selectedPeriod == $periodId) {
					$periodOptions[$periodId] = ['value' => $periodId, 'text' => $period, 'selected'];
				}
			}
		}
		$this->controller->set(compact('periodOptions'));
		// End setup periods

		// Setup week options
		$weeks = $AcademicPeriod->getAttendanceWeeks($selectedPeriod);
		$weekStr = 'Week %d (%s - %s)';
		$weekOptions = [];
		foreach ($weeks as $index => $dates) { // jeff-TODO: need to set todays date as default
			$weekOptions[$index] = sprintf($weekStr, $index, $this->formatDate($dates[0]), $this->formatDate($dates[1]));
		}
		$selectedWeek = $this->queryString('week', $weekOptions);
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
		$dayOptions = [-1 => __('All Days')];
		do {
			$firstDay = $week[0];
			if (in_array($firstDay->dayOfWeek, $schooldays)) {
				$dayOptions[$week[0]->dayOfWeek] = __($firstDay->format('l')) . ' (' . $this->formatDate($firstDay) . ')';
			}
			$firstDay->addDay();
		} while($firstDay->lte($week[1]));
		$selectedDay = $this->queryString('day', $dayOptions);
		$this->controller->set(compact('dayOptions', 'selectedDay'));
		// End setup days
	}
}
