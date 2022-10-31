<?php
namespace Schedule\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class ScheduleIntervalsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_schedule_intervals');
        parent::initialize($config);

        $this->belongsTo('Institutions', [
            'className' => 'Institution.Institutions'
        ]);

        $this->belongsTo('AcademicPeriods', [
            'className' => 'AcademicPeriod.AcademicPeriods'
        ]);

        $this->belongsTo('Shifts', [
            'className' => 'Institution.InstitutionShifts',
            'foreignKey' => 'institution_shift_id'
        ]);

        $this->hasMany('Timeslots', [
            'className' => 'Schedule.ScheduleTimeslots', 
            'foreignKey' => 'institution_schedule_interval_id', 
            'dependent' => true, 
            'cascadeCallbacks' => true
        ]);

        $this->hasMany('Timetables', [
            'className' => 'Schedule.ScheduleTimetables', 
            'foreignKey' => 'institution_schedule_interval_id', 
            'dependent' => true, 
            'cascadeCallbacks' => true
        ]);

        
        $this->addBehavior('Restful.RestfulAccessControl', [
            'ScheduleTimetable' => ['index', 'view', 'edit']
        ]);
        $this->addBehavior('Schedule.Schedule');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $validator
            ->requirePresence('timeslots', 'create');

        return $validator;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'institution_shift_id':
                return __('Shift');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain(['Shifts.ShiftOptions']);

        if (array_key_exists('selectedAcademicPeriodOptions', $extra)) {
            $query->where([
                $this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodOptions']  
            ]);
        }

        if (array_key_exists('selectedShiftOptions', $extra) && $extra['selectedShiftOptions'] != -1) {
            $query->where([
                $this->aliasField('institution_shift_id') => $extra['selectedShiftOptions']  
            ]);
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('name');
        $this->field('institution_shift_id');
        $this->setFieldOrder(['name', 'institution_shift_id']);

        // filter options
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();

        $requestQuery = $this->request->query;
        if (isset($requestQuery) && array_key_exists('period', $requestQuery)) {
            $selectedPeriodId = $requestQuery['period'];
        } else {
            $selectedPeriodId = $this->AcademicPeriods->getCurrent();
        }
        
        $shiftOptions = $this->getShiftOptions($selectedPeriodId, true);

        if (isset($requestQuery) && array_key_exists('shift', $requestQuery)) {
            $selectedShiftId = $requestQuery['shift'];
        } else {
            $selectedShiftId = -1;
        }

        $extra['selectedShiftOptions'] = $selectedShiftId;
        $extra['selectedAcademicPeriodOptions'] = $selectedPeriodId;

        $extra['elements']['control'] = [
            'name' => 'Schedule.Intervals/controls',
            'data' => [
                'periodOptions'=> $academicPeriodOptions,
                'selectedPeriodOption'=> $extra['selectedAcademicPeriodOptions'],
                'shiftOptions' => $shiftOptions,
                'selectedShiftOption' => $selectedShiftId
            ],
            'order' => 3
        ];
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['entity' => $entity]);
        $this->field('name');
        $this->field('institution_shift_id', ['type' => 'select']);
        $this->field('intervals', [
            'type' => 'element',
            'element' => 'Schedule.Intervals/interval_timeslots'
        ]);
        $this->setFieldOrder(['academic_period_id', 'name', 'institution_shift_id', 'intervals']);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain([
                'Shifts.ShiftOptions',
                'Timeslots'
            ]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['entity' => $entity]);
        $this->field('name');
        $this->field('institution_shift_id');
        $this->field('intervals', [
            'type' => 'element',
            'element' => 'Schedule.Intervals/interval_timeslots'
        ]);

        $this->setFieldOrder(['academic_period_id', 'name', 'institution_shift_id', 'intervals']);
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        // for updating of the start/end time of the timeslots on render
        if (array_key_exists('submit', $data) && in_array($data['submit'], ['changeInterval', 'addTimeslot', 'changeShiftId', 'save']) && !empty($data['timeslots'])) {
            $institutionShiftId = $data['institution_shift_id'];
            $startTime = $this->Shifts->get($institutionShiftId)->start_time;

            $hasEmpty = false;
            foreach ($data['timeslots'] as $i => $timeslot) {
                if (!$hasEmpty) {
                    if (array_key_exists('interval', $timeslot) && !empty($timeslot['interval'])) {
                        $timeslotInterval = $timeslot['interval'];
                        $data['timeslots'][$i]['start_time_add'] = $this->formatTime($startTime);
                        $modifyString = '+' . $timeslotInterval . ' minutes';
                        $data['timeslots'][$i]['end_time_add'] = $this->formatTime($startTime->modify($modifyString));    
                    } else {
                        $hasEmpty = true;
                    }
                }
            }
        }

        // for patching the order of the timeslots based on the array index
        if (array_key_exists('timeslots', $data) && !empty($data['timeslots'])) {
            foreach ($data['timeslots'] as $i => $timeslot) {
                $data['timeslots'][$i]['order'] = $i + 1;
            }
        }

        // for adding timeslots end time validation as here will have all the informations needed to do the validations
        if (array_key_exists('submit', $data) && $data['submit'] == 'save') {
            $options['associated'] = [
                'Timeslots' => ['validate' => true]
            ];

            $institutionShiftId = $data['institution_shift_id'];
            $shiftEntity = $this->Shifts->get($institutionShiftId);
            $shiftStartTime = $shiftEntity->start_time;
            $shiftEndTime = $shiftEntity->end_time;

            $timeslotList = [];
            if (array_key_exists('timeslots', $data) && !empty($data['timeslots'])) {
                $hasEmpty = false;
                $totalInterval = 0;
                foreach ($data['timeslots'] as $i => $timeslot) {
                    if (!$hasEmpty) {
                        if (array_key_exists('interval', $timeslot) && !empty($timeslot['interval'])) {
                            $totalInterval += $timeslot['interval'];
                            $timeslotList[$timeslot['order']] = $totalInterval;
                        } else {
                            $hasEmpty = true;
                        }
                    } 

                    if ($hasEmpty) {
                        $timeslotList[$timeslot['order']] = null;
                    }
                }
            }
    
            $timeslotValidator = $this->Timeslots->validator();
            $timeslotValidator
                ->add('interval', 'checkEndTime', [
                    'rule' => function($value, $context) use ($shiftStartTime, $shiftEndTime, $timeslotList) {
                        $order = $context['data']['order'];
                        $totalInterval = $timeslotList[$order];
                        if (!is_null($totalInterval)) {
                            $intervalStartTime = clone $shiftStartTime;
                            $modifyString = '+' . $totalInterval . ' minutes';
                            $intervalEndTime = $intervalStartTime->modify($modifyString);
                            return $intervalEndTime <= $shiftEndTime;
                        } 
                        return true;
                    },
                    'on' => 'create',
                    'message' => __('Value entered exceed the end time of the shift selected.')
                ])
                ->requirePresence('institution_schedule_interval_id', false);

        } else {
            // for non-save actions so the timeslot entity can be patched
            $options['associated'] = [
                'Timeslots' => ['validate' => false]
            ];
        }
    }

    // OnGet Events
    public function onGetInstitutionShiftId(Event $event, Entity $entity)
    {
        return $entity->shift->shift_option->name;
    }

    // OnUpdate Events
    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['type'] = 'select';
            $attr['select'] = false;
            $attr['options'] = $this->AcademicPeriods->getYearList();
            $attr['default'] = $this->AcademicPeriods->getCurrent();
            $attr['onChangeReload'] = 'changeAcademicPeriod';
        } elseif ($action == 'edit') {
            $attr['type'] = 'readonly';
        }
        return $attr;
    }

    public function onUpdateFieldInstitutionShiftId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $requestData = $request->data;
            if (isset($requestData) && isset($requestData[$this->alias()]) && array_key_exists('academic_period_id', $requestData[$this->alias()])) {
                $selectedPeriodId = $requestData[$this->alias()]['academic_period_id'];
            } else {
                $selectedPeriodId = $this->AcademicPeriods->getCurrent();
            }

            $attr['type'] = 'select';
            $attr['options'] = $this->getShiftOptions($selectedPeriodId);
            $attr['onChangeReload'] = 'changeShiftId';
            return $attr;
        } elseif ($action == 'edit') {
            $attr['type'] = 'readonly';
        }
        return $attr;
    }

    // Change Events
    public function addOnAddTimeslot(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $fieldKey = 'timeslots';

        if (empty($data[$this->alias()][$fieldKey])) {
            $data[$this->alias()][$fieldKey] = [];
        }

        if ($data->offsetExists($this->alias())) {
            $data[$this->alias()][$fieldKey][] = [
                'intervals' => '',
            ];
        }

        $options['associated'] = [
            'Timeslots' => ['validate' => false]
        ];
    }

    public function addOnChangeAcademicPeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $data[$this->alias()]['institution_shift_id'] = '';
        unset($data[$this->alias()]['timeslots']);
    }

    // Get Options
    public function getShiftOptions($academicPeriodId, $allShiftOption = false, $institutionId='')
    {
        if($institutionId == '' && empty($institutionId)){
            $institutionId = $this->Session->read('Institution.Institutions.id');
        }
        
        $shiftOptions = $this->Shifts
            ->find('list', [
                'keyField' => 'id',
                'valueField' => 'name'
            ])
            ->select([
                'id' => $this->Shifts->aliasField('id'),
                'name' => 'ShiftOptions.name'
            ])
            ->contain('ShiftOptions')
            ->where([
                $this->Shifts->aliasField('academic_period_id') => $academicPeriodId,
                $this->Shifts->aliasField('Institution_id') => $institutionId
            ])
            ->toArray();

        if (!empty($shiftOptions) && $allShiftOption) {
            $shiftOptions = ['-1' => '-- ' . __('All Shifts') . ' --'] + $shiftOptions;
        }

        return $shiftOptions;
    }
    
    public function getStaffShiftOptions($academicPeriodId, $allShiftOption = false, $institutionId='')
    {
        $shiftOptions = $this->Shifts
            ->find('list', [
                'keyField' => 'id',
                'valueField' => 'name'
            ])
            ->select([
                'id' => $this->Shifts->aliasField('id'),
                'name' => 'ShiftOptions.name'
            ])
            ->contain('ShiftOptions')
            ->where([
                $this->Shifts->aliasField('academic_period_id') => $academicPeriodId,
                $this->Shifts->aliasField('Institution_id') => $institutionId
            ])
            ->toArray();

        if (!empty($shiftOptions) && $allShiftOption) {
            $shiftOptions = ['-1' => '-- ' . __('All Shifts') . ' --'] + $shiftOptions;
        }

        return $shiftOptions;
    }
}
