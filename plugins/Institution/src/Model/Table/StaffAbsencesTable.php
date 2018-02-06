<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\I18n\Time;

use App\Model\Traits\OptionsTrait;
use App\Model\Table\ControllerActionTable;

class StaffAbsencesTable extends ControllerActionTable
{
    use OptionsTrait;
    private $_fieldOrder = [
        'absence_type_id', 'academic_period_id', 'staff_id',
        'full_day', 'start_date', 'end_date', 'start_time', 'end_time',
        'staff_absence_reason_id'
    ];
    private $absenceList;
    private $absenceCodeList;

    public function initialize(array $config)
    {
        $this->table('institution_staff_absences');
        parent::initialize($config);
        $this->addBehavior('Institution.Absence');

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'staff_id']);
        $this->belongsTo('StaffAbsenceReasons', ['className' => 'Institution.StaffAbsenceReasons']);
        $this->belongsTo('AbsenceTypes', ['className' => 'Institution.AbsenceTypes', 'foreignKey' =>'absence_type_id']);
        $this->addBehavior('User.AdvancedNameSearch');
        $this->addBehavior('AcademicPeriod.AcademicPeriod');
        $this->addBehavior('Excel', [
            'excludes' => [
                'start_year',
                'end_year',
                'institution_id',
                'staff_id',
                'full_day',
                'start_date',
                'start_time',
                'end_time',
                'end_date'
            ],
            'pages' => ['index']
        ]);

        // POCOR-4047 to get staff profile data
        $this->addBehavior('Institution.StaffProfile');

        $this->absenceList = $this->AbsenceTypes->getAbsenceTypeList();
        $this->absenceCodeList = $this->AbsenceTypes->getCodeList();
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $this->setValidationCode('start_date.ruleNoOverlappingAbsenceDate', 'Institution.Absences');
        $this->setValidationCode('start_date.ruleInAcademicPeriod', 'Institution.Absences');
        $this->setValidationCode('end_date.ruleInAcademicPeriod', 'Institution.Absences');
        $this->setValidationCode('end_date.ruleCompareDateReverse', 'Institution.Absences');
        $codeList = array_flip($this->absenceCodeList);
        $validator
            ->add('start_date', [
                'ruleCompareJoinDate' => [
                    'rule' => ['compareJoinDate', 'staff_id'],
                    'on' => 'create'
                ],
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []],
                    'last' => true,
                    'on' => 'create'
                ],
                'ruleNoOverlappingAbsenceDate' => [
                    'rule' => ['noOverlappingAbsenceDate', $this]
                ]
            ])
            ->add('end_date', [
                'ruleCompareJoinDate' => [
                    'rule' => ['compareJoinDate', 'staff_id'],
                    'on' => 'create'
                ],
                'ruleCompareDateReverse' => [
                    'rule' => ['compareDateReverse', 'start_date', true]
                ],
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []],
                    'last' => true,
                    'on' => 'create'
                ]
            ])
            ->requirePresence('start_time', function ($context) {
                if (array_key_exists('full_day', $context['data'])) {
                    return !$context['data']['full_day'];
                }
                return false;
            })
            ->add('start_time', [
                'ruleInInstitutionShift' => [
                    'rule' => ['inInstitutionShift', 'academic_period_id'],
                    'on' => 'create'
                ]
            ])
            ->requirePresence('end_time', function ($context) {
                if (array_key_exists('full_day', $context['data'])) {
                    return !$context['data']['full_day'];
                }
                return false;
            })
            ->add('end_time', [
                'ruleCompareAbsenceTimeReverse' => [
                    'rule' => ['compareAbsenceTimeReverse', 'start_time', true]
                ],
                'ruleInInstitutionShift' => [
                    'rule' => ['inInstitutionShift', 'academic_period_id'],
                    'on' => 'create'
                ]
            ])
            ;
        return $validator;
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.getSearchableFields'] = ['callable' => 'getSearchableFields', 'priority' => 5];
        return $events;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $query
            ->where([$this->aliasField('institution_id') => $institutionId])
            ->select(['openemis_no' => 'Users.openemis_no']);
    }

    // To select another one more field from the containable data
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
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
            'key' => 'StaffAbsences.absences',
            'field' => 'absences',
            'type' => 'string',
            'label' => __('Absences')
        ];
        $newFields = array_merge($newArray, $fields->getArrayCopy());
        $fields->exchangeArray($newFields);
    }

    public function onExcelGetStaffAbsenceReasonId(Event $event, Entity $entity)
    {
        if ($entity->staff_absence_reason_id == 0) {
            return __('Unexcused');
        }
    }

    public function onExcelGetAbsences(Event $event, Entity $entity)
    {
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

    public function onGetDate(Event $event, Entity $entity)
    {
        $startDate = $this->formatDate($entity->start_date);
        $endDate = $this->formatDate($entity->end_date);
        if ($entity->full_day == 1) {
            if (!empty($entity->end_date) && $entity->end_date > $entity->start_date) {
                $value = sprintf('%s - %s (%s)', $startDate, $endDate, __('full day'));
            } else {
                $value = sprintf('%s (%s)', $startDate, __('full day'));
            }
        } else {
            if ($this->absenceCodeList[$entity->absence_type_id] == 'LATE') {
                $endTime = $entity->end_time;
                $startTime = $entity->start_time;
                $secondsLate = intval($endTime->toUnixString()) - intval($startTime->toUnixString());
                $minutesLate = $secondsLate / 60;
                $hoursLate = floor($minutesLate / 60);
                if ($hoursLate > 0) {
                    $minutesLate = $minutesLate - ($hoursLate * 60);
                    $lateString = $hoursLate.' '.__('Hour').' '.$minutesLate.' '.__('Minute');
                } else {
                    $lateString = $minutesLate.' '.__('Minute');
                }
                $value = sprintf('%s (%s)', $startDate, $lateString);
            } else {
                $value = sprintf('%s (%s - %s)', $startDate, $this->formatTime($entity->start_time), $this->formatTime($entity->end_time));
            }
        }

        return $value;
    }

    public function onGetStaffId(Event $event, Entity $entity)
    {
        if (isset($entity->user->name_with_id)) {
            if ($this->action == 'view') {
                return $event->subject()->Html->link($entity->user->name_with_id, [
                    'plugin' => 'Institution',
                    'controller' => 'Institutions',
                    'action' => 'StaffUser',
                    'view',
                    $this->paramsEncode(['id' => $entity->user->id])
                ]);
            } else {
                return $entity->user->name_with_id;
            }
        }
    }

    public function onGetFullday(Event $event, Entity $entity)
    {
        $fullDayOptions = $this->getSelectOptions('general.yesno');
        return $fullDayOptions[$entity->full_day];
    }

    public function onGetAbsenceTypeId(Event $event, Entity $entity)
    {
        return __($entity->absence_type->name);
    }

    public function onGetStaffAbsenceReasonId(Event $event, Entity $entity)
    {
        if ($entity->staff_absence_reason_id == 0) {
            return '<i class="fa fa-minus"></i>';
        }
    }

    public function addEditBeforePatch(Event $event, $entity, $requestData, $patchOptions, ArrayObject $extra)
    {
        $absenceTypeId = $requestData[$this->alias()]['absence_type_id'];
        if ($this->absenceCodeList[$absenceTypeId] == 'LATE') {
            $requestData[$this->alias()]['end_date'] = $requestData[$this->alias()]['start_date'];
        }
    }

    public function addOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        unset($this->request->query['period']);
        unset($this->request->query['staff']);
    }

    public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->request->query['staff'] = $entity->staff_id;
        $this->request->query['full_day'] = $entity->full_day;
        $this->request->data[$this->alias()]['full_day'] = $entity->full_day;
        $this->request->data[$this->alias()]['absence_type_id'] = $entity->absence_type_id;
        $this->request->data[$this->alias()]['start_date'] = $entity->start_date;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $tabElements = [
            'Attendance' => [
                'url' => ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'StaffAttendances'],
                'text' => __('Attendance')
            ],
            'Absence' => [
                'url' => ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'StaffAbsences'],
                'text' => __('Absence')
            ]
        ];
        $tabElements = $this->controller->TabPermission->checkTabPermission($tabElements);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Absence');
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->setFieldOrder($this->_fieldOrder);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('date');
        $this->field('absence_type_id', [
            'options' => $this->absenceList
        ]);

        $this->fields['staff_id']['sort'] = ['field' => 'Users.first_name']; // POCOR-2547 adding sort
        $this->fields['full_day']['visible'] = false;
        $this->fields['start_date']['visible'] = false;
        $this->fields['end_date']['visible'] = false;
        $this->fields['start_time']['visible'] = false;
        $this->fields['end_time']['visible'] = false;
        $this->fields['comment']['visible'] = false;

        $this->_fieldOrder = ['date', 'staff_id', 'absence_type_id', 'staff_absence_reason_id'];
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $extra['auto_contain'] = false;
        $extra['auto_search'] = false;

        $query->contain(['Users', 'StaffAbsenceReasons', 'AbsenceTypes']);
        $search = $this->getSearchKey();
        if (!empty($search)) {
            // function from AdvancedNameSearchBehavior
            $query = $this->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $search]);
        }

        // POCOR-2547 Adding sortWhiteList to $extra
        $sortList = ['Users.first_name'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;

        // POCOR-2547 sort list of staff and student by name
        if (!isset($this->request->query['sort'])) {
            $query->order([$this->Users->aliasField('first_name'), $this->Users->aliasField('last_name')]);
        }
        // end POCOR-2547
    }

    public function getSearchableFields(Event $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'staff_id';
        // $searchableFields[] = 'absence_type_id';
        // $searchableFields[] = 'staff_absence_reason_id';
        // 'date', 'staff_id', 'absence_type_id', 'staff_absence_reason_id'
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // Academic period not in use in view page
        foreach ($this->_fieldOrder as $key => $value) {
            if ($value == 'academic_period_id') {
                unset($this->_fieldOrder[$key]);
            }
        }
        $this->setFieldOrder($this->_fieldOrder);

        $absenceTypeOptions = $this->absenceList;
        $this->field('absence_type_id', [
            'options' => $this->absenceList
        ]);

        if ($entity->full_day == 1) {
            $this->fields['start_time']['visible'] = false;
            $this->fields['end_time']['visible'] = false;
        }
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        list($periodOptions, $selectedPeriod, $newPeriodOptions) = array_values($this->_getSelectOptions());
        $this->field('academic_period_id', [
            'options' => $newPeriodOptions
        ]);
        $this->field('staff_id', ['type' => 'select']);
        $absenceTypeOptions = $this->absenceList;
        $this->field('absence_type_id', [
            'select' => false,
            'options' => $this->absenceList
        ]);
        $fullDayOptions = $this->getSelectOptions('general.yesno');
        $this->field('full_day', [
            'options' => $fullDayOptions
        ]);
        // Start Date and End Date
        if ($this->action == 'add') {
            $institutionId = $this->Session->read('Institution.Institutions.id');

            $InstitutionShift = TableRegistry::get('Institution.InstitutionShifts');
            $shiftTime = $InstitutionShift
                ->find('shiftTime', ['academic_period_id' => $selectedPeriod, 'institution_id' => $institutionId])
                ->toArray();

            if (empty($shiftTime)) {
                $this->Alert->warning($this->aliasField('noShift'));
            }

            $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $startDate = $AcademicPeriod->get($selectedPeriod)->start_date;
            $endDate = $AcademicPeriod->get($selectedPeriod)->end_date;

            $dateAttr = ['startDate' => Time::now(), 'endDate' => Time::now()];
            if (array_key_exists($this->alias(), $this->request->data)) {
                if (array_key_exists('staff_id', $this->request->data[$this->alias()])) {
                    $StaffTable = TableRegistry::get('Institution.Staff');
                    $staffRecord = $StaffTable->find()->where([
                            $StaffTable->aliasField('staff_id') => $this->request->data[$this->alias()]['staff_id'],
                            $StaffTable->aliasField('end_date').' IS NULL'
                        ])
                        ->first();

                    if (empty($staffRecord)) {
                        $staffRecord = $StaffTable->find()
                            ->where([
                                $StaffTable->aliasField('staff_id') => $this->request->data[$this->alias()]['staff_id'],
                            ])
                            ->order([$StaffTable->aliasField('end_date')])
                            ->first();
                    }
                    if (!empty($staffRecord)) {
                        $dateAttr['startDate'] = $staffRecord->start_date;
                        $dateAttr['endDate'] = $staffRecord->end_date;
                    }
                }
            }

            $this->field('start_date', $dateAttr);
            $this->field('end_date', $dateAttr);

            // To put restiction on the calendar date field
            $this->fields['start_date']['date_options']['startDate'] = $startDate->format('d-m-Y');
            $this->fields['start_date']['date_options']['endDate'] = $endDate->format('d-m-Y');
            $this->fields['end_date']['date_options']['startDate'] = $startDate->format('d-m-Y');
            $this->fields['end_date']['date_options']['endDate'] = $endDate->format('d-m-Y');

        // Malcolm discussed with Umairah and Thed - will revisit this when default date of htmlhelper is capable of setting 'defaultViewDate' ($entity->start_date = $todayDate; was: causing validation error to disappear)
            // $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            // $startDate = $AcademicPeriod->get($selectedPeriod)->start_date;
            // $endDate = $AcademicPeriod->get($selectedPeriod)->end_date;

            // $this->field('start_date', [
            // 	'date_options' => ['startDate' => $startDate->format('d-m-Y'), 'endDate' => $endDate->format('d-m-Y')]
            // ]);
            // $this->field('end_date', [
            // 	'date_options' => ['startDate' => $startDate->format('d-m-Y'), 'endDate' => $endDate->format('d-m-Y')]
            // ]);

            // $todayDate = date("Y-m-d");
            // if ($todayDate >= $startDate->format('Y-m-d') && $todayDate <= $endDate->format('Y-m-d')) {
            // 	$entity->start_date = $todayDate;
            // 	$entity->end_date = $todayDate;
            // } else {
            // 	$entity->start_date = $startDate->format('Y-m-d');
            // 	$entity->end_date = $startDate->format('Y-m-d');
            // }
        } elseif ($this->action == 'edit') {
            $this->field('start_date', ['value' => date('Y-m-d', strtotime($entity->start_date))]);
            $this->field('end_date', ['value' => date('Y-m-d', strtotime($entity->end_date))]);
        }
        // End
        $this->field('start_time', ['type' => 'time', 'attr' => ['value' => date('h:i A', strtotime($entity->start_time))]]);
        $this->field('end_time', ['type' => 'time', 'attr' => ['value' => date('h:i A', strtotime($entity->end_time))]]);
        $this->field('staff_absence_reason_id', ['type' => 'select']);
    }

    public function onUpdateFieldStartDate(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $startDate = $attr['startDate'];
            $endDate = $attr['endDate'];
            $attr['default_date'] = Time::now()->format('d-m-Y');
            $attr['date_options'] = ['startDate' => $startDate->format('d-m-Y')];
            if (!empty($endDate)) {
                $attr['date_options']['endDate'] = $endDate->format('d-m-Y');
            }
        }

        if ($action == 'edit') {
            $attr['type'] = 'readonly';
            $attr['attr']['value'] = date('d-m-Y', strtotime($attr['value']));
        }

        return $attr;
    }

    public function onUpdateFieldEndDate(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $selectedAbsenceType = $request->data[$this->alias()]['absence_type_id'];
            if (array_key_exists($selectedAbsenceType, $this->absenceCodeList) && $this->absenceCodeList[$selectedAbsenceType] == 'LATE') {
                $attr['type'] = 'hidden';
            }
        }

        if ($action == 'edit') {
            $attr['type'] = 'readonly';
            $attr['attr']['value'] = date('d-m-Y', strtotime($attr['value']));
        }

        return $attr;
    }

    public function onUpdateFieldStartTime(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            $attr['type'] = 'readonly';
        }
        return $attr;
    }

    public function onUpdateFieldEndTime(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            $attr['type'] = 'readonly';
        }
        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $attr['select'] = false;
            $attr['onChangeReload'] = 'changePeriod';
        } elseif ($action == 'view' || $action == 'edit') {
            $attr['visible'] = false;
        }

        return $attr;
    }

    public function onUpdateFieldStaffId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $Staff = TableRegistry::get('Institution.Staff');

            $institutionId = $this->Session->read('Institution.Institutions.id');
            $periodOptions = $AcademicPeriodTable->getYearList(['isEditable' => true]);
            $selectedPeriod = $this->queryString('period', $periodOptions);
            $startDate = $AcademicPeriodTable->get($selectedPeriod)->start_date;
            $endDate = $AcademicPeriodTable->get($selectedPeriod)->end_date;
            $activeStaffOptions = $Staff
                ->find()
                ->where([
                    $Staff->aliasField('institution_id') => $institutionId
                ])
                ->find('InDateRange', ['start_date' => $startDate, 'end_date' => $endDate])
                ->contain(['Users'])
                ->order(['Users.first_name', 'Users.last_name']) // POCOR-2547 sort list of staff and student by name
                ->find('list', ['keyField' => 'staff_id', 'valueField' => 'staff_name']);

            $activeStaffOptionsClone = clone $activeStaffOptions;
            $inactiveStaffOptions = $Staff->find()
                ->where([$Staff->aliasField('institution_id').' = '.$institutionId])
                ->where([$Staff->aliasField('id').' NOT IN ' => $activeStaffOptionsClone->select(['id'])])
                ->contain(['Users'])
                ->find('list', ['keyField' => 'staff_id', 'valueField' => 'staff_name'])
                ->order(['Users.first_name', 'Users.last_name']) // POCOR-2547 sort list of staff and student by name
                ->toArray();

            $activeStaffOptions = $activeStaffOptions->toArray();
            $newActiveStaffOptions = [];
            foreach ($activeStaffOptions as $key => $value) {
                $newActiveStaffOptions[$key] = [
                    'value' => $key,
                    'text' => $value
                ];
            }

            $newInactiveStaffOptions = [];
            foreach ($inactiveStaffOptions as $inactiveKey => $inactiveValue) {
                if (!array_key_exists($inactiveKey, $activeStaffOptions)) {
                    $newInactiveStaffOptions[$inactiveKey] = [
                        'value' => $inactiveKey,
                        'text' => $inactiveValue,
                        'disabled'
                    ];
                }
            }

            $staffOptions = [__('Active Staff') => $newActiveStaffOptions, __('Inactive Staff') => $newInactiveStaffOptions];
            $attr['options'] = $staffOptions;
            $attr['onChangeReload'] = 'changeStaff';
        } elseif ($action == 'edit') {
            $Users = TableRegistry::get('User.Users');
            $selectedStaff = $request->query('staff');

            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $Users->get($selectedStaff)->name_with_id;
        }

        return $attr;
    }

    public function onUpdateFieldFullDay(Event $event, array $attr, $action, $request)
    {
        $fullDayOptions = $attr['options'];
        $selectedFullDay = isset($request->data[$this->alias()]['full_day']) ? $request->data[$this->alias()]['full_day'] : 1;
        $this->advancedSelectOptions($fullDayOptions, $selectedFullDay);

        if ($selectedFullDay == 1) {
            $this->fields['start_time']['visible'] = false;
            $this->fields['end_time']['visible'] = false;
        } else {
            $this->fields['start_time']['visible'] = true;
            $this->fields['end_time']['visible'] = true;

            // to on the mandatory field asterick, using timepicker_input.ctp
            // timepicker_input.ctp, have the form helper error message, turn off the form helper error message.
            $this->fields['start_time']['null'] = false;
            $this->fields['end_time']['null'] = false;
        }

        if ($action == 'add') {
            $selectedAbsenceType = $request->data[$this->alias()]['absence_type_id'];
            if (array_key_exists($selectedAbsenceType, $this->absenceCodeList) && $this->absenceCodeList[$selectedAbsenceType] == 'LATE') {
                $attr['type'] = 'hidden';
                $attr['attr']['value'] = 0;
                $this->fields['start_time']['visible'] = true;
                $this->fields['end_time']['visible'] = true;
                $request->data[$this->alias()]['full_day'] = 0;
            }
        }

        if ($action == 'edit') {
            $attr['type'] = 'readonly';
            if ($this->request->query['full_day']) {
                $attr['attr']['value'] = __('Yes');
            } else {
                $attr['attr']['value'] = __('No');
            }
        }

        $attr['select'] = false;
        $attr['options'] = $fullDayOptions;
        $attr['onChangeReload'] = 'changeFullDay';

        return $attr;
    }

    public function onUpdateFieldAbsenceTypeId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            foreach ($attr['options'] as $key => $value) {
                $absenceTypeOptions[$key] = __($value);
            }
            if (!isset($request->data[$this->alias()]['absence_type_id'])) {
                $request->data[$this->alias()]['absence_type_id'] = key($absenceTypeOptions);
            }
            $selectedAbsenceType = $request->data[$this->alias()]['absence_type_id'];

            $attr['options'] = $absenceTypeOptions;
            $attr['default'] = $selectedAbsenceType;
            $attr['onChangeReload'] = 'changeAbsenceType';
        }

        if ($action == 'edit') {
            $attr['type'] = 'readonly';
        }

        return $attr;
    }

    public function onUpdateFieldStaffAbsenceReasonId(Event $event, array $attr, $action, $request)
    {
        $selectedAbsenceType = $request->data[$this->alias()]['absence_type_id'];
        if (!empty($selectedAbsenceType)) {
            $absenceType = $this->absenceCodeList[$selectedAbsenceType];
            if ($absenceType == 'UNEXCUSED') {
                $attr['type'] = 'hidden';
                $attr['attr']['value'] = 0;
            }
        }

        return $attr;
    }

    public function addEditOnChangeFullDay(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['full_day']);
        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('full_day', $request->data[$this->alias()])) {
                    $request->query['full_day'] = $request->data[$this->alias()]['full_day'];
                    // full day == 1, not full day == 0
                    if (!$request->data[$this->alias()]['full_day']) {
                        $selectedPeriod = $this->request->data[$this->alias()]['academic_period_id'];
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

                            $startTime = min($shiftStartTimeArray);
                            $endTime = max($shiftEndTimeArray);
                        } else {
                            $configTiming = $this->getConfigTiming();

                            $startTime = $configTiming['startTime'];
                            $endTime = $configTiming['endTime'];
                        }

                        $entity->start_time = date('h:i A', strtotime($startTime));
                        $entity->end_time = date('h:i A', strtotime($endTime));
                    }
                }
            }
        }
    }

    public function addEditOnChangeAbsenceType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['absence_type_id']);
        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('absence_type_id', $request->data[$this->alias()])) {
                    $selectedAbsenceType = $request->data[$this->alias()]['absence_type_id'];
                    $request->query['absence_type_id'] = $selectedAbsenceType;
                    if (array_key_exists($selectedAbsenceType, $this->absenceCodeList) && $this->absenceCodeList[$selectedAbsenceType] == 'LATE') {
                        $selectedPeriod = $this->request->data[$this->alias()]['academic_period_id'];
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

                            $startTime = min($shiftStartTimeArray);
                            $endTime = max($shiftEndTimeArray);
                        } else {
                            $configTiming = $this->getConfigTiming();

                            $startTime = $configTiming['startTime'];
                            $endTime = $configTiming['endTime'];
                        }

                        $entity->start_time = date('h:i A', strtotime($startTime));
                        $entity->end_time = date('h:i A', strtotime($endTime));
                    }
                }
            }
        }
    }

    public function addEditOnChangePeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['period']);
        unset($request->query['staff']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
                    $selectedPeriod = $request->data[$this->alias()]['academic_period_id'];
                    $request->query['period'] = $selectedPeriod;
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

                        $startTime = min($shiftStartTimeArray);
                        $endTime = max($shiftEndTimeArray);
                    } else {
                        $configTiming = $this->getConfigTiming();

                        $startTime = $configTiming['startTime'];
                        $endTime = $configTiming['endTime'];
                    }

                    $entity->start_time = date('h:i A', strtotime($startTime));
                    $entity->end_time = date('h:i A', strtotime($endTime));

                    $data[$this->alias()]['start_time'] = $entity->start_time;
                    $data[$this->alias()]['end_time'] = $entity->end_time;
                }

                $data[$this->alias()]['staff_id'] = '';
            }
        }
    }

    public function addEditOnChangeStaff(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['staff']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('staff_id', $request->data[$this->alias()])) {
                    $selectedStaff = $request->data[$this->alias()]['staff_id'];
                    $request->query['staff'] = $selectedStaff;
                }
            }
        }
    }

    // to get the default timing from the system config.
    public function getConfigTiming()
    {
        $ConfigItems = TableRegistry::get('Configuration.configItems');

        $configStartTime = $ConfigItems->value('start_time');
        $hourPerDay = $ConfigItems->value('hours_per_day');

        $endTime = new time($configStartTime);
        $endTime->addHour($hourPerDay);

        $configTiming = [];
        $configTiming['startTime'] = new time($configStartTime);
        $configTiming['endTime'] = $endTime;

        return $configTiming;
    }

    public function _getSelectOptions()
    {
        //Return all required options and their key
        $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $Staffs = TableRegistry::get('Institution.Staff');

        $institutionId = $this->Session->read('Institution.Institutions.id');

        // Academic Period
        $periodOptionsData = $AcademicPeriod->getList(['isEditable'=>true]);
        $periodOptions = $periodOptionsData[key($periodOptionsData)];
        if (is_null($this->request->query('period'))) {
            $this->request->query['period'] = $AcademicPeriod->getCurrent();
        }
        $selectedPeriod = $this->queryString('period', $periodOptions);

        // count staff on the academic period, if its empty the period will be disabled.
        $newPeriodOptions = [];
        foreach ($periodOptions as $key => $value) {
            $startDate = $AcademicPeriod->get($key)->start_date;
            $endDate = $AcademicPeriod->get($key)->end_date;

            $activeStaff = $Staffs
                ->find()
                ->where([$Staffs->aliasField('institution_id') => $institutionId])
                ->find('InDateRange', ['start_date' => $startDate, 'end_date' => $endDate])
                ->count();

            $newPeriodOptions[$key] = [
                'value' => $key,
                'text' => $value
            ];

            if ($key == $selectedPeriod) {
                $newPeriodOptions[$key] = [
                    'value' => $key,
                    'text' => $value,
                    'selected'
                ];
            }

            if ($activeStaff == 0) {
                $newPeriodOptions[$key] = [
                    'value' => $key,
                    'text' => $value,
                    'disabled'
                ];
            }
        }
        return compact('periodOptions', 'selectedPeriod', 'newPeriodOptions');
    }
}
