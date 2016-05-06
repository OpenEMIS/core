<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class StaffAbsencesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_staff_absences');
		parent::initialize($config);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'staff_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' =>'institution_id']);
		$this->belongsTo('StaffAbsenceReasons', ['className' => 'FieldOption.StaffAbsenceReasons']);
		$this->addBehavior('AcademicPeriod.Period');
		$this->addBehavior('Report.ReportList');
		$this->addBehavior('Excel', [
			'excludes' => [
				'start_year',
				'end_year',
				'staff_id',
				'institution_id',
				'full_day', 
				'start_date', 
				'start_time', 
				'end_time',
				'end_date',
				'staff_absence_reason_id',
			],
			'pages' => false
		]);
	}

	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
		$requestData = json_decode($settings['process']['params']);
		$academicPeriodId = $requestData->academic_period_id;

		if (!is_null($academicPeriodId) && $academicPeriodId != 0) {
			$query->find('academicPeriod', ['academic_period_id' => $academicPeriodId]);
		}

		$query
			->select(['openemis_no' => 'Users.openemis_no', 'code' => 'Institutions.code'])
			->order([$this->aliasField('staff_id'), $this->aliasField('institution_id'), $this->aliasField('start_date')]);
	}

	// To select another one more field from the containable data
	public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields) {
		$newArray = [];
		$newArray[] = [
			'key' => 'Users.openemis_no',
			'field' => 'openemis_no',
			'type' => 'string',
			'label' => ''
		];
		$newArray[] = [
			'key' => 'StaffAbsences.staff_id',
			'field' => 'staff_id',
			'type' => 'integer',
			'label' => ''
		];
		$newArray[] = [
			'key' => 'StaffAbsences.institution_id',
			'field' => 'institution_id',
			'type' => 'string',
			'label' => ''
		];
		$newArray[] = [
			'key' => 'Institutions.code',
			'field' => 'code',
			'type' => 'string',
			'label' => ''
		];
		$newArray[] = [
			'key' => 'StaffAbsences.absences',
			'field' => 'absences',
			'type' => 'string',
			'label' => __('Absences')
		];
		$newArray[] = [
			'key' => 'StaffAbsences.staff_absence_reason_id',
			'field' => 'staff_absence_reason_id',
			'type' => 'string',
			'label' => ''
		];
		$newFields = array_merge($newArray, $fields->getArrayCopy());
		$fields->exchangeArray($newFields);
	}

	public function onExcelGetStaffAbsenceReasonId(Event $event, Entity $entity) {
		if ($entity->staff_absence_reason_id == 0) {
			return __('Unexcused');
		}
	}

	public function onExcelGetAbsences(Event $event, Entity $entity) {
		$startDate = "";
		$endDate = "";

		if (!empty($entity->start_date)) {
			$startDate = $this->formatDate($entity->start_date);
		} else {
			$startDate = $entity->start_date;
		}

		if (!empty($entity->end_date)) {
			$endDate = $this->formatDate($entity->end_date);
		} else {
			$endDate = $entity->end_date;
		}
		
		if ($entity->full_day) {
			return sprintf('%s %s (%s - %s)', __('Full'), __('Day'), $startDate, $endDate);
		} else {
			$startTime = $entity->start_time;
			$endTime = $entity->end_time;
			return sprintf('%s (%s - %s) %s (%s - %s)', __('Non Full Day'), $startDate, $endDate, __('Time'), $startTime, $endTime);
		}
	}
}
