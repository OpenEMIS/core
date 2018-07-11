<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\MessagesTrait;
use Cake\Utility\Inflector;
use Cake\I18n\Time;

class StudentAttendancesTable extends AppTable
{
    use OptionsTrait;
    use MessagesTrait;
    private $allDayOptions = [];
    private $selectedDate = [];
    private $typeOptions = [];
    private $reasonOptions = [];
    private $_fieldOrder = ['openemis_no', 'student_id'];
    private $dataCount = null;
    private $_absenceData = [];
    const PRESENT = 0;
    private $absenceList;
    private $absenceCodeList;

    public function initialize(array $config)
    {
        $this->table('institution_class_students');
        $config['Modified'] = false;
        $config['Created'] = false;
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'student_id']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->hasMany('InstitutionClassGrades', ['className' => 'Institution.InstitutionClassGrades']);

        $this->addBehavior('AcademicPeriod.AcademicPeriod');
        $this->addBehavior('Excel', [
            'excludes' => ['status', 'education_grade_id', 'id', 'academic_period_id', 'institution_id'],
            'pages' => ['index']
        ]);
        $this->addBehavior('Import.ImportLink');
        $this->addBehavior('Institution.Calendar');
        $AbsenceTypesTable = TableRegistry::get('Institution.AbsenceTypes');
        $this->absenceList = $AbsenceTypesTable->getAbsenceTypeList();
        $this->absenceCodeList = $AbsenceTypesTable->getCodeList();
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $classId = !empty($this->request->query['class_id']) ? $this->request->query['class_id'] : 0 ;
        $query->where([$this->aliasField('institution_class_id') => $classId]);
    }

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $academicPeriodId = $this->request->query['academic_period_id'];
        $startDate = $AcademicPeriodTable->get($academicPeriodId)->start_date->format('Y-m-d');
        $endDate = $AcademicPeriodTable->get($academicPeriodId)->end_date->format('Y-m-d');
        $months = $AcademicPeriodTable->generateMonthsByDates($startDate, $endDate);
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $classId = !empty($this->request->query['class_id']) ? $this->request->query['class_id'] : 0 ;

        foreach ($months as $month) {
            $year = $month['year'];
            $sheetName = $month['month']['inString'].' '.$year;
            $monthInNumber = $month['month']['inNumber'];
            $sheets[] = [
                'name' => $sheetName,
                'table' => $this,
                'query' => $this
                    ->find()
                    ->select(['openemis_no' => 'Users.openemis_no']),
                'month' => $monthInNumber,
                'year' => $year,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'institutionId' => $institutionId,
                'classId' => $classId,
                'orientation' => 'landscape'
            ];
        }
    }

    // To select another one more field from the containable data
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
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
        $dayIndex = [];
        $workingDays = $AcademicPeriodTable->getWorkingDaysOfWeek();
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
        $startDate = $dayIndex[0];
        $endDate = $dayIndex[count($dayIndex)-1];

        // Set data into a temporary variable
        $this->_absenceData = $this->getData($startDate, $endDate, $sheet['institutionId'], $sheet['classId']);
    }

    public function onExcelRenderAttendance(Event $event, Entity $entity, array $attr)
    {
        // Get the data from the temporary variable
        $absenceData = $this->_absenceData;
        $absenceCodeList = $this->absenceCodeList;
        if (isset($absenceData[$entity->student_id][$attr['date']])) {
            $absenceObj = $absenceData[$entity->student_id][$attr['date']];
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

    public function getData($monthStartDay, $monthEndDay, $institutionId, $classId)
    {
        $StudentAbsenceTable = TableRegistry::get('Institution.InstitutionStudentAbsences');

        $absenceData = $StudentAbsenceTable->find('all')
            ->contain(['StudentAbsenceReasons', 'AbsenceTypes'])
            ->where([
                $StudentAbsenceTable->aliasField('institution_id') => $institutionId,
                $StudentAbsenceTable->aliasField('start_date').' >= ' => $monthStartDay,
                $StudentAbsenceTable->aliasField('end_date').' <= ' => $monthEndDay,
            ])
            ->innerJoin(
                ['InstitutionClassStudents' => 'institution_class_students'],
                [
                    'InstitutionClassStudents.student_id = '.$StudentAbsenceTable->aliasField('student_id'),
                    'InstitutionClassStudents.institution_class_id' => $classId
                ]
            )
            ->select([
                    'student_id' => $StudentAbsenceTable->aliasField('student_id'),
                    'start_date' => $StudentAbsenceTable->aliasField('start_date'),
                    'end_date' => $StudentAbsenceTable->aliasField('end_date'),
                    'full_day' => $StudentAbsenceTable->aliasField('full_day'),
                    'start_time' => $StudentAbsenceTable->aliasField('start_time'),
                    'end_time' => $StudentAbsenceTable->aliasField('end_time'),
                    'absence_type_id' => $StudentAbsenceTable->aliasField('absence_type_id'),
                    'absence_type_name' => 'AbsenceTypes.name',
                    'absence_reason' => 'StudentAbsenceReasons.name'
                ])
            ->toArray();
        $absenceCheckList = [];
        foreach ($absenceData as $absenceUnit) {
            $studentId = $absenceUnit['student_id'];
            $indexAbsenceDate = date('Y-m-d', strtotime($absenceUnit['start_date']));

            $absenceCheckList[$studentId][$indexAbsenceDate] = $absenceUnit;

            if ($absenceUnit['full_day'] && !empty($absenceUnit['end_date']) && $absenceUnit['end_date'] > $absenceUnit['start_date']) {
                $tempStartDate = date("Y-m-d", strtotime($absenceUnit['start_date']));
                $formatedLastDate = date("Y-m-d", strtotime($absenceUnit['end_date']));

                while ($tempStartDate <= $formatedLastDate) {
                    $stampTempDate = strtotime($tempStartDate);
                    $tempIndex = date('Y-m-d', $stampTempDate);
                    $absenceCheckList[$studentId][$tempIndex] = $absenceUnit;
                    $stampTempDateNew = strtotime('+1 day', $stampTempDate);
                    $tempStartDate = date("Y-m-d", $stampTempDateNew);
                }
            }
        }
        return $absenceCheckList;
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
        return $events;
    }

    // Event: ControllerAction.Model.beforeAction
    public function beforeAction(Event $event)
    {
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
        $tabElements = $this->controller->TabPermission->checkTabPermission($tabElements);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Attendance');

        $this->ControllerAction->field('openemis_no');
        $this->ControllerAction->field('student_id');
        $this->ControllerAction->field('institution_class_id', ['visible' => false]);
        $this->ControllerAction->field('education_grade_id', ['visible' => false]);
        $this->ControllerAction->field('academic_period_id', ['visible' => false]);
        $this->ControllerAction->field('status', ['visible' => false]);
        $this->ControllerAction->field('student_status_id', ['visible' => false]);
    }

    // Event: ControllerAction.Model.afterAction
    public function afterAction(Event $event, ArrayObject $config)
    {
        if (!is_null($this->request->query('mode'))) {
            if ($this->dataCount > 0) {
                if (empty($this->reasonOptions)) {
                    $this->Alert->warning('StudentAttendances.noReasons');
                } else {
                    $config['formButtons'] = true;
                    $config['url'] = $config['buttons']['index']['url'];
                    $config['url'][0] = 'indexEdit';
                }
            }
        }
        $this->ControllerAction->setFieldOrder($this->_fieldOrder);
    }

    // Function use by the mini dashboard
    public function getNumberOfStudentByAttendance($params=[])
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
                    'StudentAbsences.end_date >=' => $startDate,
                    'StudentAbsences.start_date <=' => $endDate
                ];
            } else {
                $dateRangeCondition = ['1 = 0'];
            }
        } else {
            $dateRangeCondition = [];
        }

        $StudentAttendancesQuery = clone $query;

        $studentAbsenceArray = $StudentAttendancesQuery
            ->select([
                'absence_id' => 'StudentAbsences.id',
                'student_id' => $this->aliasField('student_id'),
                'absence_type' => 'StudentAbsences.absence_type_id',
                'full_day' => 'StudentAbsences.full_day'
            ])
            ->group(['student_id', 'absence_type'])
            ->where($dateRangeCondition)
            ->toArray();

        $tempArr = [];
        foreach ($studentAbsenceArray as $key => $value) {
            $tempArr[] = [
                'absence_id' => $value->absence_id,
                'student_id' => $value->student_id,
                'absence_type' => $value->absence_type,
                'full_day' => $value->full_day
            ];
        }
        $studentAbsenceArray = $tempArr;

        $data = [
            'Present' => 0,
            'Late' => 0,
            'Absence' => 0
        ];

        foreach ($studentAbsenceArray as $key => $value) {
            if (empty($value['absence_id'])) {
                $data['Present']++;
            } else {
                $typeCode = $this->absenceCodeList[$value['absence_type']];

                if ($typeCode == 'LATE') {
                    $data['Late']++;
                    $data['Present']++;
                } else {
                    if ($value['full_day'] == 0) {
                        $data['Present']++;
                    } elseif ($value['full_day'] == 1) {
                        $data['Absence']++;
                    }
                }
            }
        }

        unset($StudentAttendancesQuery);
        return $data;
    }

    // Event: ControllerAction.Model.onGetOpenemisNo
    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        $sessionPath = 'Users.institution_student_absences.';
        $timeError = $this->Session->read($sessionPath.$entity->student_id.'.timeError');
        $startTimestamp = $this->Session->read($sessionPath.$entity->student_id.'.startTimestamp');
        $endTimestamp = $this->Session->read($sessionPath.$entity->student_id.'.endTimestamp');
        $this->Session->delete($sessionPath.$entity->student_id.'.timeError');
        $this->Session->delete($sessionPath.$entity->student_id.'.startTimestamp');
        $this->Session->delete($sessionPath.$entity->student_id.'.endTimestamp');
        $html = $event->subject()->Html->link($entity->user->openemis_no, [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'StudentUser',
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
        $studentId = $entity->student_id;
        $StudentTable = TableRegistry::get('Institution.Students');
        $institutionId = $this->Session->read('Institution.Institutions.id');
        // checkEnrolledInInstitution will list only enrolled student in the school
        if (!is_null($this->request->query('mode'))) {
            $Form = $event->subject()->Form;

            $institutionId = $this->Session->read('Institution.Institutions.id');
            $id = $entity->student_id;
            $StudentAbsences = TableRegistry::get('Institution.InstitutionStudentAbsences');

            $alias = Inflector::underscore($StudentAbsences->alias());
            $fieldPrefix = $StudentAbsences->Users->alias() . '.'.$alias.'.' . $id;
            $absenceCodeList = $this->absenceCodeList;
            $codeAbsenceTypeList = array_flip($absenceCodeList);
            $options = [
                'error' => true,
                'type' => 'select',
                'label' => false,
                'options' => $this->typeOptions,
                'onChange' => '$(".type_'.$id.'").hide();$("#type_'.$id.'_"+$(this).val()).show();$("#late_time__'.$id.'").hide();$(".late_time__'.$id.'_"+$(this).val()).show();',
            ];
            $displayTime = 'display:none;';
            $HtmlField = $event->subject()->HtmlField;

            $classId = $this->request->query['class_id'];

            $InstitutionShift = TableRegistry::get('Institution.InstitutionShifts');
            $shiftTime = $InstitutionShift
                ->find('shiftTime', ['institution_class_id' => $classId])
                ->first();

            $startTime = $shiftTime->start_time;
            $startTimestamp = strtotime($startTime);

            $attr['value'] = date('h:i A', $startTimestamp);
            $attr['default_time'] = false;
            $attr['null'] = true;

            if (empty($entity->StudentAbsences['id'])) {
                $options['value'] = self::PRESENT;
                $html .= $Form->input($fieldPrefix.".absence_type_id", $options);
            } else {
                $html .= $Form->hidden($fieldPrefix.".id", ['value' => $entity->StudentAbsences['id']]);
                $options['value'] = $entity->StudentAbsences['absence_type_id'];
                $html .= $Form->input($fieldPrefix.".absence_type_id", $options);
                $html .= $Form->hidden($fieldPrefix.".start_time", ['value' => $entity->StudentAbsences['start_time']]);
                if ($absenceCodeList[$options['value']] == 'LATE') {
                    $displayTime = '';
                    $endTime = new Time($entity->StudentAbsences['end_time']);
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
            $html .= $Form->hidden($fieldPrefix.".student_id", ['value' => $id]);

            $selectedDate = $this->selectedDate->format('d-m-Y');
            $html .= $Form->hidden($fieldPrefix.".full_day", ['value' => 1]);    //full day
            $html .= $Form->hidden($fieldPrefix.".start_date", ['value' => $selectedDate]);
            $html .= $Form->hidden($fieldPrefix.".end_date", ['value' => $selectedDate]);
        } else {
            if ($this->isSchoolClosed($this->selectedDate)) {
                $html = '<i style="color: #999" class="fa fa-minus"></i>';
            } else {
                $classId = $this->request->query['class_id'];
                $academicPeriodId = $this->request->query['academic_period_id'];

                if ($this->isDateMarked($classId, $academicPeriodId, $this->selectedDate)) {
                    $html = '<i class="fa fa-check"></i>';
                } else {
                    $html = '<i class="fa fa-minus"></i>';
                }

                if (!empty($entity->StudentAbsences['id'])) {
                    $absenceTypeList = $this->absenceList;
                    $absenceCodeList = $this->absenceCodeList;
                    $fullDay = '';

                    $absenceTypeId = $entity->StudentAbsences['absence_type_id'];
                    $type = __($absenceTypeList[$absenceTypeId]);

                    if ($absenceCodeList[$absenceTypeId] != 'LATE') {
                        if ($entity->StudentAbsences['full_day'] == 1) {
                            $fullDay = ' ('. __('Full Day').')';
                        } else {
                            $fullDay = ' ('. __('Not Full Day').')';
                        }
                    }

                    $html = $type . $fullDay;
                }
            }
        }

        return $html;
    }

    // Event: ControllerAction.Model.onGetReason
    public function onGetReason(Event $event, Entity $entity)
    {
        $html = '';
        $studentId = $entity->student_id;
        $StudentTable = TableRegistry::get('Institution.Students');
        $institutionId = $this->Session->read('Institution.Institutions.id');
        if (!is_null($this->request->query('mode'))) {
            $Form = $event->subject()->Form;

            $id = $entity->student_id;
            $StudentAbsences = TableRegistry::get('Institution.InstitutionStudentAbsences');

            $alias = Inflector::underscore($StudentAbsences->alias());
            $fieldPrefix = $StudentAbsences->Users->alias() . '.'.$alias.'.' . $id;

            $presentDisplay = 'display: none;';
            $excusedDisplay = 'display: none;';
            $unexcusedDisplay = 'display: none;';
            $lateDisplay = 'display: none;';
            $absenceCodeList = $this->absenceCodeList;
            if (empty($entity->StudentAbsences['student_absence_reason_id'])) {
                $reasonId = 0;
            } else {
                $reasonId = $entity->StudentAbsences['student_absence_reason_id'];
            }
            if (empty($entity->StudentAbsences['id'])) {
                $presentDisplay = '';    // PRESENT
            } else {
                $absenceTypeId = $entity->StudentAbsences['absence_type_id'];
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
                            $html .= $Form->input($fieldPrefix.".student_absence_reason_id", $options);
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
                            $html .= $Form->input($fieldPrefix.".late_student_absence_reason_id", $options);
                        $html .= '</span>';
                        break;
                }
            }
        } else {
            $reasonId = $entity->StudentAbsences['student_absence_reason_id'];
            $StudentAbsenceReasons = TableRegistry::get('Institution.StudentAbsenceReasons');

            if (!empty($reasonId)) {
                $obj = $StudentAbsenceReasons->findById($reasonId)->first();
                $html .= $obj['name'];
            } else {
                if (!empty($entity['StudentAbsences']['absence_type_id'])) {
                    if ($this->absenceCodeList[$entity['StudentAbsences']['absence_type_id']] == 'LATE') {
                        $startTime = new Time($entity['StudentAbsences']['start_time']);
                        $endTime = new Time($entity['StudentAbsences']['end_time']);
                        $secondsLate = intval($endTime->toUnixString()) - intval($startTime->toUnixString());
                        $minutesLate = $secondsLate / 60;
                        $hoursLate = floor($minutesLate / 60);
                        if ($hoursLate > 0) {
                            $minutesLate = $minutesLate - ($hoursLate * 60);
                            $lateString = $hoursLate.' '.__('Hour').' '.$minutesLate.' '.__('Minute');
                        } else {
                            $lateString = $minutesLate.' '.__('Minute');
                        }
                        $html .= $lateString;
                    } else {
                        $html .= '<i class="fa fa-minus"></i>';
                    }
                } else {
                    $html .= '<i class="fa fa-minus"></i>';
                }
            }
        }

        return $html;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        $requestQuery = $this->request->query;
        $selectedPeriod = $requestQuery['academic_period_id'];
        $selectedWeek = $requestQuery['week'];

        $label = Inflector::humanize(__($field));
        if ($field == 'openemis_no') {
            $label = __('OpenEMIS ID');
        } elseif ($field == 'student_id') {
            $label = __('Student');
        }

        if ($field == 'type') {
            $selectedDay = $requestQuery['day'];
            $date = $this->getSelectedDate($selectedPeriod, $selectedWeek, $selectedDay);
            if ($this->isSchoolClosed($date)) {
                return '<span style="color: #999">' . $label . '</span>';
            }
        } elseif ($field == 'monday' ||
            $field == 'tuesday' ||
            $field == 'wednesday' ||
            $field == 'thursday' ||
            $field == 'friday' ||
            $field == 'saturday' ||
            $field == 'sunday') {
            $date = $this->getDateFromPeriodWeekDay($selectedPeriod, $selectedWeek, $label);
            if ($this->isSchoolClosed($date)) {
                return '<span style="color: #999">' . $label . '</span>';
            }
        }

        return $label;
    }

    public function onGetSunday(Event $event, Entity $entity)
    {
        $requestQuery = $this->request->query;
        $selectedPeriod = $requestQuery['academic_period_id'];
        $selectedWeek = $requestQuery['week'];

        $date = $this->getDateFromPeriodWeekDay($selectedPeriod, $selectedWeek, 'Sunday');
        return $this->getAbsenceData($event, $entity, 'sunday', $date);
    }

    public function onGetMonday(Event $event, Entity $entity)
    {
        $requestQuery = $this->request->query;
        $selectedPeriod = $requestQuery['academic_period_id'];
        $selectedWeek = $requestQuery['week'];

        $date = $this->getDateFromPeriodWeekDay($selectedPeriod, $selectedWeek, 'Monday');
        return $this->getAbsenceData($event, $entity, 'monday', $date);
    }

    public function onGetTuesday(Event $event, Entity $entity)
    {
        $requestQuery = $this->request->query;
        $selectedPeriod = $requestQuery['academic_period_id'];
        $selectedWeek = $requestQuery['week'];

        $date = $this->getDateFromPeriodWeekDay($selectedPeriod, $selectedWeek, 'Tuesday');
        return $this->getAbsenceData($event, $entity, 'tuesday', $date);
    }

    public function onGetWednesday(Event $event, Entity $entity)
    {
        $requestQuery = $this->request->query;
        $selectedPeriod = $requestQuery['academic_period_id'];
        $selectedWeek = $requestQuery['week'];

        $date = $this->getDateFromPeriodWeekDay($selectedPeriod, $selectedWeek, 'Wednesday');
        return $this->getAbsenceData($event, $entity, 'wednesday', $date);
    }

    public function onGetThursday(Event $event, Entity $entity)
    {
        $requestQuery = $this->request->query;
        $selectedPeriod = $requestQuery['academic_period_id'];
        $selectedWeek = $requestQuery['week'];

        $date = $this->getDateFromPeriodWeekDay($selectedPeriod, $selectedWeek, 'Thursday');
        return $this->getAbsenceData($event, $entity, 'thursday', $date);
    }

    public function onGetFriday(Event $event, Entity $entity)
    {
        $requestQuery = $this->request->query;
        $selectedPeriod = $requestQuery['academic_period_id'];
        $selectedWeek = $requestQuery['week'];

        $date = $this->getDateFromPeriodWeekDay($selectedPeriod, $selectedWeek, 'Friday');
        return $this->getAbsenceData($event, $entity, 'friday', $date);
    }

    public function onGetSaturday(Event $event, Entity $entity)
    {
        $requestQuery = $this->request->query;
        $selectedPeriod = $requestQuery['academic_period_id'];
        $selectedWeek = $requestQuery['week'];

        $date = $this->getDateFromPeriodWeekDay($selectedPeriod, $selectedWeek, 'Saturday');
        return $this->getAbsenceData($event, $entity, 'saturday', $date);
    }

    public function getAbsenceData(Event $event, Entity $entity, $key, $date)
    {
        if ($this->isSchoolClosed($date)) {
            $value = '<i style="color: #999" class="fa fa-minus"></i>';
        } else {
            $classId = $this->request->query['class_id'];
            $academicPeriodId = $this->request->query['academic_period_id'];

            if ($this->isDateMarked($classId, $academicPeriodId, $date)) {
                $value = '<i class="fa fa-check"></i>';
            } else {
                $value = '<i class="fa fa-minus"></i>';
            }

            if (isset($entity->StudentAbsences['id'])) {
                $startDate = $entity->StudentAbsences['start_date'];
                $endDate = $entity->StudentAbsences['end_date'];
                $currentDay = $this->allDayOptions[$key]['date'];
                if ($currentDay >= $startDate && $currentDay <= $endDate) {
                    $InstitutionStudentAbsences = TableRegistry::get('Institution.InstitutionStudentAbsences');
                    $absenceQuery = $InstitutionStudentAbsences
                            ->findById($entity->StudentAbsences['id'])
                            ->contain('StudentAbsenceReasons');
                    $absenceResult = $absenceQuery->first();

                    $absenceType = $this->absenceList[$entity->StudentAbsences['absence_type_id']];
                    if ($absenceResult->full_day == 0) {
                        $urlLink = sprintf(__($absenceType) . ' - (%s - %s)', $absenceResult->start_time, $absenceResult->end_time);
                    } else {
                        $urlLink = __($absenceType) . ' - ('.__('Full Day').')';
                    }

                    $StudentAbsences = TableRegistry::get('Institution.StudentAbsences');
                    $value = $event->subject()->Html->link($urlLink, [
                            'plugin' => $this->controller->plugin,
                            'controller' => $this->controller->name,
                            'action' => $StudentAbsences->alias(),
                            'view',
                            $this->paramsEncode(['id' => $entity->StudentAbsences['id']])
                        ]);
                }
            }
        }

        return $value;
    }

    // Event: ControllerAction.Model.index.beforeAction
    public function indexBeforeAction(Event $event, ArrayObject $settings)
    {
        $query = $settings['query'];
        // Setup period options
        $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $periodOptions = $AcademicPeriod->getYearList();

        if (empty($this->request->query['academic_period_id'])) {
            $this->request->query['academic_period_id'] = $AcademicPeriod->getCurrent();
        }

        $Classes = TableRegistry::get('Institution.InstitutionClasses');
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $selectedPeriod = $this->queryString('academic_period_id', $periodOptions);

        $this->advancedSelectOptions($periodOptions, $selectedPeriod, [
            'message' => '{{label}} - ' . $this->getMessage('general.noClasses'),
            'callable' => function ($id) use ($Classes, $institutionId) {
                return $Classes->findByInstitutionIdAndAcademicPeriodId($institutionId, $id)->count();
            }
        ]);
        // End setup periods

        $this->request->query['academic_period_id'] = $selectedPeriod;

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

            // added to query string to find the selected weeks
            if (empty($this->request->query['week'])) {
                $this->request->query['week'] = $selectedWeek;
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

                    // POCOR-2377 adding the school closed text
                    $schoolClosed = $this->isSchoolClosed($firstDayOfWeek) ? __('School Closed') : '';
                    ;

                    $dayOptions[$firstDayOfWeek->dayOfWeek] = [
                        'value' => $firstDayOfWeek->dayOfWeek,
                        'text' => __($firstDayOfWeek->format('l')) . ' (' . $this->formatDate($firstDayOfWeek) . ') ' . $schoolClosed,
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

            // added to query string to find the selected day
            if (empty($this->request->query['day'])) {
                $this->request->query['day'] = $selectedDay;
            }

            $currentDay = $week[0]->copy();
            if ($selectedDay != -1) {
                if ($currentDay->dayOfWeek != $selectedDay) {
                    $this->selectedDate = $currentDay->next($selectedDay);
                } else {
                    $this->selectedDate = $currentDay;
                }

                if (!is_null($this->request->query('mode'))) {
                    if ($this->isSchoolClosed($this->selectedDate)) {
                        unset($this->request->query['mode']);
                    }
                }
            } else {
                $this->selectedDate = $week;
            }
            $this->controller->set(compact('dayOptions', 'selectedDay'));
            // End setup days

            // Setup class options
            $userId = $this->Auth->user('id');
            $AccessControl = $this->AccessControl;
            $classOptions = $Classes
                ->find('list')
                ->find('byAccess', ['userId' => $userId, 'accessControl' => $AccessControl, 'controller' => $this->controller]) // restrict user to see own class if permission is set
                ->where([
                    $Classes->aliasField('institution_id') => $institutionId,
                    $Classes->aliasField('academic_period_id') => $selectedPeriod
                ])
                ->order(['name'])
                ->toArray();

            $selectedClass = $this->queryString('class_id', $classOptions);
            $this->advancedSelectOptions($classOptions, $selectedClass);
            $this->controller->set(compact('classOptions', 'selectedClass'));
            // End setup classes

            // $settings['pagination'] = false; // POCOR-3324 Remove this code and add the indexBeforePaginate function

            if ($selectedDay == -1) {
                $startDate = $weekStartDate->format('Y-m-d');
                $endDate = $weekEndDate->format('Y-m-d');
            } else {
                $startDate = $this->selectedDate->format('Y-m-d');
                $endDate = $startDate;
            }

            $conditions = [];
            $conditions['OR'] = [
                'OR' => [
                    [
                        'InstitutionStudents.end_date IS NOT NULL',
                        'InstitutionStudents.start_date <=' => $startDate,
                        'InstitutionStudents.end_date >=' => $startDate
                    ],
                    [
                        'InstitutionStudents.end_date IS NOT NULL',
                        'InstitutionStudents.start_date <=' => $endDate,
                        'InstitutionStudents.end_date >=' => $endDate
                    ],
                    [
                        'InstitutionStudents.end_date IS NOT NULL',
                        'InstitutionStudents.start_date >=' => $startDate,
                        'InstitutionStudents.end_date <=' => $endDate
                    ]
                ],
                [
                    'InstitutionStudents.end_date IS NULL',
                    'InstitutionStudents.start_date <=' => $endDate
                ]
            ];

            $query
                ->contain(['Users'])
                ->find('withAbsence', ['date' => $this->selectedDate])
                ->innerJoin(['InstitutionClasses' => 'institution_classes'], [
                    'InstitutionClasses.id = '.$this->aliasField('institution_class_id')
                ])
                ->innerJoin(['InstitutionStudents' => 'institution_students'], [
                    'InstitutionStudents.academic_period_id = InstitutionClasses.academic_period_id',
                    'InstitutionStudents.institution_id = InstitutionClasses.institution_id',
                    'InstitutionStudents.education_grade_id = '. $this->aliasField('education_grade_id'),
                    'InstitutionStudents.student_id = '. $this->aliasField('student_id'),
                ])
                ->where([$this->aliasField('institution_class_id') => $selectedClass])
                ->where($conditions);

            $queryClone = clone $query;
            $totalStudent = $queryClone->distinct(['InstitutionStudents.student_id'])->count();

            $indexDashboard = 'attendance';
            $present = '-';
            $absent = '-';
            $late = '-';

            $toUpdateDashboard = false;
            if ($selectedDay == -1) {
                $findDay = $this->selectedDate[0];
                $endWeek = $this->selectedDate[1];

                do {
                    if ($this->isDateMarked($selectedClass, $selectedPeriod, $findDay)) {
                        $toUpdateDashboard = true;
                        break;
                    }
                    $findDay->addDay();
                } while ($findDay->lte($endWeek));
            } else {
                $toUpdateDashboard = $this->isDateMarked($selectedClass, $selectedPeriod, $this->selectedDate);
            }

            if ($toUpdateDashboard) {
                $dataSet = $this->getNumberOfStudentByAttendance(['query' => $query, 'selectedDay' => $selectedDay]);
                $present = $dataSet['Present'];
                $absent = $dataSet['Absence'];
                $late = $dataSet['Late'];
            }

            $studentAttendanceArray = [];

            if ($selectedDay != -1) {
                $studentAttendanceArray[] = ['label' => 'No. of Students Present', 'value' => $present];
                $studentAttendanceArray[] = ['label' => 'No. of Students Absent', 'value' => $absent];
                $studentAttendanceArray[] = ['label' => 'No. of Students Late', 'value' => $late];
            } else {
                $studentAttendanceArray[] = ['label' => 'No. of Students Absent for the week', 'value' => $absent];
                $studentAttendanceArray[] = ['label' => 'No. of Students Late for the week', 'value' => $late];
            }

            $toolbarElements[] = [
                'name' => $indexDashboard,
                'data' => [
                    'model' => 'students',
                    'modelCount' => $totalStudent,
                    'modelArray' => $studentAttendanceArray,
                ],
                'options' => []
            ];
            $toolbarElements[] = [
                'name' => 'Institution.Attendance/controls',
                'data' => [],
                'options' => []
            ];

            $this->controller->set('toolbarElements', $toolbarElements);

            if ($selectedDay == -1) {
                foreach ($this->allDayOptions as $key => $obj) {
                    $this->ControllerAction->addField($key);
                    $this->_fieldOrder[] = $key;
                }
            } else {
                $this->ControllerAction->field('type', ['tableColumnClass' => 'vertical-align-top']);
                $this->ControllerAction->field('reason', ['tableColumnClass' => 'vertical-align-top']);
                $this->_fieldOrder[] = 'type';
                $this->_fieldOrder[] = 'reason';

                $typeOptions = [self::PRESENT => __('Present')];
                $this->typeOptions = $typeOptions + $this->absenceList;

                $StudentAbsenceReasons = TableRegistry::get('Institution.StudentAbsenceReasons');
                $this->reasonOptions = $StudentAbsenceReasons->getList()->toArray();
            }
        } else {
            $settings['pagination'] = false;
            $query
                ->where([$this->aliasField('student_id') => 0]);

            $this->ControllerAction->field('type');
            $this->ControllerAction->field('reason');

            $this->Alert->warning('StudentAttendances.noClasses');
        }
    }

    // POCOR-3324 Add this one and remove the paginate = false
    public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options)
    {
        $requestQuery = $request->query;
        $selectedAcademicPeriodId = array_key_exists('academic_period_id', $requestQuery) ? $requestQuery['academic_period_id'] : null;
        $selectedClassId = array_key_exists('class_id', $requestQuery) ? $requestQuery['class_id'] : null;

        // sort
        $sortList = ['Users.openemis_no', 'Users.first_name'];
        if (array_key_exists('sortWhitelist', $options)) {
            $sortList = array_merge($options['sortWhitelist'], $sortList);
        }
        $options['sortWhitelist'] = $sortList;

        $query
            ->contain(['Users'])
            ->find('withAbsence', ['date' => $this->selectedDate])
            ->where([
                $this->aliasField('academic_period_id') => $selectedAcademicPeriodId,
                $this->aliasField('institution_class_id') => $selectedClassId,
                $this->aliasField('student_status_id') => $this->StudentStatuses->getIdByCode('CURRENT'),
            ]);

        $sortable = array_key_exists('sort', $requestQuery) ? $requestQuery['sort'] : false;
        if (!$sortable) {
            $query->order(['Users.first_name' => 'ASC']);
        }
    }
    // End POCOR-3324

    public function indexAfterAction(Event $event, $data)
    {
        $this->dataCount = $data->count();

        $this->ControllerAction->field('openemis_no', ['visible' => true, 'type' => 'string', 'sort' => ['field' => 'Users.openemis_no']]);
        $this->ControllerAction->field('student_id', ['visible' => true, 'type' => 'string', 'sort' => ['field' => 'Users.first_name']]);
    }

    public function findWithAbsence(Query $query, array $options)
    {
        $date = $options['date'];

        $conditions = ['StudentAbsences.student_id = StudentAttendances.student_id'];
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
                        'StudentAbsences.end_date <=' => $endDate
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
                $this->aliasField('student_id'),
                'Users.openemis_no', 'Users.first_name', 'Users.middle_name', 'Users.third_name','Users.last_name', 'Users.id',
                'StudentAbsences.id',
                'StudentAbsences.start_date',
                'StudentAbsences.end_date',
                'StudentAbsences.start_time',
                'StudentAbsences.end_time',
                'StudentAbsences.absence_type_id',
                'StudentAbsences.full_day',
                'StudentAbsences.student_absence_reason_id'
            ])
            ->join([
                [
                    'table' => 'institution_student_absences',
                    'alias' => 'StudentAbsences',
                    'type' => 'LEFT',
                    'conditions' => $conditions
                ]
            ]);
    }

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        if ($this->AccessControl->check(['Institutions', 'StudentAttendances', 'indexEdit'])) {
            if ($this->request->query('day') != -1) {
                if (!is_null($this->request->query('mode'))) {
                    $toolbarButtons['back'] = $buttons['back'];
                    if ($toolbarButtons['back']['url']['mode']) {
                        unset($toolbarButtons['back']['url']['mode']);
                    }
                    $toolbarButtons['back']['type'] = 'button';
                    $toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
                    $toolbarButtons['back']['attr'] = $attr;
                    $toolbarButtons['back']['attr']['title'] = __('Back');

                    if (isset($toolbarButtons['export'])) {
                        unset($toolbarButtons['export']);
                    }
                } else {
                    $toolbarButtons['back'] = $buttons['back'];
                    $toolbarButtons['back']['type'] = null;
                }

                // POCOR-2377 hide the edit button if school is closed
                if ($this->isSchoolClosed($this->selectedDate)) {
                    $toolbarButtons->offsetUnset('edit');
                }
            } else { // if user selected All Days, Edit operation will not be allowed
                if ($toolbarButtons->offsetExists('edit')) {
                    $toolbarButtons->offsetUnset('edit');
                }
            }
        }
    }

    public function indexEdit()
    {
        if ($this->request->is(['post', 'put'])) {
            $requestQuery = $this->request->query;
            $requestData = $this->request->data;

            $StudentAbsences = TableRegistry::get('Institution.InstitutionStudentAbsences');
            $alias = Inflector::underscore($StudentAbsences->alias());
            $codeAbsenceType = array_flip($this->absenceCodeList);
            $error = false;

            if (array_key_exists($StudentAbsences->Users->alias(), $requestData)) {
                if (array_key_exists($alias, $requestData[$StudentAbsences->Users->alias()])) {
                    $this->updateAttendanceRecords($requestQuery);
                    foreach ($requestData[$StudentAbsences->Users->alias()][$alias] as $key => $obj) {
                        $timeError = false;
                        $obj['academic_period_id'] = $requestQuery['academic_period_id'];

                        if ($obj['absence_type_id'] == $codeAbsenceType['UNEXCUSED']) {
                            $obj['student_absence_reason_id'] = 0;
                        } elseif ($obj['absence_type_id'] == $codeAbsenceType['LATE']) {
                            $obj['student_absence_reason_id'] = $obj['late_student_absence_reason_id'];
                            $obj['full_day'] = 0;

                            $lateTime = strtotime($obj['late_time']);

                            $classId = $this->request->query['class_id'];

                            $InstitutionShift = TableRegistry::get('Institution.InstitutionShifts');
                            $shiftTime = $InstitutionShift
                                ->find('shiftTime', ['institution_class_id' => $classId])
                                ->first();

                            $startTime = $shiftTime->start_time;
                            $endTime = $shiftTime->end_time;

                            $obj['start_time'] = $startTime;
                            $inputTime = $obj['late_time'];
                            $obj['end_time'] = $inputTime;

                            $startTimestamp = strtotime($startTime);
                            $endTimestamp = strtotime($endTime);

                            if (($lateTime < $startTimestamp) || ($lateTime > $endTimestamp)) {
                                $key = $obj['student_id'];
                                $timeError = true;
                                $error = true;
                                $this->Session->write($StudentAbsences->Users->alias().'.'.$alias.'.'.$key.'.timeError', true);
                                $this->Session->write($StudentAbsences->Users->alias().'.'.$alias.'.'.$key.'.startTimestamp', $startTimestamp);
                                $this->Session->write($StudentAbsences->Users->alias().'.'.$alias.'.'.$key.'.endTimestamp', $endTimestamp);
                            }
                        }

                        if ($obj['absence_type_id'] == self::PRESENT) {
                            if (isset($obj['id'])) {
                                $StudentAbsences->deleteAll([
                                    $StudentAbsences->aliasField('id') => $obj['id']
                                ]);
                            }
                        } else {
                            if (!$timeError) {
                                $entity = $StudentAbsences->newEntity($obj);
                                if ($StudentAbsences->save($entity)) {
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
        if (isset($url['mode'])) {
            unset($url['mode']);
        }

        return $this->controller->redirect($url);
    }

    private function updateAttendanceRecords($requestQuery)
    {
        $ClassAttendanceRecordsTable = TableRegistry::get('Institution.ClassAttendanceRecords');

        $classId = $requestQuery['class_id'];
        $academicPeriodId = $requestQuery['academic_period_id'];
        $selectedWeek = $requestQuery['week'];
        $selectedDay = $requestQuery['day'];

        $selectedDate = $this->getSelectedDate($academicPeriodId, $selectedWeek, $selectedDay);
        $year = date('Y', strtotime($selectedDate));
        $month = date('n', strtotime($selectedDate));
        $day = date('j', strtotime($selectedDate));
        $dayColumn = 'day_' . $day;

        $recordData = [
            'institution_class_id' => $classId,
            'academic_period_id' => $academicPeriodId,
            'year' => $year,
            'month' => $month,
            $dayColumn => $ClassAttendanceRecordsTable::MARKED
        ];

        $classAttendanceRecord = $ClassAttendanceRecordsTable->newEntity($recordData);
        $ClassAttendanceRecordsTable->save($classAttendanceRecord);
    }

    private function getSelectedDate($academicPeriodId, $week, $day)
    {
        $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');

        $allWeeks = $AcademicPeriod->getAttendanceWeeks($academicPeriodId);
        $selectedStartDay = $allWeeks[$week][0];

        if ($selectedStartDay->dayOfWeek != $day) {
            $selectedDate = $selectedStartDay->next($day);
        } else {
            $selectedDate = $selectedStartDay;
        }

        return $selectedDate;
    }

    private function isDateMarked($classId, $academicPeriodId, $date)
    {
        $ClassAttendanceRecordsTable = TableRegistry::get('Institution.ClassAttendanceRecords');
        return $ClassAttendanceRecordsTable->isDateMarked($classId, $academicPeriodId, $date);
    }
}
