<?php
namespace Historical\Model\Table;

use ArrayObject;
use DatePeriod;
use DateInterval;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use App\Model\Traits\OptionsTrait;
use App\Model\Table\ControllerActionTable;

class HistoricalStaffLeaveTable extends ControllerActionTable
{
    use OptionsTrait;
    public function initialize(array $config)
    {
        $this->table('historical_staff_leaves');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('StaffLeaveTypes', ['className' => 'Staff.StaffLeaveTypes']);
        $this->addBehavior(
            'ControllerAction.FileUpload', [
            'size' => '10MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true]
        );
        $this->addBehavior('Historical.Historical', [
            'originUrl' => [
                'action' => 'StaffLeave'
            ],
            'model' => 'Historical.HistoricalStaffLeave'
        ]);

        $this->toggle('index', false);
    }
    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->add('date_to', 'ruleCompareDateReverse', [
                'rule' => ['compareDateReverse', 'date_from', true]
            ])
            ->allowEmpty('file_content');
        return $validator;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('number_of_days', [
                'visible' => ['view' => true, 'edit' => false, 'add' => false]
            ]
        );
        $this->field('status', [
                'visible' => ['view' => true, 'edit' => false, 'add' => false]
            ]
        );
        $this->field('assignee', [
                'visible' => ['view' => true, 'edit' => false, 'add' => false]
            ]
        );
        $this->setFieldOrder(['status','assignee','institution_name', 'staff_leave_type_id', 'date_from', 'date_to', 'start_time', 'end_time','full_day', 'number_of_days', 'comments', 'academic_period_id', 'file_name', 'file_content']);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('staff_leave_type_id');
        $this->field('full_day');
        $this->field('start_time', ['entity' => $entity]);
        $this->field('end_time', ['entity' => $entity]);

        $this->setFieldOrder(['staff_leave_type_id', 'institution_name', 'date_from', 'date_to', 'full_day', 'start_time', 'end_time', 'number_of_days', 'comments', 'file_name', 'file_content']);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $firstDayOfWeek = $ConfigItems->value('first_day_of_week');
        $daysPerWeek = $ConfigItems->value('days_per_week');

        $numericDaysArray = [];
        for ($i = 0; $i < $daysPerWeek; ++$i) {
            $day = ($firstDayOfWeek + $i) % 7;
            $numericDaysArray[] = $day;
        }

        $dateFrom = date_create($entity->date_from);
        $dateTo = date_create($entity->date_to);
        $isFullDayLeave = $entity->full_day;

        if ($isFullDayLeave == 1) {
            $day = 1;
            $entity->start_time = null;
            $entity->end_time = null;
        } else {
            $day = 0.5;
        }

        $startDate = $dateFrom;
        $endDate = $dateTo;
        $endDate = $endDate->modify('+1 day');
        $interval = new DateInterval('P1D');
        $datePeriod = new DatePeriod($startDate, $interval, $endDate);
        $dayCount = 0;

        foreach ($datePeriod as $key => $date) {
            $numericDay = $date->format('N');
            if (in_array($numericDay, $numericDaysArray)) {
                ++$dayCount;
            }
        }

        $entity->number_of_days = $dayCount * $day;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'institution_name') {
            return __('Institution');
        } else if ($field == 'file_content') {
            return __('Attachment');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetStatus(Event $event, Entity $entity)
    {
        return '<span class="status highlight">Historical</span>';
    }

    public function onGetAssignee(Event $event, Entity $entity)
    {
        return '-';
    }

    public function onGetFullDay(Event $event, Entity $entity)
    {
        return $this->getSelectOptions('general.yesno')[$entity->full_day];
    }

    public function onUpdateFieldStaffLeaveTypeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'select';
        }
        return $attr;
    }

    public function onUpdateFieldFullDay(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['select'] = false;
            $attr['options'] = $this->getSelectOptions('general.yesno');
            $attr['onChangeReload'] = true;
        }
        return $attr;
    }

    public function onUpdateFieldStartTime(Event $event, array $attr, $action, Request $request)
    {
        $attr = $this->_setupTimeField($event, $attr, $action, $request);
        return $attr;
    }

    public function onUpdateFieldEndTime(Event $event, array $attr, $action, Request $request)
    {
        $attr = $this->_setupTimeField($event, $attr, $action, $request);
        return $attr;
    }

    private function _setupTimeField(Event $event, array $attr, $action, Request $request)
    {
        $attr['visible'] = false;
        switch ($action) {
        case 'add':
            if (isset($request->data[$this->alias()]['full_day']) && !$request->data[$this->alias()]['full_day']) {
                $attr['visible'] = true;
            }
            break;

        case 'edit':
            $fullDay = $attr['entity']->full_day;
            if (!$fullDay) {
                $attr['visible'] = true;
            }
            break;
        }
        return $attr;
    }
}