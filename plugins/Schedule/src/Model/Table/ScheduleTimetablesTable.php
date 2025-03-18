<?php
namespace Schedule\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Datasource\ResultSetInterface;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Http\ServerRequest;
use Cake\Datasource\Exception\RecordNotFoundException; // POCOR-8985

class ScheduleTimetablesTable extends ControllerActionTable
{
    const DRAFT = 1;
    const PUBLISHED = 2;
    const DEFAULT = -1;
    private $_status = [];

    public function initialize(array $config): void
    {
        $this->setTable('institution_schedule_timetables');
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
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['ScheduleTimetableOverview' =>['id']
            ]
        ]);
    }

    public function validationDefault(Validator $validator): Validator
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
        // POCOR-8985 start
         $validator
             ->notEmptyString('name')
             ->notEmptyString('institution_schedule_term_id')
             ->notEmptyString('institution_class_id')
             ->notEmptyString('shift')
             ->notEmptyString('grade')
             ->notEmptyString('institution_schedule_interval_id')
         ;
        // POCOR-8985 end
        return $validator;
    }

    public function implementedEvents(): array
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
//                        'escapeTitle' => false,
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
            case 'status':
                return __('Status');
            case 'name':
                return __('Name');
            case 'shift':
                return __('Shift');
            case 'academic_period_id':
                return __('Academic Period');
            case 'grade':
                return __('Grade');
            case 'time_slots':
                return __('Time Slots');
            case 'modified':
                return __('Modified');
            case 'modified_user_id':
                return __('Modified By');
            case 'created':
                return __('Created');
            case 'created_user_id':
                return __('Created By');
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
        if (isset($extra['selectedAcademicPeriodOptions'])) {
            $query->where([
                $this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodOptions']
            ]);
        }

        // institution_schedule_term_id filter
        if (isset($extra['selectedTermOptions']) && $extra['selectedTermOptions'] != self::DEFAULT) {
            $query->where([
                $this->aliasField('institution_schedule_term_id') => $extra['selectedTermOptions']
            ]);
        }

        // education_grade_id filter
        if (isset($extra['selectedGradeOptions']) && $extra['selectedGradeOptions'] != self::DEFAULT) {
            $educationGradeId = $extra['selectedGradeOptions'];
            $query
                ->matching('InstitutionClasses.ClassGrades', function (Query $q) use ($educationGradeId) {
                    return $q->where(['ClassGrades.education_grade_id' => $educationGradeId]);
                });
        }

        // status filter
        if (isset($extra['selectedStatusOptions']) && $extra['selectedStatusOptions'] != self::DEFAULT) {
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
        $requestQuery = $this->request->getQuery();

        // academic_period_id filter
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();

        if (isset($requestQuery) && isset($requestQuery['period'])) {
            $selectedPeriodId = $requestQuery['period'];
        } else {
            $selectedPeriodId = $this->AcademicPeriods->getCurrent();
        }

        $extra['selectedAcademicPeriodOptions'] = $selectedPeriodId;

        // academic_period_id filter - END

        // institution_schedule_term_id filter
        $termOptions = $this->getTermOptions($extra['selectedAcademicPeriodOptions'], true);

        if (isset($requestQuery) && isset($requestQuery['term'])) {
            $selectedTerm = $requestQuery['term'];
        } else {
            $selectedTerm = self::DEFAULT;
        }

        $extra['selectedTermOptions'] = $selectedTerm;
        // institution_schedule_term_id filter - END

        // education_grade_id filter
        $educationGradeOptions = $this->getEducationGradeOptions($extra['selectedAcademicPeriodOptions'], true);

        if (isset($requestQuery) && isset($requestQuery['grade'])) {
            $selectedGrade = $requestQuery['grade'];
        } else {
            $selectedGrade = self::DEFAULT;
        }

        $extra['selectedGradeOptions'] = $selectedGrade;
        // education_grade_id filter - END

        // status filter
        $statusOptions = [self::DEFAULT => __('-- Select Status --')] + $this->_status;

        if (isset($requestQuery) && isset($requestQuery['status'])) {
            $selectedStatusId = $requestQuery['status'];
        } else {
            $selectedStatusId = self::DEFAULT;
        }

        $extra['selectedStatusOptions'] = $selectedStatusId;
        // status filter - END
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $extra['elements']['control'] = [
            'name' => 'Schedule.Timetables/controls',
            'data' => [
                 'encodedQueryString' => $encodedQueryString,
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

        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Institutions','Timetable','Schedules');
        if(!empty($is_manual_exist)){
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target'=>'_blank'
            ];

            $helpBtn['url'] = $is_manual_exist['url'];
            $helpBtn['type'] = 'button';
            $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
            $helpBtn['attr'] = $btnAttr;
            $helpBtn['attr']['title'] = __('Help');
            $extra['toolbarButtons']['help'] = $helpBtn;
        }
        // End POCOR-5188
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        // POCOR-8985 start
        $this->field('academic_period_id', ['type' => 'select', 'attr' => ['required' => true]]);
        $this->field('institution_schedule_term_id', ['type' => 'select', 'attr' => ['required' => true]]);
        $this->field('name', ['attr' => ['required' => true]]);
        $this->field('education_grade_id', ['attr' => ['required' => true]]);
        $this->field('institution_class_id', ['attr' => ['required' => true]]);
        $this->field('shift', ['attr' => ['required' => true]]);
        $this->field('institution_schedule_interval_id', ['attr' => ['required' => true]]);
        // POCOR-8985 end
        $this->field('status');
        $this->setFieldOrder(['academic_period_id', 'institution_schedule_term_id', 'name', 'education_grade_id', 'institution_class_id', 'shift', 'institution_schedule_interval_id', 'status']);
    }

    /**
     * // POCOR-8985
     * common proc to show related field in the index table
     * @param $tableName
     * @param $relatedField
     * @return string
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function getRelatedName($tableName, $relatedField)
    {
        if (!$relatedField) {
            return "";
        }
        $Table = TableRegistry::getTableLocator()->get($tableName);
        try {
            $related = $Table->get($relatedField);
            $name = strval($related->name);
            return $name;
        } catch (RecordNotFoundException $e) {
            return $relatedField;
        }
    }
    public function editAfterAction(Event $event, Entity $entity)
    {
        $this->field('academic_period_id',
            ['type' => 'readonly',
                'attr' => ['value' =>
                    self::getRelatedName('AcademicPeriod.AcademicPeriods',
                        $entity->academic_period_id )]]);
        $this->field('institution_schedule_term_id',
            ['type' => 'readonly',
                'attr' => ['value' =>
                    self::getRelatedName('Schedule.ScheduleTerms',
                        $entity->institution_schedule_term_id )]]);
        $this->field('name', ['attr' => ['required' => true]]);
        $this->field('institution_class_id',
            ['type' => 'readonly',
                'attr' => ['value' =>
                    self::getRelatedName('Institution.InstitutionClasses',
                        $entity->institution_class_id )]]);
        $this->field('institution_schedule_interval_id',
            ['type' => 'readonly',
                'attr' => ['value' =>
                    self::getRelatedName('Schedule.ScheduleIntervals',
                        $entity->institution_schedule_interval_id )]]);
        $this->field('status',
            ['type' => 'readonly',
                'attr' => ['value' =>
                    $this->_status[$entity->status]]]);
        $this->setFieldOrder(['academic_period_id', 'institution_schedule_term_id', 'name',
            'institution_class_id', 'institution_schedule_interval_id', 'status']);
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
        $params = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($params);
        $tabElements = [
            'ScheduleTimetableOverview' => [
                'url' => [
                    'plugin' => $this->controller->getPlugin(),
                    'controller' => $this->controller->getName(),
                    'action' => 'ScheduleTimetableOverview',
                    '0' => 'view',
                    '1' => $encodedQueryString,
                ],
                'text' => __('Overview')
            ],
            'ScheduleTimetable' => [
                'url' => [
                    'plugin' => $this->controller->getPlugin(),
                    'controller' => $this->controller->getName(),
                    'action' => 'ScheduleTimetable',
                    '0' => 'view',
                    '1' => $encodedQueryString
                ],
                'text' => __('Timetable')
            ]
        ];

        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'ScheduleTimetableOverview');

    }

    //POCOR-8662 -- START
    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $extraArray = $extra->getArrayCopy();
        if (isset($extraArray['toolbarButtons']) && is_array($extraArray['toolbarButtons'])) {
            if (array_key_exists('edit', $extraArray['toolbarButtons'])) {
                $params = $this->getQueryString();
                $timetableId = $entity->id;
                $institutionId = $entity->institution_id;
                $params['institution_id'] = $institutionId;
                $params['timetable_id'] = $timetableId;
                $encodedQueryString = $this->paramsEncode($params);
                $timetableEditUrl = [
                    'plugin' => $this->controller->getPlugin(),
                    'controller' => $this->controller->getName(),
                    'action' => 'ScheduleTimetableOverview',
                    '0' => 'edit',
                    '1' => $encodedQueryString,
                ];
                $extraArray['toolbarButtons']['edit']['url'] = $timetableEditUrl;
            }
        }
    }
    //POCOR-8662 -- END

    public function viewAfterActionOld(Event $event, Entity $entity, ArrayObject $extra)
    {
        if (array_key_exists('edit', $extra['toolbarButtons'])) {
            $params = $this->getQueryString();
            $timetableId = $entity->id;
            $institutionId = $entity->institution_id;
            $params['institution_id'] = $institutionId;
            $params['timetable_id'] = $timetableId;
            $encodedQueryString = $this->paramsEncode($params);
            $timetableEditUrl = [
                'plugin' => $this->controller->getPlugin(),
                'controller' => $this->controller->getName(),
                'action' => 'ScheduleTimetableOverview',
                '0' => 'edit',
                '1' => $encodedQueryString,
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
    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, ServerRequest $request)
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

    public function onUpdateFieldInstitutionScheduleTermId(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $academicPeriodId = $this->extractRequestData($request, 'academic_period_id');

            $attr['type'] = 'select';
            $attr['options'] = $this->getTermOptions($academicPeriodId);
        }
        return $attr;
    }

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, ServerRequest $request)
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

    public function onUpdateFieldInstitutionClassId(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $academicPeriodId = $this->extractRequestData($request, 'academic_period_id');
            $educationGradeId = $this->extractRequestData($request, 'education_grade_id');
            // POCOR-8985 start
            $attr['onChangeReload'] = true;
            $attr['attr']['required'] = true;
            // POCOR-8985 end
            $attr['type'] = 'select';
            $attr['options'] = $this->getInstitutionClassOptions($academicPeriodId, $educationGradeId);
        }

        return $attr;
    }

    public function onUpdateFieldShift(Event $event, array $attr, $action, ServerRequest $request)
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

    public function onUpdateFieldInstitutionScheduleIntervalId(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $shiftId = $this->extractRequestData($request, 'shift');
            $academicPeriodId = $this->extractRequestData($request, 'academic_period_id');

            $attr['attr']['label'] = __('Interval');
            $attr['type'] = 'select';
            $attr['attr']['required'] = true; // POCOR-8985
            $attr['options'] = $this->getScheduleIntervalOptions($academicPeriodId, $shiftId);

        }
        return $attr;
    }

    public function onUpdateFieldStatus(Event $event, array $attr, $action, ServerRequest $request)
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
        unset($data[$this->getAlias()]['institution_schedule_interval_id']);
        unset($data[$this->getAlias()]['shift']);
        unset($data[$this->getAlias()]['education_grade_id']);
    }

    public function addOnSaveSchedule(Event $event, Entity $entity, ArrayObject $data, ArrayObject $patchOptions, ArrayObject $extra)
    {

        $patchOptions['validate'] = true;
        $entity = $this->patchEntity($entity,
            $data->getArrayCopy(),
            $patchOptions->getArrayCopy());
        $result = $this->save($entity);
//        echo "<pre>"; print_r($result); die('gjhghg');


        if ($result) {
            $params = $this->getQueryString();
            $timetableId = $result->id;
            $institutionId = $result->institution_id;
            $params['institution_id'] = $institutionId;
            $params['timetable_id'] = $timetableId;
            $encodedQueryString = $this->paramsEncode($params);
            $timetableEditUrl = [
                'plugin' => $this->controller->getPlugin(),
                'controller' => $this->controller->getName(),
                'action' => 'ScheduleTimetable',
                '0' => 'edit',
                '1' => $encodedQueryString
            ];
            return $this->controller->redirect($timetableEditUrl);
        } else {
//            die('<pre>' . print_r($errors, true));
            $this->controller->Alert->error('general.add.failed');
        }
    }

    // Get Options
    private function getInstitutionClassOptions($academicPeriodId = null, $educationGradeId = null)
    {
        if (is_null($educationGradeId) || is_null($academicPeriodId)) {
            return [];
        }

        $institutionId = $this->getInstitutionID();
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

        $institutionId = $this->getInstitutionID();
        $InstitutionGradesTable = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades');
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

        $institutionId = $this->getInstitutionID();
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

        $institutionId = $this->getInstitutionID();
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

        $institutionId = $this->getInstitutionID();
        $ShiftsTable = TableRegistry::getTableLocator()->get('Institution.InstitutionShifts');

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
    private function extractRequestData(serverRequest $request, $field)
    {
        $getRequestData = $request->getData();
        if (isset($getRequestData) && array_key_exists($this->getAlias(), $getRequestData)) {
            $requestData = $getRequestData[$this->getAlias()];
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
