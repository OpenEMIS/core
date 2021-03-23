<?php
namespace Report\Model\Table;

use ArrayObject;
use DateInterval;
use DatePeriod;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\I18n\Time;
use Cake\Log\Log;
use App\Model\Table\ControllerActionTable;

class StaffAttendancesTable extends ControllerActionTable
{
    private $_leaveData = [];
    private $_attendanceData = [];

    public function initialize(array $config)
    {
        $this->table('institution_staff');
        $config['Modified'] = false;
        $config['Created'] = false;
        parent::initialize($config);

        $this->belongsTo('StaffTypes', ['className' => 'Staff.StaffTypes']);
        $this->belongsTo('StaffStatuses', ['className' => 'Staff.StaffStatuses']);
        $this->belongsTo('InstitutionPositions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'institution_position_id']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'staff_id']);
        $this->addBehavior('AcademicPeriod.AcademicPeriod');
        $this->addBehavior('AcademicPeriod.Period');
        $this->belongsTo('SecurityGroupUsers', ['className' => 'Security.SecurityGroupUsers']);
        $this->addBehavior('Excel', [
            'excludes' => [
                'start_date',
                'end_date',
                'start_year',
                'end_year',
                'FTE',
                'staff_type_id',
                'staff_status_id',
                'institution_id',
                'institution_position_id',
                'security_group_user_id'
            ]
        ]);
        $this->addBehavior('Report.ReportList');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {   
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $institutionId = $requestData->institution_id;
        $startDate = $AcademicPeriodTable->get($academicPeriodId)->start_date->format('Y-m-d');
        $endDate = $AcademicPeriodTable->get($academicPeriodId)->end_date->format('Y-m-d');
        $months = $AcademicPeriodTable->generateMonthsByDates($startDate, $endDate);
        
        foreach ($months as $month) {
            $year = $month['year'];
            $sheetName = $month['month']['inString'].' '.$year;
            $monthInNumber = $month['month']['inNumber'];
            $days = $AcademicPeriodTable->generateDaysOfMonth($year, $monthInNumber, $startDate, $endDate);
            $dates = [];
            foreach ($days as $item) {
                $dates[] = $item['date'];
            }
            $monthStartDay = $dates[0];
            $monthEndDay = $dates[count($dates) - 1];

            $sheets[] = [
                'name' => $sheetName,
                'table' => $this,
                'query' => $this
                    ->find()
                    ->select(['openemis_no' => 'Users.openemis_no'])
                    ->find('InDateRange', ['start_date' => $monthStartDay, 'end_date' => $monthEndDay])
                    ,
                'month' => $monthInNumber,
                'year' => $year,
                'startDate' => $monthStartDay,
                'endDate' => $monthEndDay,
                'institutionId' => $institutionId,
                'orientation' => 'landscape'
            ];
        }
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);        
        $academicPeriodId = $requestData->academic_period_id;
        $institutionId = $requestData->institution_id;
        
		$conditions = [];
		
		if (!empty($institutionId)) {
			$query->where([$this->aliasField('institution_id') => $institutionId]);
		}

        $query
			->select([
				'institution_code' => 'Institutions.code',
				'institution_name' => 'Institutions.name',
				'position_title' =>  $query->func()->concat([
					'InstitutionPositions.position_no' => 'literal',
					" - ",
					'StaffPositionTitles.name' => 'literal'
				]),
				'identity_type' => 'IdentityTypes.name',
				'identity_number' => 'UserIdentity.number',
			])
			->leftJoin(['Institutions' => 'institutions'], [
				'Institutions.id = ' . $this->aliasfield('institution_id'),
			])
			->leftJoin(['InstitutionPositions' => 'institution_positions'], [
				'InstitutionPositions.id = ' . $this->aliasfield('institution_position_id'),
			])
			->leftJoin(['StaffPositionTitles' => 'staff_position_titles'], [
				'StaffPositionTitles.id = InstitutionPositions.staff_position_title_id',
			])
			->leftJoin(['UserNationalities' => 'user_nationalities'], [
				'UserNationalities.security_user_id = ' . $this->aliasfield('staff_id'),
			])
			->leftJoin(['Nationalities' => 'nationalities'], [
			   'Nationalities.id = UserNationalities.nationality_id',
			   'AND' => [
					'Nationalities.default = 1',
				]
			])
			->leftJoin(['IdentityTypes' => 'identity_types'], [
				'IdentityTypes.id = Nationalities.identity_type_id',
			])
			->leftJoin(['UserIdentity' => 'user_identities'], [
				'UserIdentity.security_user_id = ' . $this->aliasfield('staff_id'),
			])
            ->distinct([$this->aliasField('staff_id')])
            // ->find('academicPeriod', ['academic_period_id' => $academicPeriodId])
            ;
            $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
				return $results->map(function ($row) {
                    
					
					$StaffCustomFieldValues = TableRegistry::get('staff_custom_field_values');
					
					$customFieldData = $StaffCustomFieldValues->find()
						->select([
							'custom_field_id' => 'StaffCustomFields.id',
							'staff_custom_field_values.text_value',
							'staff_custom_field_values.number_value',
							'staff_custom_field_values.decimal_value',
							'staff_custom_field_values.textarea_value',
							'staff_custom_field_values.date_value'
						])
						->innerJoin(
							['StaffCustomFields' => 'staff_custom_fields'],
							[
								'StaffCustomFields.id = staff_custom_field_values.staff_custom_field_id'
							]
						)
						->where(['staff_custom_field_values.staff_id' => $row->staff_id])
						->toArray();
					
					foreach($customFieldData as $data) {
						if(!empty($data->text_value)) {
							$row[$data->custom_field_id] = $data->text_value;
						} 
						if(!empty($data->number_value)) {
							$row[$data->custom_field_id] = $data->number_value;
						}
						if(!empty($data->decimal_value)) {
							$row[$data->custom_field_id] = $data->decimal_value;
						}
						if(!empty($data->textarea_value)) {
							$row[$data->custom_field_id] = $data->textarea_value;
						}
						if(!empty($data->date_value)) {
							$row[$data->custom_field_id] = $data->date_value;
							
						}
						
					}
					return $row;
				});
			});
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $newArray = [];
		$newArray[] = [
            'key' => '',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];
        $newArray[] = [
            'key' => '',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];
        $newArray[] = [
            'key' => '',
            'field' => 'identity_type',
            'type' => 'string',
            'label' => __('Default Identity Type')
        ];
        $newArray[] = [
            'key' => '',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => __('Identity Number')
        ];
		$newArray[] = [
            'key' => '',
            'field' => 'position_title',
            'type' => 'string',
            'label' => __('Position Title')
        ];
        $StaffCustomFields = TableRegistry::get('staff_custom_fields');
                    
