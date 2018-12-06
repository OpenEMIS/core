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

        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Shifts', ['className' => 'Institution.InstitutionShifts', 'foreignKey' => 'institution_shift_id']);
        $this->hasMany('Timeslots', ['className' => 'Schedule.ScheduleTimeslots', 'foreignKey' => 'institution_schedule_interval_id']);

        $this->addBehavior('Schedule.Schedule');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

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

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
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
        $this->field('intevals', [
            'type' => 'element',
            'element' => 'Schedule.Intervals/interval_timeslots'
        ]);
        $this->setFieldOrder(['academic_period_id', 'name', 'institution_shift_id']);
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        pr('beforeMarshal');
        pr($data);
        die;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['type'] = 'select';
            $attr['select'] = false;
            $attr['options'] = $this->AcademicPeriods->getYearList();
            $attr['default'] = $this->AcademicPeriods->getCurrent();
            $attr['onChangeReload'] = true;
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
            $attr['onChangeReload'] = true;
            return $attr;
        } elseif ($action == 'edit') {
            $attr['type'] = 'readonly';
        }
        return $attr;
    }

    public function addOnAddTimeslot(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $fieldKey = 'timeslots';

        if (empty($data[$this->alias()][$fieldKey])) {
            $data[$this->alias()][$fieldKey] = [];
        }

        if ($data->offsetExists($this->alias())) {
            $data[$this->alias()][$fieldKey][] = [
                'inteval' => '',
            ];
        }

        $options['associated'] = [
            'Timeslots' => ['validate' => false]
        ];
    }

    private function getShiftOptions($academicPeriodId, $allShiftOption = false)
    {
        $institutionId = $this->Session->read('Institution.Institutions.id');
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
