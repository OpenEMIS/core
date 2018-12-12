<?php
namespace Schedule\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
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

        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Classes', ['className' => 'Institution.InstitutionClasses', 'foreignKey' => 'institution_class_id']);
        $this->belongsTo('ScheduleIntervals', ['className' => 'Schedule.ScheduleIntervals', 'foreignKey' => 'institution_schedule_interval_id']);
        $this->belongsTo('ScheduleTerms', ['className' => 'Schedule.ScheduleTerms', 'foreignKey' => 'institution_schedule_term_id']);

        // $this->hasMany('Lessons', [
        //     'className' => 'Schedule.ScheduleLessons', 
        //     'foreignKey' => 'institution_schedule_timetable_id', 
        //     'dependent' => true, 
        //     'cascadeCallbacks' => true
        // ]);

        $this->addBehavior('Schedule.Schedule');

        $this->_status = [
            self::DRAFT => __('Draft'),
            self::PUBLISHED => __('Published')
        ];
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator;
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'institution_schedule_term_id':
                return __('Term');
                case 'institution_class_id':
                return __('Class');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
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
        $this->field('institution_schedule_term_id');
        $this->field('name');
        $this->field('institution_class_id');
        $this->field('shift'); // filtering purpose (?)
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('institution_schedule_interval_id', ['visible' => false]);
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
        
        // education_grade_id filter - END

        // status filter
        $statusOptions = [self::DEFAULT => __('-- Select Status --')] + $this->_status;

        if (isset($requestQuery) && array_key_exists('status', $requestQuery)) {
            $selectedStatusId = $requestQuery['status'];
        } else {
            $selectedStatusId = self::DEFAULT;
        }

        $extra['selectedStatusOptions'] = $selectedPeriodId;
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
        $this->field('institution_grade_id');
        $this->field('institution_class_id');
        $this->field('shift'); // filtering purpose (?)
        $this->field('institution_schedule_interval_id');
        $this->field('status');
        $this->setFieldOrder(['academic_period_id', 'institution_schedule_term_id', 'name', 'institution_grade_id', 'institution_class_id', 'shift', 'institution_schedule_interval_id', 'status']);
    }

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

    public function onUpdateFieldInstitutionScheduleTermId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $academicPeriodId = $this->extractRequestData($request, 'academic_period_id');

            $attr['type'] = 'select';
            $attr['options'] = $this->getTermOptions($academicPeriodId);
        }
        return $attr;
    }

    public function onUpdateFieldInstitutionGradeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['attr']['label'] = __('Grade');
            $attr['attr']['required'] = true;

            // grade options
        }
        return $attr;
    }

    public function onUpdateFieldInstitutionClassId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            // class options by education grade id

        }
        return $attr;
    }

    public function onUpdateFieldShift(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $academicPeriodId = $this->extractRequestData($request, 'academic_period_id');

            $attr['onChangeReload'] = true;            
            $attr['type'] = 'select';
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
        $attr['type'] = 'readonly';
        $attr['value'] = self::DRAFT;
        $attr['attr']['value'] = $this->_status[self::DRAFT];
        return $attr;
    }

    public function addOnChangeAcademicPeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        unset($data[$this->alias()]['institution_schedule_interval_id']);
        unset($data[$this->alias()]['shift']);
    }

    private function getEducationGradeOptions($academicPeriodId = null, $withDefault = false)
    {
        if (is_null($academicPeriodId)) {
            return [];
        }

        $institutionId = $this->Session->read('Institution.Institutions.id');
        $educationGradeOptions = [];

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

    private function extractRequestData(Request $request, $field)
    {
        if (isset($request->data) && array_key_exists($this->alias(), $request->data)) {
            $requestData = $request->data[$this->alias()];

            if (array_key_exists($field, $requestData)) {
                return $requestData[$field];
            }
        }
        return null;
    }
}