        $customFieldData = $StaffCustomFields->find()
            ->select([
                'custom_field_id' => 'staff_custom_fields.id',
                'custom_field' => 'staff_custom_fields.name'
            ])
            ->toArray();
        
        foreach($customFieldData as $data) {
            $custom_field_id = $data->custom_field_id;
            $custom_field = $data->custom_field;
            $newArray[] = [
                'key' => '',
                'field' => $custom_field_id,
                'type' => 'string',
                'label' => __($custom_field)
            ];
        }
		$newArray[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => ''
        ];
        $newFields = array_merge($newArray, $fields->getArrayCopy());
        $fields->exchangeArray($newFields);
        $sheet = $settings['sheet'];
        $year = $sheet['year'];
        $month = $sheet['month'];
        $startDate = $sheet['startDate'];
        $endDate = $sheet['endDate'];
        $AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $days = $AcademicPeriodTable->generateDaysOfMonth($year, $month, $startDate, $endDate);
        $workingDays = $AcademicPeriodTable->getWorkingDaysOfWeek();
        $dayIndex = [];
        foreach ($days as $item) {
            $dayIndex[] = $item['date'];
            if (in_array($item['weekDay'], $workingDays)) {
                $fields[] = [
                    'key' => 'AcademicPeriod.days',
                    'field' => 'attendance_field',
                    'type' => 'attendance',
                    'label' => sprintf('%s (%s)', $item['day'], __($item['weekDay'])),
                    'date' => $item['date']
                ];
            }
        }
        
