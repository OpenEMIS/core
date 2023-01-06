<?php
namespace Schedule\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Datasource\ResultSetInterface;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class ScheduleTimetablesTable extends ControllerActionTable
{
    const DRAFT = 1;
    const PUBLISHED = 2;
    const DEFAULT = -1;
    private $_status = [];

    public function initialize(array $config)
    {
        $this->table('institution_schedule_timetables');
        parent::initialize($config);

        $this->belongsTo('Institutions', [
            'className' => 'Institution.Institutions'
        ]);

        $this->belongsTo('AcademicPeriods', [
            'className' => 'AcademicPeriod.AcademicPeriods'
        ]);

        $this->belongsTo('InstitutionClasses', [
            'className' => 'Institution.InstitutionClasses', 
            'foreignKey' => 'institution_class_id'
        ]);

        $this->belongsTo('ScheduleIntervals', [
            'className' => 'Schedule.ScheduleIntervals', 
            'foreignKey' => 'institution_schedule_interval_id'
        ]);

        $this->belongsTo('ScheduleTerms', [
            'className' => 'Schedule.ScheduleTerms', 
            'foreignKey' => 'institution_schedule_term_id'
        ]);

        $this->hasMany('Lessons', [
            'className' => 'Schedule.ScheduleLessons', 
            'foreignKey' => 'institution_schedule_timetable_id', 
            'dependent' => true, 
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('Schedule.Schedule');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'ScheduleTimetable' => ['index', 'view', 'edit']
        ]);

        // $this->toggle('edit', false);

        $this->_status = [
            self::DRAFT => __('Draft'),
            self::PUBLISHED => __('Published')
        ];
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->add('status', 'checkExistPublish', [
                'rule' => function ($value, $context) {
                    $TimetableTable = $context['providers']['table'];
                    return !$TimetableTable->exists([
                        'institution_class_id' => $context['data']['institution_class_id'],
                        'academic_period_id' => $context['data']['academic_period_id'],
                        'institution_schedule_term_id' => $context['data']['institution_schedule_term_id'],
                        'status' => $TimetableTable::PUBLISHED
                    ]);
                },
                'on' => function($context) {
                    $TimetableTable = $context['providers']['table'];
                    $status = $context['data']['status'];
                    return $status == $TimetableTable::PUBLISHED;
                },
                'message' => __('There is existing published timetable for the class.')
            ]);
        return $validator;
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.onGetFormButtons'] = 'onGetFormButtons';
        return $events;
    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
        if ($this->action == 'add') {
            $originalButtons = $buttons->getArrayCopy();
            $startSchedulingButton = [
                [
                    'name' => '<i class="fa kd-header-row"></i>' . __('Start Scheduling'),
                    'attr' => [
                        'class' => 'btn btn-default',
                        'name' => 'submit',
                        'value' => 'saveSchedule',
                        'div' => false
                    ]
                ]
            ];

            array_splice($originalButtons, 1, 0, $startSchedulingButton);
            $buttons->exchangeArray($originalButtons);
        }
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'institution_schedule_term_id':
                return __('Term');
            case 'institution_class_id':
                return __('Class');
            case 'institution_schedule_interval_id':
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain(['ScheduleIntervals.Shifts.ShiftOptions']);

        // academic_period_id filter
        if (array_key_exists('selectedAcademicPeriodOptions', $extra)) {
            $query->where([
                $this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodOptions']  
            ]);
        }

        // institution_schedule_term_id filter
        if (array_key_exists('selectedTermOptions', $extra) && $extra['selectedTermOptions'] != self::DEFAULT) {
            $query->where([
                $this->aliasField('institution_schedule_term_id') => $extra['selectedTermOptions']  
            ]);
        }
        
        // education_grade_id filter
        if (array_key_exists('selectedGradeOptions', $extra) && $extra['selectedGradeOptions'] != self::DEFAULT) {
            $educationGradeId = $extra['selectedGradeOptions'];
            $query
                ->matching('InstitutionClasses.ClassGrades', function (Query $q) use ($educationGradeId) {
                    return $q->where(['ClassGrades.education_grade_id' => $educationGradeId]);
                });
        }

        // status filter
        if (array_key_exists('selectedStatusOptions', $extra) && $extra['selectedStatusOptions'] != self::DEFAULT) {
            $query->where([
                $this->aliasField('status') => $extra['selectedStatusOptions']  
            ]);
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('status');
        $this->field('institution_schedule_term_id', ['visible' => false]);
        $this->field('name');
        $this->field('institution_class_id');
        $this->field('shift');
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('institution_schedule_interval_id');
        $this->setFieldOrder(['status', 'institution_schedule_term_id', 'name', 'institution_class_id', 'shift']);

        // filter options
        $requestQuery = $this->request->query;

        // academic_period_id filter
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();

        if (isset($requestQuery) && array_key_exists('period', $requestQuery)) {
            $selectedPeriodId = $requestQuery['period'];
        } else {
            $selectedPeriodId = $this->AcademicPeriods->getCurrent();
        }

        $extra['selectedAcademicPeriodOptions'] = $selectedPeriodId;
        // academic_period_id filter - END
        
        // institution_schedule_term_id filter
        $termOptions = $this->getTermOptions($extra['selectedAcademicPeriodOptions'], true);

        if (isset($requestQuery) && array_key_exists('term', $requestQuery)) {
            $selectedTerm = $requestQuery['term'];
        } else {
            $selectedTerm = self::DEFAULT;
        }

        $extra['selectedTermOptions'] = $selectedTerm;
        // institution_schedule_term_id filter - END
        
        // education_grade_id filter
        $educationGradeOptions = $this->getEducationGradeOptions($extra['selectedAcademicPeriodOptions'], true);

        if (isset($requestQuery) && array_key_exists('grade', $requestQuery)) {
            $selectedGrade = $requestQuery['grade'];
        } else {
            $selectedGrade = self::DEFAULT;
        }

        $extra['selectedGradeOptions'] = $selectedGrade;
        // education_grade_id filter - END

        // status filter
        $statusOptions = [self::DEFAULT => __('-- Select Status --')] + $this->_status;

        if (isset($requestQuery) && array_key_exists('status', $requestQuery)) {
            $selectedStatusId = $requestQuery['status'];
        } else {
            $selectedStatusId = self::DEFAULT;
        }

        $extra['selectedStatusOptions'] = $selectedStatusId;
        // status filter - END

        $extra['elements']['control'] = [
            'name' => 'Schedule.Timetables/controls',
            'data' => [
                // academic_period_id
                'periodOptions'=> $academicPeriodOptions,
                'selectedPeriodOption'=> $extra['selectedAcademicPeriodOptions'],

                // institution_schedule_term_id
                'termOptions' => $termOptions,
                'selectedTermOptions' => $extra['selectedTermOptions'],

                // education_grade_id
                'educationGradeOptions' => $educationGradeOptions,
                'selectedGradeOptions' => $extra['selectedGradeOptions'],

                // status 
                'statusOptions' => $statusOptions,
                'selectedStatusOption' => $extra['selectedStatusOptions']
            ],
            'order' => 3
        ];
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['type' => 'select']);
        $this->field('institution_schedule_term_id', ['type' => 'select']);
        $this->field('name');
        $this->field('education_grade_id');
        $this->field('institution_class_id');
        $this->field('shift');
        $this->field('institution_schedule_interval_id');
        $this->field('status');
        $this->setFieldOrder(['academic_period_id', 'institution_schedule_term_id', 'name', 'education_grade_id', 'institution_class_id', 'shift', 'institution_schedule_interval_id', 'status']);
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain([
                'ScheduleIntervals.Shifts.ShiftOptions',
                'ScheduleIntervals.Timeslots',
                'InstitutionClasses.ClassGrades.EducationGrades'
            ]);
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['type' => 'select']);
        $this->field('institution_schedule_term_id', ['type' => 'select']);
        $this->field('status');
        $this->field('name');
        $this->field('grade');
        $this->field('institution_class_id');
        $this->field('shift');
        $this->field('time_slots', [
            'type' => 'element',
            'element' => 'Schedule.Intervals/interval_timeslots'
        ]);
        $this->field('institution_schedule_interval_id', ['visible' => true]);
        $this->setFieldOrder(['academic_period_id', 'institution_schedule_term_id', 'status', 'name', 'grade', 'institution_class_id', 'shift', 'time_slots']);
       
