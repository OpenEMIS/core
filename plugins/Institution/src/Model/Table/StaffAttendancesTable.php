<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\MessagesTrait;
use Cake\Utility\Inflector;
use Cake\I18n\Time;

use App\Model\Table\ControllerActionTable;

class StaffAttendancesTable extends ControllerActionTable
{
    use OptionsTrait;
    use MessagesTrait;
    private $allDayOptions = [];
    private $selectedDate = [];
    private $typeOptions = [];
    private $reasonOptions = [];
    private $_fieldOrder = ['openemis_no', 'staff_id'];
    private $_absenceData = [];
    const PRESENT = 0;

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
            ],
            'pages' => ['index']
        ]);
        $this->addBehavior('Import.ImportLink');

        $AbsenceTypesTable = TableRegistry::get('Institution.AbsenceTypes');
        $this->absenceList = $AbsenceTypesTable->getAbsenceTypeList();
        $this->absenceCodeList = $AbsenceTypesTable->getCodeList();

        $this->toggle('search', false);
        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('view', false);
        $this->toggle('remove', false);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.indexEdit'] = 'indexEdit';
        return $events;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $academicPeriodId = $this->request->query['academic_period_id'];
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $query
            ->where([$this->aliasField('institution_id') => $institutionId])
            ->distinct([$this->aliasField('staff_id')])
            ->find('academicPeriod', ['academic_period_id' => $academicPeriodId]);
    }

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $startDate = $AcademicPeriodTable->get($this->request->query['academic_period_id'])->start_date->format('Y-m-d');
        $endDate = $AcademicPeriodTable->get($this->request->query['academic_period_id'])->end_date->format('Y-m-d');
        $months = $AcademicPeriodTable->generateMonthsByDates($startDate, $endDate);
        $institutionId = $this->Session->read('Institution.Institutions.id');
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

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $newArray = [];
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
        $this->_absenceData = $this->getData($startDate, $endDate, $sheet['institutionId']);
    }

    public function onExcelRenderAttendance(Event $event, Entity $entity, array $attr)
    {
        // get the data from the temporary variable
        $absenceData = $this->_absenceData;
        $absenceCodeList = $this->absenceCodeList;
        if (isset($absenceData[$entity->staff_id][$attr['date']])) {
            $absenceObj = $absenceData[$entity->staff_id][$attr['date']];
            if (! $absenceObj['full_day']) {
                $startTimeAbsent = $absenceObj['start_time'];
                $endTimeAbsent = $absenceObj['end_time'];
                $startTime = new Time($startTimeAbsent);
                $startTimeAbsent = $startTime->format('h:i A');
                $endTime = new Time($endTimeAbsent);
                $endTimeAbsent = $endTime->format('h:i A');
                if ($absenceCodeList[$absenceObj['absence_type_id']] == 'LATE') {
                    $secondsLate = intval($endTime->toUnixString()) - intval($startTime->toUnixString());
                    $minutesLate = $secondsLate / 60;
                    $hoursLate = floor($minutesLate / 60);
                    if ($hoursLate > 0) {
                        $minutesLate = $minutesLate - ($hoursLate * 60);
                        $lateString = $hoursLate.' '.__('Hour').' '.$minutesLate.' '.__('Minute');
                    } else {
                        $lateString = $minutesLate.' '.__('Minute');
                    }
                    $timeStr = sprintf(__($absenceObj['absence_type_name']) . ' - (%s)', $lateString);
                } else {
                    $timeStr = sprintf(__('Absent') . ' - ' . $absenceObj['absence_reason']. ' (%s - %s)', $startTimeAbsent, $endTimeAbsent);
                }
                return $timeStr;
            } else {
                return sprintf('%s %s %s', __('Absent'), __('Full'), __('Day'));
            }
        } else {
            return '';
        }
    }

    public function getData($monthStartDay, $monthEndDay, $institutionId)
    {
        $StaffAbsencesTable = TableRegistry::get('Institution.StaffAbsences');
        $absenceData = $StaffAbsencesTable->find('all')
                ->contain(['StaffAbsenceReasons', 'AbsenceTypes'])
                ->where([
                    $StaffAbsencesTable->aliasField('institution_id') => $institutionId,
                    $StaffAbsencesTable->aliasField('start_date').' >= ' => $monthStartDay,
                    $StaffAbsencesTable->aliasField('end_date').' <= ' => $monthEndDay,
                ])
                ->select([
                    'staff_id' => $StaffAbsencesTable->aliasField('staff_id'),
                    'start_date' => $StaffAbsencesTable->aliasField('start_date'),
                    'end_date' => $StaffAbsencesTable->aliasField('end_date'),
                    'full_day' => $StaffAbsencesTable->aliasField('full_day'),
                    'start_time' => $StaffAbsencesTable->aliasField('start_time'),
                    'end_time' => $StaffAbsencesTable->aliasField('end_time'),
                    'absence_type_id' => $StaffAbsencesTable->aliasField('absence_type_id'),
                    'absence_type_name' => 'AbsenceTypes.name',
                    'absence_reason' => 'StaffAbsenceReasons.name'
                ])
                ->toArray();
        $absenceCheckList = [];
        foreach ($absenceData as $absenceUnit) {
            $staffId = $absenceUnit['staff_id'];
            $indexAbsenceDate = date('Y-m-d', strtotime($absenceUnit['start_date']));
            $absenceCheckList[$staffId][$indexAbsenceDate] = $absenceUnit;

            if ($absenceUnit['full_day'] && !empty($absenceUnit['end_date']) && $absenceUnit['end_date'] > $absenceUnit['start_date']) {
                $tempStartDate = date("Y-m-d", strtotime($absenceUnit['start_date']));
                $formatedLastDate = date("Y-m-d", strtotime($absenceUnit['end_date']));

                while ($tempStartDate <= $formatedLastDate) {
                    $stampTempDate = strtotime($tempStartDate);
                    $tempIndex = date('Y-m-d', $stampTempDate);

                    $absenceCheckList[$staffId][$tempIndex] = $absenceUnit;

                    $stampTempDateNew = strtotime('+1 day', $stampTempDate);
                    $tempStartDate = date("Y-m-d", $stampTempDateNew);
                }
            }
        }

        return $absenceCheckList;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $tabElements = [
            'Attendance' => [
                'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StaffAttendances'],
                'text' => __('Attendance')
            ],
            'Absence' => [
                'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StaffAbsences'],
                'text' => __('Absence')
            ]
        ];
        $tabElements = $this->controller->TabPermission->checkTabPermission($tabElements);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Attendance');

        $this->field('openemis_no', ['sort' => ['field' => 'Users.openemis_no']]);
        $this->field('staff_id', ['order' => 2, 'sort' => ['field' => 'Users.first_name']]);

        $this->field('FTE', ['visible' => false]);
        $this->field('start_date', ['visible' => false]);
        $this->field('start_year', ['visible' => false]);
        $this->field('end_date', ['visible' => false]);
        $this->field('end_year', ['visible' => false]);
        $this->field('staff_type_id', ['visible' => false]);
        $this->field('staff_status_id', ['visible' => false]);
        $this->field('institution_position_id', ['visible' => false]);
        $this->field('security_group_user_id', ['visible' => false]);
    }

    // Event: ControllerAction.Model.afterAction
    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->setFieldOrder($this->_fieldOrder);
    }

    // Function use by the mini dashboard
    public function getNumberOfStaffByAttendance($params=[])
    {
        $query = $params['query'];
        $selectedDay = $params['selectedDay'];

        // Add this condition if the selected day is all day
        if ($selectedDay == -1) {
            $dateRange = array_column($this->allDayOptions, 'date');
            // Sort the date range
            usort($dateRange, function ($a, $b) {
                $dateTimestamp1 = strtotime($a);
                $dateTimestamp2 = strtotime($b);
                return $dateTimestamp1 < $dateTimestamp2 ? -1: 1;
            });
            if (!empty($dateRange)) {
                $startDate = $dateRange[0];
                $endDate = $dateRange[count($dateRange) - 1];
                $dateRangeCondition = [
                    'StaffAbsences.end_date >=' => $startDate,
                    'StaffAbsences.start_date <=' => $endDate
                ];
            } else {
                $dateRangeCondition = ['1 = 0'];
            }
        } else {
            $dateRangeCondition = [];
        }

        $StaffAttendancesQuery = clone $query;

        $staffAbsenceArray = $StaffAttendancesQuery
            ->select([
                'absence_id' => 'StaffAbsences.id',
                'staff_id' => $this->aliasField('staff_id'),
                'absence_type' => 'StaffAbsences.absence_type_id',
                'full_day' => 'StaffAbsences.full_day'
            ])
            ->group(['staff_id', 'absence_type'])
            ->where($dateRangeCondition)
            ->toArray();

        $tempArr = [];
        foreach ($staffAbsenceArray as $key => $value) {
            $tempArr[] = [
                'absence_id' => $value->absence_id,
                'student_id' => $value->staff_id,
                'absence_type' => $value->absence_type,
                'full_day' => $value->full_day
            ];
        }
        $staffAbsenceArray = $tempArr;

        $data = [];

        foreach ($staffAbsenceArray as $key => $value) {
            if (empty($value['absence_id'])) {
                if (isset($data['Present'])) {
                    $data['Present'] = ++$data['Present'];
                } else {
                    $data['Present'] = 1;
                }
            } else {
                $typeCode = $this->absenceCodeList[$value['absence_type']];

                if ($typeCode == 'LATE') {
                    if (isset($data['Late'])) {
                        $data['Late'] = ++$data['Late'];
                    } else {
                        $data['Late'] = 1;
                    }
                    if (isset($data['Present'])) {
                        $data['Present'] = ++$data['Present'];
                    } else {
                        $data['Present'] = 1;
                    }
                } else {
                    if ($value['full_day'] == 0) {
                        if (isset($data['Present'])) {
                            $data['Present'] = ++$data['Present'];
                        } else {
                            $data['Present'] = 1;
                        }
                    }
                    if ($value['full_day'] == 1) {
                        if (isset($data['Absence'])) {
                            $data['Absence'] = ++$data['Absence'];
                        } else {
                            $data['Absence'] = 1;
                        }
                    }
                }
            }
        }
        unset($StaffAttendancesQuery);
        return $data;
    }

    // Event: ControllerAction.Model.onGetOpenemisNo
    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        $sessionPath = 'Users.staff_absences.';
        $timeError = $this->Session->read($sessionPath.$entity->staff_id.'.timeError');
        $startTimestamp = $this->Session->read($sessionPath.$entity->staff_id.'.startTimestamp');
        $endTimestamp = $this->Session->read($sessionPath.$entity->staff_id.'.endTimestamp');
        $this->Session->delete($sessionPath.$entity->staff_id.'.timeError');
        $this->Session->delete($sessionPath.$entity->staff_id.'.startTimestamp');
        $this->Session->delete($sessionPath.$entity->staff_id.'.endTimestamp');
        $html = $event->subject()->Html->link($entity->user->openemis_no, [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'StaffUser',
            'view',
            $this->paramsEncode(['id' => $entity->user->id])
        ]);
        if ($timeError) {
            $startTime = __('Must be within shift timing, from') . ' ' . date('h:i A', $startTimestamp);
            $endTime = __('to') . ' ' . date('h:i A', $endTimestamp);

            $error = $startTime . ' ' . $endTime;
            $html .= '&nbsp;<i class="fa fa-exclamation-circle fa-lg table-tooltip icon-red" data-placement="right" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="'.$error.'"></i>';
        }
        return $html;
    }

    // Event: ControllerAction.Model.onGetType
    public function onGetType(Event $event, Entity $entity)
    {
        $html = '';

        if (!is_null($this->request->query('mode')) && $this->AccessControl->check(['Institutions', 'StaffAttendances', 'indexEdit'])) {
            $Form = $event->subject()->Form;

            $institutionId = $this->Session->read('Institution.Institutions.id');
            $id = $entity->staff_id;
            $StaffAbsences = TableRegistry::get('Institution.StaffAbsences');

            $alias = Inflector::underscore($StaffAbsences->alias());
            $fieldPrefix = $StaffAbsences->Users->alias() . '.'.$alias.'.' . $id;
            $absenceCodeList = $this->absenceCodeList;
            $codeAbsenceTypeList = array_flip($absenceCodeList);
            $options = [
                'type' => 'select',
                'label' => false,
                'options' => $this->typeOptions,
                'onChange' => '$(".type_'.$id.'").hide();$("#type_'.$id.'_"+$(this).val()).show();$("#late_time__'.$id.'").hide();$(".late_time__'.$id.'_"+$(this).val()).show();'
            ];
            $displayTime = 'display:none;';
            $HtmlField = $event->subject()->HtmlField;

            $selectedPeriod = $this->request->query['academic_period_id'];
            $institutionId = $this->Session->read('Institution.Institutions.id');

            $InstitutionShift = TableRegistry::get('Institution.InstitutionShifts');
            $shiftTime = $InstitutionShift
                ->find('shiftTime', ['academic_period_id' => $selectedPeriod, 'institution_id' => $institutionId])
                ->toArray();

            if (!empty($shiftTime)) {
                $shiftStartTimeArray = [];
                foreach ($shiftTime as $key => $value) {
                    $shiftStartTimeArray[$key] = $value->start_time;
                }

                $startTime = min($shiftStartTimeArray);
            } else {
                $configTiming = $StaffAbsences->getConfigTiming();

                $startTime = $configTiming['startTime'];
            }

            $startTimestamp = strtotime($startTime);

            $attr['value'] = date('h:i A', $startTimestamp);
            $attr['default_time'] = false;
            $attr['null'] = true;
            if (empty($entity->StaffAbsences['id'])) {
                $options['value'] = self::PRESENT;
                $html .= $Form->input($fieldPrefix.".absence_type_id", $options);
            } else {
                $html .= $Form->hidden($fieldPrefix.".id", ['value' => $entity->StaffAbsences['id']]);
                $options['value'] = $entity->StaffAbsences['absence_type_id'];
                $html .= $Form->input($fieldPrefix.".absence_type_id", $options);
                $html .= $Form->hidden($fieldPrefix.".start_time", ['value' => $entity->StaffAbsences['start_time']]);
                if ($absenceCodeList[$options['value']] == 'LATE') {
                    $displayTime = '';
                    $endTime = new Time($entity->StaffAbsences['end_time']);
                    $attr['value'] = $endTime->format('h:i A');
                }
            }
            $attr['time_options']['defaultTime'] = $attr['value'];
            $attr['class'] = 'margin-top-10 no-margin-bottom';
            $attr['field'] = 'late_time';
            $attr['model'] = $fieldPrefix;
            $attr['id'] = 'late_time_'.$id;
            $attr['label'] = false;

            $time = $HtmlField->time('edit', $entity, $attr);
            $html .= '<div id="late_time__'.$id.'" class="late_time__'.$id.'_'.$codeAbsenceTypeList['LATE'].'" style="'.$displayTime.'">'.$time.'</div>';

            $html .= $Form->hidden($fieldPrefix.".institution_id", ['value' => $institutionId]);
            $html .= $Form->hidden($fieldPrefix.".staff_id", ['value' => $id]);

            $selectedDate = $this->selectedDate->format('d-m-Y');
            $html .= $Form->hidden($fieldPrefix.".full_day", ['value' => 1]);    //full day
            $html .= $Form->hidden($fieldPrefix.".start_date", ['value' => $selectedDate]);
            $html .= $Form->hidden($fieldPrefix.".end_date", ['value' => $selectedDate]);
        } else {
            $absenceTypeList = $this->absenceList;
            $absenceCodeList = $this->absenceCodeList;
            $fullDay = '';
            if (empty($entity->StaffAbsences['id'])) {
                $type = '<i class="fa fa-check"></i>';
            } else {
                $absenceTypeId = $entity->StaffAbsences['absence_type_id'];
                $type = __($absenceTypeList[$absenceTypeId]);

                if ($absenceCodeList[$absenceTypeId] != 'LATE') {
                    if ($entity->StaffAbsences['full_day'] == 1) {
                        $fullDay = ' ('. __('Full Day').')';
                    } else {
                        $fullDay = ' ('. __('Not Full Day').')';
                    }
                }
            }

            $html .= $type . $fullDay;
        }

        return $html;
    }

    // Event: ControllerAction.Model.onGetReason
    public function onGetReason(Event $event, Entity $entity)
    {
        $html = '';

        if (!is_null($this->request->query('mode')) && $this->AccessControl->check(['Institutions', 'StaffAttendances', 'indexEdit'])) {
            $Form = $event->subject()->Form;

            $id = $entity->staff_id;
            $StaffAbsences = TableRegistry::get('Institution.StaffAbsences');

            $alias = Inflector::underscore($StaffAbsences->alias());
            $fieldPrefix = $StaffAbsences->Users->alias() . '.'.$alias.'.' . $id;

            $presentDisplay = 'display: none;';
            $excusedDisplay = 'display: none;';
            $unexcusedDisplay = 'display: none;';
            $lateDisplay = 'display: none;';
            $absenceCodeList = $this->absenceCodeList;
            if (empty($entity->StaffAbsences['staff_absence_reason_id'])) {
                $reasonId = 0;
            } else {
                $reasonId = $entity->StaffAbsences['staff_absence_reason_id'];
            }

            if (empty($entity->StaffAbsences['id'])) {
                $presentDisplay = '';    // PRESENT
            } else {
                $absenceTypeId = $entity->StaffAbsences['absence_type_id'];
                foreach ($absenceCodeList as $absenceTypeCode) {
                    $codeDisplay = strtolower($absenceTypeCode).'Display';
                    if ($absenceCodeList[$absenceTypeId] == $absenceTypeCode) {
                        $$codeDisplay = '';
                        break;
                    }
                }
            }
            $codeAbsenceType = array_flip($absenceCodeList);

            foreach ($this->typeOptions as $key => $value) {
                switch ($key) {
                    case self::PRESENT:
                        $html .= '<span class="type_'.$id.'" id="type_'.$id.'_'.$key.'" style="'.$presentDisplay.'">';
                        $html .= '<i class="fa fa-minus"></i>';
                        $html .= '</span>';
                        break;
                    case $codeAbsenceType['EXCUSED']:
                        $html .= '<span class="type_'.$id.'" id="type_'.$id.'_'.$key.'" style="'.$excusedDisplay.'">';
                            $options = ['type' => 'select', 'label' => false, 'options' => $this->reasonOptions];
                            if ($reasonId != 0) {
                                $options['value'] = $reasonId;
                            }
                            $html .= $Form->input($fieldPrefix.".staff_absence_reason_id", $options);
                        $html .= '</span>';
                        break;
                    case $codeAbsenceType['UNEXCUSED']:
                        $html .= '<span class="type_'.$id.'" id="type_'.$id.'_'.$key.'" style="'.$unexcusedDisplay.'">';
                        $html .= '<i class="fa fa-minus"></i>';
                        $html .= '</span>';
                        break;
                    case $codeAbsenceType['LATE']:
                        $html .= '<span class="type_'.$id.'" id="type_'.$id.'_'.$key.'" style="'.$lateDisplay.'">';
                            $options = ['type' => 'select', 'label' => false, 'options' => $this->reasonOptions];
                            if ($reasonId != 0) {
                                $options['value'] = $reasonId;
                            }
                            $html .= $Form->input($fieldPrefix.".late_staff_absence_reason_id", $options);
                        $html .= '</span>';
                        break;
                }
            }
        } else {
            $reasonId = $entity->StaffAbsences['staff_absence_reason_id'];
            $StaffAbsenceReasons = TableRegistry::get('Institution.StaffAbsenceReasons');

            if (!empty($reasonId)) {
                $obj = $StaffAbsenceReasons->findById($reasonId)->first();
                $html .= $obj['name'];
            } else {
                $html .= '<i class="fa fa-minus"></i>';
            }
        }

        return $html;
    }

    public function onGetSunday(Event $event, Entity $entity)
    {
        return $this->getAbsenceData($event, $entity, 'sunday');
    }

    public function onGetMonday(Event $event, Entity $entity)
    {
        return $this->getAbsenceData($event, $entity, 'monday');
    }

    public function onGetTuesday(Event $event, Entity $entity)
    {
        return $this->getAbsenceData($event, $entity, 'tuesday');
    }

    public function onGetWednesday(Event $event, Entity $entity)
    {
        return $this->getAbsenceData($event, $entity, 'wednesday');
    }

    public function onGetThursday(Event $event, Entity $entity)
    {
        return $this->getAbsenceData($event, $entity, 'thursday');
    }

    public function onGetFriday(Event $event, Entity $entity)
    {
        return $this->getAbsenceData($event, $entity, 'friday');
    }

    public function onGetSaturday(Event $event, Entity $entity)
    {
        return $this->getAbsenceData($event, $entity, 'saturday');
    }

    public function getAbsenceData(Event $event, Entity $entity, $key)
    {
        $value = '<i class="fa fa-check"></i>';
        $currentDay = $this->allDayOptions[$key]['date'];

        if (isset($entity->StaffAbsences['id'])) {
            $startDate = $entity->StaffAbsences['start_date'];
            $endDate = $entity->StaffAbsences['end_date'];
            if ($currentDay >= $startDate && $currentDay <= $endDate) {
                $StaffAbsences = TableRegistry::get('Institution.StaffAbsences');
                $absenceQuery = $StaffAbsences
                    ->findById($entity->StaffAbsences['id'])
                    ->contain('StaffAbsenceReasons');
                $absenceResult = $absenceQuery->first();

                $absenceType = $this->absenceList[$entity->StaffAbsences['absence_type_id']];
                if ($absenceResult->full_day == 0) {
                    $urlLink = sprintf(__($absenceType) . ' - (%s - %s)', $absenceResult->start_time, $absenceResult->end_time);
                } else {
                    $urlLink = __($absenceType) . ' - ('.__('Full Day').')';
                }

                $StaffAbsences = TableRegistry::get('Institution.StaffAbsences');
                $value = $event->subject()->Html->link($urlLink, [
                    'plugin' => $this->controller->plugin,
                    'controller' => $this->controller->name,
                    'action' => $StaffAbsences->alias(),
                    'view',
                    $this->paramsEncode(['id' => $entity->StaffAbsences['id']])
                ]);
            }
        }

        $query = $this->find()
            ->select(['start_date' => $this->aliasField('start_date')])
            ->where([
                $this->aliasField('staff_id') => $entity->staff_id,
                $this->aliasField('institution_id') => $entity->institution_id
            ])
            ->order([$this->aliasField('start_date') => 'ASC'])
            ->first();
        $staffStartDate = $query->start_date->format('Y-m-d');

        if ($currentDay < $staffStartDate) {
            $value = '<i class="fa fa-minus"></i>';
        }

        return $value;
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // Setup period options
        $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $periodOptions = $AcademicPeriod->getYearList();

        if (empty($this->request->query['academic_period_id'])) {
            $this->request->query['academic_period_id'] = $AcademicPeriod->getCurrent();
        }

        $Staff = $this;
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $selectedPeriod = $this->request->query['academic_period_id'];

        $this->advancedSelectOptions($periodOptions, $selectedPeriod, [
            'message' => '{{label}} - ' . $this->getMessage('general.noStaff'),
            'callable' => function ($id) use ($Staff, $institutionId) {
                return $Staff
                    ->findByInstitutionId($institutionId)
                    ->find('academicPeriod', ['academic_period_id' => $id])
                    ->count();
            }
        ]);

        // To add the academic_period_id to export
        if (isset($extra['toolbarButtons']['export']['url'])) {
            $extra['toolbarButtons']['export']['url']['academic_period_id'] = $selectedPeriod;
        }

        $this->request->query['academic_period_id'] = $selectedPeriod;
        // End setup periods

        if ($selectedPeriod != 0) {
            $todayDate = date("Y-m-d");
            $this->controller->set(compact('periodOptions', 'selectedPeriod'));

            // Setup week options
            $weeks = $AcademicPeriod->getAttendanceWeeks($selectedPeriod);
            $weekOptions = [];
            $currentWeek = null;
            foreach ($weeks as $index => $dates) {
                if ($todayDate >= $dates[0]->format('Y-m-d') && $todayDate <= $dates[1]->format('Y-m-d')) {
                    $weekStr = __('Current Week') . ' %d (%s - %s)';
                    $currentWeek = $index;
                } else {
                    $weekStr = __('Week').' %d (%s - %s)';
                }
                $weekOptions[$index] = sprintf($weekStr, $index, $this->formatDate($dates[0]), $this->formatDate($dates[1]));
            }
            $academicPeriodObj = $AcademicPeriod->get($selectedPeriod);
            $startYear = $academicPeriodObj->start_year;
            $endYear = $academicPeriodObj->end_year;
            if (date("Y") >= $startYear && date("Y") <= $endYear && !is_null($currentWeek)) {
                $selectedWeek = !is_null($this->request->query('week')) ? $this->request->query('week') : $currentWeek;
            } else {
                $selectedWeek = $this->queryString('week', $weekOptions);
            }

            $weekStartDate = $weeks[$selectedWeek][0];
            $weekEndDate = $weeks[$selectedWeek][1];

            $this->advancedSelectOptions($weekOptions, $selectedWeek);
            $this->controller->set(compact('weekOptions', 'selectedWeek'));
            // end setup weeks

            // Setup day options
            $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
            $firstDayOfWeek = $ConfigItems->value('first_day_of_week');
            $daysPerWeek = $ConfigItems->value('days_per_week');
            $schooldays = [];

            for ($i=0; $i<$daysPerWeek; $i++) {
                // sunday should be '7' in order to be displayed
                $schooldays[] = 1 + ($firstDayOfWeek + 6 + $i) % 7;
            }

            $week = $weeks[$selectedWeek];
            if (is_null($this->request->query('mode'))) {
                $dayOptions = [-1 => ['value' => -1, 'text' => __('All Days')]];
            }
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
            } while ($firstDayOfWeek->lte($week[1]));

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
                    $this->selectedDate = $currentDay->next($selectedDay);
                } else {
                    $this->selectedDate = $currentDay;
                }
            } else {
                $this->selectedDate = $week;
            }
            $this->controller->set(compact('dayOptions', 'selectedDay'));
            // End setup days

            if ($selectedDay == -1) {
                $startDate = $weekStartDate;
                $endDate = $weekEndDate;
            } else {
                $startDate = $this->selectedDate;
                $endDate = $startDate;
            }

            // sort
            $sortList = ['Users.openemis_no', 'Users.first_name'];
            if (array_key_exists('sortWhitelist', $extra['options'])) {
                $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
            }
            $extra['options']['sortWhitelist'] = $sortList;

            $query
                ->find('academicPeriod', ['academic_period_id' => $selectedPeriod])
                ->find('inDateRange', ['start_date' => $startDate, 'end_date' => $endDate])
                ->select(['institution_id'])
                ->contain(['Users'])
                ->find('withAbsence', ['date' => $this->selectedDate])
                ->where([$this->aliasField('institution_id') => $institutionId])
                ->distinct();

            // POCOR-3324 Check if no sorting will be sort by users first name
            $requestQuery = $this->request->query;
            $sortable = array_key_exists('sort', $requestQuery) ? true : false;
            if (!$sortable) {
                $query->order(['Users.first_name' => 'ASC']); // no sorting request sort by Users.first_name
            }
            // end POCOR-3324

            $InstitutionArray = [];

            $queryClone = clone $query;
            $totalStaff = $queryClone->distinct([$this->aliasField('staff_id')])->count();

            $indexDashboard = 'attendance';

            $dataSet = $this->getNumberOfStaffByAttendance(['query' => $query, 'selectedDay' => $selectedDay]);
            $present = 0;
            $absent = 0;
            $late = 0;
            foreach ($dataSet as $key => $data) {
                if ($key == 'Present') {
                    $present = $data;
                } elseif ($key == 'Late') {
                    $late = $data;
                } else {
                    $absent += $data;
                }
            }

            $staffAttendanceArray = [];

            if ($selectedDay != -1) {
                $staffAttendanceArray[] = ['label' => 'No. of Staff Present', 'value' => $present];
                $staffAttendanceArray[] = ['label' => 'No. of Staff Absent', 'value' => $absent];
                $staffAttendanceArray[] = ['label' => 'No. of Staff Late', 'value' => $late];
            } else {
                $staffAttendanceArray[] = ['label' => 'No. of Staff Absent for the week', 'value' => $absent];
                $staffAttendanceArray[] = ['label' => 'No. of Staff Late for the week', 'value' => $late];
            }

            $extra['elements']['dashboard'] = [
                'name' => $indexDashboard,
                'data' => [
                    'model' => 'staff',
                    'modelCount' => $totalStaff,
                    'modelArray' => $staffAttendanceArray,
                ],
                'options' => [],
                'order' => 0
            ];

            $extra['elements']['controls'] = ['name' => 'Institution.Attendance/controls', 'data' => [], 'options' => [], 'order' => 1];

            if ($selectedDay == -1) {
                foreach ($this->allDayOptions as $key => $obj) {
                    $this->field($key);
                    $this->_fieldOrder[] = $key;
                }
            } else {
                $this->field('type', ['tableColumnClass' => 'vertical-align-top']);
                $this->field('reason', ['tableColumnClass' => 'vertical-align-top']);
                $this->_fieldOrder[] = 'type';
                $this->_fieldOrder[] = 'reason';
                $typeOptions = [self::PRESENT => __('Present')];
                $this->typeOptions = $typeOptions + $this->absenceList;

                $StaffAbsenceReasons = TableRegistry::get('Institution.StaffAbsenceReasons');
                $this->reasonOptions = $StaffAbsenceReasons->getList()->toArray();
            }
        } else {
            $query->where([$this->aliasField('staff_id') => 0]);

            $this->field('type');
            $this->field('reason');

            $this->Alert->warning('StaffAttendances.noStaff');
        }
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $resultSet, ArrayObject $extra)
    {
        $dataCount = $resultSet->count();

        // POCOR-3983 get the institution status
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $Institutions = TableRegistry::get('Institution.Institutions');
        $isActive = $Institutions->isActive($institutionId);
        // end of getting the institution status

        $btnTemplate = [
            'type' => 'button',
            'attr' => [
                'class' => 'btn btn-xs btn-default',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false
            ],
            'url' => $this->url('index')
        ];

        if ($this->AccessControl->check(['Institutions', 'StaffAttendances', 'indexEdit'])) {
            if ($this->request->query('day') != -1) { // only one day selected
                if (!is_null($this->request->query('mode'))) { // edit mode

                    // enable form, change form action to indexEdit
                    $extra['config']['form']['url'] = $this->url('indexEdit');

                    // Back button
                    $extra['toolbarButtons']['back'] = $btnTemplate;
                    $extra['toolbarButtons']['back']['attr']['title'] = __('Back');
                    $extra['toolbarButtons']['back']['label'] = '<i class="fa kd-back"></i>';
                    if ($extra['toolbarButtons']['back']['url']['mode']) {
                        unset($extra['toolbarButtons']['back']['url']['mode']);
                    }

                    // Save button, only can save if there is data
                    if ($dataCount > 0) {
                        if(empty($this->reasonOptions)) {
                            $this->Alert->warning('StaffAttendances.noReasons');
                        } else {
                            $extra['toolbarButtons']['indexEdit'] = $btnTemplate;
                            $extra['toolbarButtons']['indexEdit']['attr']['title'] = __('Save');
                            $extra['toolbarButtons']['indexEdit']['attr']['onclick'] = 'jsForm.submit();';
                            $extra['toolbarButtons']['indexEdit']['label'] = '<i class="fa kd-save"></i>';
                            $extra['toolbarButtons']['indexEdit']['url'] = '#';  
                        }
                    }

                    // unset export button
                    if (isset($extra['toolbarButtons']['export'])) {
                        unset($extra['toolbarButtons']['export']);
                    }
                } else { // not edit mode
                    // unset Back button
                    if (isset($extra['toolbarButtons']['back'])) {
                        unset($extra['toolbarButtons']['back']);
                    }

                    // Edit button, only can edit if there is data and if the institution is active
                    if ($dataCount > 0 && $isActive) {
                        $extra['toolbarButtons']['indexEdit'] = $btnTemplate;
                        $extra['toolbarButtons']['indexEdit']['attr']['title'] = __('Edit');
                        $extra['toolbarButtons']['indexEdit']['label'] = '<i class="fa kd-edit"></i>';
                        $extra['toolbarButtons']['indexEdit']['url']['mode'] = 'edit';
                    }
                }
            } else { // if user selected All Days, Edit operation will not be allowed
                // unset Edit button
                if ($extra['toolbarButtons']->offsetExists('indexEdit')) {
                    $extra['toolbarButtons']->offsetUnset('indexEdit');
                }
            }
        }
    }

    public function findWithAbsence(Query $query, array $options)
    {
        $date = $options['date'];

        $conditions = ['StaffAbsences.staff_id = StaffAttendances.staff_id'];
        if (is_array($date)) {
            $startDate = $date[0]->format('Y-m-d');
            $endDate = $date[1]->format('Y-m-d');

            $conditions['OR'] = [
                'OR' => [
                    [
                        'StaffAbsences.end_date IS NOT NULL',
                        'StaffAbsences.start_date >=' => $startDate,
                        'StaffAbsences.start_date <=' => $endDate
                    ],
                    [
                        'StaffAbsences.end_date IS NOT NULL',
                        'StaffAbsences.start_date <=' => $startDate,
                        'StaffAbsences.end_date >=' => $startDate
                    ],
                    [
                        'StaffAbsences.end_date IS NOT NULL',
                        'StaffAbsences.start_date <=' => $endDate,
                        'StaffAbsences.end_date >=' => $endDate
                    ],
                    [
                        'StaffAbsences.end_date IS NOT NULL',
                        'StaffAbsences.start_date >=' => $startDate,
                        'StaffAbsences.end_date <=' => $endDate
                    ]
                ],
                [
                    'StaffAbsences.end_date IS NULL',
                    'StaffAbsences.start_date <=' => $endDate
                ]
            ];
        } else {
            $conditions['StaffAbsences.start_date <= '] = $date->format('Y-m-d');
            $conditions['StaffAbsences.end_date >= '] = $date->format('Y-m-d');
        }
        return $query
            ->select([
                $this->aliasField('staff_id'),
                'Users.openemis_no', 'Users.first_name', 'Users.middle_name', 'Users.third_name','Users.last_name', 'Users.id',
                'StaffAbsences.id',
                'StaffAbsences.start_date',
                'StaffAbsences.end_date',
                'StaffAbsences.start_time',
                'StaffAbsences.end_time',
                'StaffAbsences.absence_type_id',
                'StaffAbsences.full_day',
                'StaffAbsences.staff_absence_reason_id'
            ])
            ->join([
                [
                    'table' => 'institution_staff_absences',
                    'alias' => 'StaffAbsences',
                    'type' => 'LEFT',
                    'conditions' => $conditions
                ]
            ]);
    }

    public function indexEdit()
    {
        if ($this->request->is(['post', 'put'])) {
            $requestQuery = $this->request->query;
            $requestData = $this->request->data;
            $StaffAbsences = TableRegistry::get('Institution.StaffAbsences');
            $alias = Inflector::underscore($StaffAbsences->alias());
            $codeAbsenceType = array_flip($this->absenceCodeList);
            $error = false;

            $configTiming = $StaffAbsences->getConfigTiming();

            $selectedPeriod = $this->request->query['academic_period_id'];
            $institutionId = $this->Session->read('Institution.Institutions.id');

            $InstitutionShift = TableRegistry::get('Institution.InstitutionShifts');
            $shiftTime = $InstitutionShift
                ->find('shiftTime', ['academic_period_id' => $selectedPeriod, 'institution_id' => $institutionId])
                ->toArray();

            if (!empty($shiftTime)) {
                $shiftStartTimeArray = [];
                $shiftEndTimeArray = [];
                foreach ($shiftTime as $key => $value) {
                    $shiftStartTimeArray[$key] = $value->start_time;
                    $shiftEndTimeArray[$key] = $value->end_time;
                }
            }

            if (array_key_exists($StaffAbsences->Users->alias(), $requestData)) {
                if (array_key_exists($alias, $requestData[$StaffAbsences->Users->alias()])) {
                    foreach ($requestData[$StaffAbsences->Users->alias()][$alias] as $key => $obj) {
                        $timeError = false;
                        $obj['academic_period_id'] = $requestQuery['academic_period_id'];
                        if ($obj['absence_type_id'] == $codeAbsenceType['UNEXCUSED']) {
                            $obj['staff_absence_reason_id'] = 0;
                        } elseif ($obj['absence_type_id'] == $codeAbsenceType['LATE']) {
                            $obj['staff_absence_reason_id'] = $obj['late_staff_absence_reason_id'];
                            $obj['full_day'] = 0;

                            $lateTime = strtotime($obj['late_time']);

                            if (!empty($shiftStartTimeArray) && !empty($shiftEndTimeArray)) {
                                $startTime = min($shiftStartTimeArray);

                                $startTimestamp = intval(min($shiftStartTimeArray)->toUnixString());
                                $endTimestamp = intval(max($shiftEndTimeArray)->toUnixString());
                            } else {
                                $startTime = $configTiming['startTime'];

                                $startTimestamp = intval($configTiming['startTime']->toUnixString());
                                $endTimestamp = intval($configTiming['endTime']->toUnixString());
                            }

                            $obj['start_time'] = $startTime->format('H:i A');
                            $endTime = $obj['late_time'];
                            $obj['end_time'] = $endTime;

                            if (($lateTime < $startTimestamp) || ($lateTime > $endTimestamp)) {
                                $key = $obj['staff_id'];
                                $timeError = true;
                                $error = true;
                                $this->Session->write($StaffAbsences->Users->alias().'.'.$alias.'.'.$key.'.timeError', true);
                                $this->Session->write($StaffAbsences->Users->alias().'.'.$alias.'.'.$key.'.startTimestamp', $startTimestamp);
                                $this->Session->write($StaffAbsences->Users->alias().'.'.$alias.'.'.$key.'.endTimestamp', $endTimestamp);
                            }
                        } elseif ($obj['absence_type_id'] == $codeAbsenceType['EXCUSED']) {
                            $startTime = !empty($shiftStartTimeArray) ? min($shiftStartTimeArray) : $configTiming['startTime'];
                            $obj['start_time'] = $startTime->format('H:i A');
                        }

                        if ($obj['absence_type_id'] == self::PRESENT) {
                            if (isset($obj['id'])) {
                                $StaffAbsences->deleteAll([
                                    $StaffAbsences->aliasField('id') => $obj['id']
                                ]);
                            }
                        } else {
                            if (!$timeError) {
                                $entity = $StaffAbsences->newEntity($obj);
                                if ($StaffAbsences->save($entity)) {
                                } else {
                                    $this->log($entity->errors(), 'debug');
                                }
                            } else {
                                $this->Alert->error('general.edit.failed', ['reset' => true]);
                            }
                        }
                    }
                }
            }
        }
        $url = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => $this->alias];
        $url = array_merge($url, $this->request->query, $this->request->pass);
        $url[0] = 'index';
        if (isset($url['mode']) && !$error) {
            unset($url['mode']);
        }

        return $this->controller->redirect($url);
    }
}