        // Set the data into the temporary variable
        $this->_leaveData = $this->getLeaveData($startDate, $endDate, $sheet['institutionId']);
        $this->_attendanceData = $this->getAttendanceData($startDate, $endDate, $sheet['institutionId']);
    }

    public function onExcelRenderAttendance(Event $event, Entity $entity, array $attr)
    {
        // get the data from the temporary variable
        $leaveData = $this->_leaveData;       
        $attendanceData = $this->_attendanceData;
       
        if (isset($leaveData[$entity->staff_id][$attr['date']])) {
            $leaveObj = $leaveData[$entity->staff_id][$attr['date']]['leave'];
            if ($leaveObj->full_day) {
                return sprintf('%s %s %s', __('Absent'), __('Full'), __('Day'));
            } else {
                // maybe can remove this part. Here is just getting the time in and time out if detected that a staff is on half day leave
                if (isset($attendanceData[$entity->staff_id][$attr['date']])) {
                    $attendanceObj = $attendanceData[$entity->staff_id][$attr['date']]['attendance'];
                    $timeIn = $attendanceObj->time_in ? $attendanceObj->time_in->format('H:i:s') : '';
                    $timeOut = $attendanceObj->time_out ? ' - '.$attendanceObj->time_out->format('H:i:s') : '';
                    return sprintf('%s %s %s', __('Absent'), __('Half'), __('Day')).
                    "\r\n". $timeIn . $timeOut;
                }
                return sprintf('%s %s %s', __('Absent'), __('Half'), __('Day'));
            }
        }

        if (isset($attendanceData[$entity->staff_id][$attr['date']])) {
            $attendanceObj = $attendanceData[$entity->staff_id][$attr['date']]['attendance'];
            $timeIn = $attendanceObj->time_in ? $attendanceObj->time_in->format('H:i:s') : '';
            $timeOut = $attendanceObj->time_out ? ' - '.$attendanceObj->time_out->format('H:i:s') : '';
            return $timeIn. $timeOut;
        }

        return 'Attendance Not Marked';
    }

    public function getLeaveData($monthStartDay, $monthEndDay, $institutionId)
    {
        // getting data for staff leave
        $StaffLeave = TableRegistry::get('Institution.StaffLeave');
        $where = [
            'OR' => [
                [
                    $StaffLeave->aliasField("date_to <= '") . $monthEndDay. "'",
                    $StaffLeave->aliasField("date_from >= '") . $monthStartDay. "'"
                ],
                [
                    $StaffLeave->aliasField("date_to <= '") . $monthEndDay. "'",
                    $StaffLeave->aliasField("date_to >= '") . $monthStartDay. "'"
                ],
                [
                    $StaffLeave->aliasField("date_from <= '") . $monthEndDay. "'",
                    $StaffLeave->aliasField("date_from >= '") . $monthStartDay. "'"
                ],
                [
                    $StaffLeave->aliasField("date_from <= '") . $monthStartDay. "'",
                    $StaffLeave->aliasField("date_to >= '") . $monthEndDay. "'"
                ]
            ],
            $StaffLeave->aliasField('institution_id') => $institutionId
        ];

        $StaffLeaveArr = $StaffLeave
            ->find()
            ->where($where)
            ->toArray();

        // reformating staff leave array
        $leaveByStaffIdRecords = [];
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $workingDaysOfWeek = $AcademicPeriods->getWorkingDaysOfWeek();
        foreach ($StaffLeaveArr as $key => $value) {
            $staffId = $value->staff_id;
            $startDate = date_create($value->date_from);
            $endDate = date_create($value->date_to);
            $endDate = $endDate->modify('+1 day');
            $interval = new DateInterval('P1D');
            $datePeriod = new DatePeriod($startDate, $interval, $endDate);
            foreach ($datePeriod as $key => $date) {
                $dayText = $date->format('l');
                $dateStr = $date->format('Y-m-d');
                // to ensure that the date is within the start and end time of the excel sheet and that the date is working day of the week
                if (in_array($dayText, $workingDaysOfWeek) && $monthStartDay <= $dateStr && $monthEndDay >= $dateStr) {
                    $leaveByStaffIdRecords[$staffId][$dateStr]['leave'] = $value;
                }
            }
        }
        return $leaveByStaffIdRecords;
    }


    public function getAttendanceData($monthStartDay, $monthEndDay, $institutionId)
    {
        // getting data for staff attendance
        $StaffAttendances = TableRegistry::get('Institution.InstitutionStaffAttendances');
        $StaffAttendancesArr = $StaffAttendances
             ->find()
            ->where([
                $StaffAttendances->aliasField('institution_id') => $institutionId,
                $StaffAttendances->aliasField('date').' >= ' => $monthStartDay,
                $StaffAttendances->aliasField('date').' <= ' => $monthEndDay,
            ])
            ->toArray();

        // reformating staff attendance array
        $attendanceByStaffIdRecords = [];
        foreach ($StaffAttendancesArr as $key => $value) {
            $dateStr = $value->date->format('Y-m-d');
            $staffId = $value->staff_id;
            $attendanceByStaffIdRecords[$staffId][$dateStr]['attendance'] = $value;
        }
        return $attendanceByStaffIdRecords;
    }
}