        $tabElements = [
            'ScheduleTimetableOverview' => [
                'url' => [
                    'plugin' => $this->controller->plugin,
                    'controller' => $this->controller->name,
                    'action' => 'ScheduleTimetableOverview'
                ],
                'text' => __('Overview')
            ],
            'ScheduleTimetable' => [
                'url' => [
                    'plugin' => $this->controller->plugin,
                    'controller' => $this->controller->name,
                    'action' => 'ScheduleTimetable',
                    'view',
                    'timetableId'=>$this->request->params['pass'][1],
                    'period'=>$this->request->query('period')
                    
                ],
                'text' => __('Timetable')
            ]
        ];

        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'ScheduleTimetableOverview');

    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        if (array_key_exists('edit', $extra['toolbarButtons'])) {
            $timetableId = $entity->id;
            $institutionId = $entity->institution_id;

            $timetableEditUrl = [
                'plugin' => $this->controller->plugin,
                'controller' => $this->controller->name,
                'action' => 'ScheduleTimetable',
                'institutionId' => $this->paramsEncode(['id' => $institutionId]),
                'edit',
                'timetableId' => $this->paramsEncode(['id' => $timetableId])
            ];

            $extra['toolbarButtons']['edit']['url'] = $timetableEditUrl;
        }
    }

    // OnGet Events
    public function onGetStatus(Event $event, Entity $entity)
    {
        if ($entity->status == self::DRAFT) {
            $color = '#DDDDDD';
        } else { // self::PUBLISHED
            $color = '#77B576';
        }
        
        $status = $this->_status[$entity->status];

        if ($this->action == 'index') {
            return '<span class="status" style="border:none; background-color: ' . $color . ';">' . $status . '</span>';

        } 
        return $status;
    }

    public function onGetShift(Event $event, Entity $entity)
    {
        return $entity->schedule_interval->shift->shift_option->name;
    }

    public function onGetGrade(Event $event, Entity $entity)
    {
        $classGrades = $entity->institution_class->class_grades;
        $educationGradeList = [];

        foreach ($classGrades as $classGradeEntity) {
            $educationGradeList[] = $classGradeEntity->education_grade->name;
        }

        if (empty($educationGradeList)) {
            return '-';
        }

        return implode(', ', $educationGradeList);
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
        }
        return $attr;
    }

    public function onUpdateFieldInstitutionScheduleTermId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $academicPeriodId = $this->extractRequestData($request, 'academic_period_id');

            $attr['type'] = 'select';
            $attr['options'] = $this->getTermOptions($academicPeriodId);
        }
        return $attr;
    }

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $academicPeriodId = $this->extractRequestData($request, 'academic_period_id');

            $attr['attr']['label'] = __('Grade');
            $attr['onChangeReload'] = true;            
            $attr['attr']['required'] = true;
            $attr['options'] = $this->getEducationGradeOptions($academicPeriodId);
        }
        return $attr;
    }

    public function onUpdateFieldInstitutionClassId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $academicPeriodId = $this->extractRequestData($request, 'academic_period_id');
            $educationGradeId = $this->extractRequestData($request, 'education_grade_id');
            
            $attr['type'] = 'select';
            $attr['options'] = $this->getInstitutionClassOptions($academicPeriodId, $educationGradeId);
        }
        return $attr;
    }

    public function onUpdateFieldShift(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $academicPeriodId = $this->extractRequestData($request, 'academic_period_id');

            $attr['onChangeReload'] = true;            
            $attr['type'] = 'select';
            $attr['attr']['required'] = true;
            $attr['options'] = $this->getShiftOptions($academicPeriodId);
        }
        return $attr;
    }

    public function onUpdateFieldInstitutionScheduleIntervalId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $shiftId = $this->extractRequestData($request, 'shift');
            $academicPeriodId = $this->extractRequestData($request, 'academic_period_id');

            $attr['attr']['label'] = __('Interval');
            $attr['type'] = 'select';
            $attr['options'] = $this->getScheduleIntervalOptions($academicPeriodId, $shiftId);

        }
        return $attr;
    }

    public function onUpdateFieldStatus(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['type'] = 'readonly';
            $attr['value'] = self::DRAFT;
            $attr['attr']['value'] = $this->_status[self::DRAFT];
            return $attr;
        }
    }

    // Change Events
    public function addOnChangeAcademicPeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        unset($data[$this->alias()]['institution_schedule_interval_id']);
        unset($data[$this->alias()]['shift']);
        unset($data[$this->alias()]['education_grade_id']);
    }

    public function addOnSaveSchedule(Event $event, Entity $entity, ArrayObject $data, ArrayObject $patchOptions, ArrayObject $extra)
    {
        $patchOptions['validate'] = true;
        $entity = $this->patchEntity($entity, $data->getArrayCopy(), $patchOptions->getArrayCopy());
        $result = $this->save($entity);

        if ($result) {
            $timetableId = $result->id;
            $institutionId = $result->institution_id;
            $timetableEditUrl = [
                'plugin' => $this->controller->plugin,
                'controller' => $this->controller->name,
                'action' => 'ScheduleTimetable',
                'institutionId' => $this->paramsEncode(['id' => $institutionId]),
                'edit',
                'timetableId' => $this->paramsEncode(['id' => $timetableId])
            ];
            return $this->controller->redirect($timetableEditUrl);   
        } else {
            $this->controller->Alert->error('general.add.failed');
        }
    }

    // Get Options
    private function getInstitutionClassOptions($academicPeriodId = null, $educationGradeId = null)
    {
        if (is_null($educationGradeId) || is_null($academicPeriodId)) {
            return [];
        }
        
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $classOptions = $this->InstitutionClasses
            ->find('list')
            ->find('byGrades', ['education_grade_id' => $educationGradeId])
            ->where([
                $this->InstitutionClasses->aliasField('academic_period_id') => $academicPeriodId,
                $this->InstitutionClasses->aliasField('institution_id') => $institutionId
            ])
            ->group([$this->InstitutionClasses->aliasField('id')])
            ->toArray();

        return $classOptions;
    }

    private function getEducationGradeOptions($academicPeriodId = null, $withDefault = false)
    {
        if (is_null($academicPeriodId)) {
            return [];
        }

        $institutionId = $this->Session->read('Institution.Institutions.id');
        $InstitutionGradesTable = TableRegistry::get('Institution.InstitutionGrades');
        $educationGradeOptions = $InstitutionGradesTable->getGradeOptions($institutionId, $academicPeriodId);

        if ($withDefault) {
            if (!empty($educationGradeOptions)) {
                $educationGradeOptions = [0 => __('-- Select Grade --')] + $educationGradeOptions;
            } else {
                $educationGradeOptions = [0 => __('No Options')];
            }
        }

        return $educationGradeOptions;
    }

    private function getScheduleIntervalOptions($academicPeriodId = null, $shiftId = null) 
    {
        if (is_null($shiftId) || is_null($academicPeriodId)) {
            return [];
        }

        $institutionId = $this->Session->read('Institution.Institutions.id');
        $intervalOptions = $this->ScheduleIntervals
            ->find('list')
            ->where([
                $this->ScheduleIntervals->aliasField('institution_id') => $institutionId,
                $this->ScheduleIntervals->aliasField('academic_period_id') => $academicPeriodId,
                $this->ScheduleIntervals->aliasField('institution_shift_id') => $shiftId
            ])
            ->toArray();

        return $intervalOptions;
    }

    private function getTermOptions($academicPeriodId = null, $withDefault = false)
    {
        if (is_null($academicPeriodId)) {
            return [];
        }

        $institutionId = $this->Session->read('Institution.Institutions.id');
        $termOptions = $this->ScheduleTerms
            ->find('list')
            ->where([
                $this->ScheduleTerms->aliasField('institution_id') => $institutionId,
                $this->ScheduleTerms->aliasField('academic_period_id') => $academicPeriodId
            ])
            ->order([$this->ScheduleTerms->aliasField('start_date') => 'ASC'])
            ->toArray();

        if ($withDefault) {
            if (!empty($termOptions)) {
                $termOptions = [0 => __('-- Select Term --')] + $termOptions;
            } else {
                $termOptions = [0 => __('No Options')];
            }
        }
        return $termOptions;
    }

    private function getShiftOptions($academicPeriodId = null)
    {
        if (is_null($academicPeriodId)) {
            return [];
        }

        $institutionId = $this->Session->read('Institution.Institutions.id');
        $ShiftsTable = TableRegistry::get('Institution.InstitutionShifts');

        $shiftOptions = $ShiftsTable
            ->find('list', [
                'keyField' => 'id',
                'valueField' => 'name'
            ])
            ->select([
                'id' => $ShiftsTable->aliasField('id'),
                'name' => 'ShiftOptions.name'
            ])
            ->contain('ShiftOptions')
            ->where([
                $ShiftsTable->aliasField('academic_period_id') => $academicPeriodId,
                $ShiftsTable->aliasField('Institution_id') => $institutionId
            ])
            ->toArray();

        return $shiftOptions;
    }

    // Finder
    public function findTimetableStatus(Query $query, array $options)
    {
        $tempStatus = $this->_status;
        $status = [];

        foreach ($tempStatus as $id => $name) {
            $status[] = [
                'id' => $id,
                'name' => $name
            ];
        }

        return $query->formatResults(function (ResultSetInterface $results) use ($status) {
            return $status;
        });
    }

    // Misc 
    private function extractRequestData(Request $request, $field)
    {
        if (isset($request->data) && array_key_exists($this->alias(), $request->data)) {
            $requestData = $request->data[$this->alias()];

            if (array_key_exists($field, $requestData)) {
                return $requestData[$field];
            }
        }

        if ($field == 'academic_period_id') {
            return $this->AcademicPeriods->getCurrent();
        }

        return null;
    }
}
