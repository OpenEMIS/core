<?php
namespace Staff\Model\Table;

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

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->updateBackButton($extra);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field(
            'number_of_days', [
                'visible' => ['index' => true, 'view' => true, 'edit' => false, 'add' => false]
            ]
        );
    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        $extra['redirect'] = ['plugin' => 'Directory', 'controller' => 'Directories', 'action' => 'StaffLeave', 'index'];
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('staff_leave_type_id');
        $this->field('full_day');
        $this->field('start_time', ['entity' => $entity]);
        $this->field('end_time', ['entity' => $entity]);
        $this->field('file_name', ['type' => 'hidden']);

        $this->setFieldOrder(['staff_leave_type_id', 'institution_name', 'date_from', 'date_to', 'full_day', 'start_time', 'end_time', 'number_of_days', 'comments', 'file_name', 'file_content']);
    }

    public function onUpdateFieldStaffLeaveTypeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'select';
            $attr['onChangeReload'] = true;
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
            if ($fullDay) {
                $attr['visible'] = true;
            }
            break;
        }
        return $attr;
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $firstDayOfWeek = $ConfigItems->value('first_day_of_week');
        $daysPerWeek = $ConfigItems->value('days_per_week');
        $endDay = $firstDayOfWeek + $daysPerWeek - 1;

        $numericDaysArray = [];
        for ($i = $firstDayOfWeek; $i <= $endDay; $i++) {
            $numericDaysArray[] = $i;
        }

        $dateFrom = date_create($entity->date_from);
        $dateTo = date_create($entity->date_to);
        $isFullDayLeave = $entity->full_day;
        if (!$entity->isNew()) {
            $entityId = $entity->id;
        }
        /*
            Non full day leave is always assume to be 0.5 since staff can only apply 2 non full day leave
            Set start_time and end_time to null, in the case when user first choose Full Day = No and then Full Day = Yes. If start_time and end_time is not set to null, the start_time and end_time will be saved which shouldn't be the case.
        */
        if ($isFullDayLeave == 1) {
            $day = 1;
            $entity->start_time = null;
            $entity->end_time = null;
        } else {
            $day = 0.5;
        }

        $entityStartTime = $entity->start_time;
        $entityEndTime = $entity->end_time;

        $startDate = $dateFrom;
        $endDate = $dateTo;
        $endDate = $endDate->modify('+1 day');
        $interval = new DateInterval('P1D');
        $datePeriod = new DatePeriod($startDate, $interval, $endDate);
        $count = 0;

        foreach ($datePeriod as $key => $date) {
            $numericDay = $date->format('N');
            if (in_array($numericDay, $numericDaysArray)) {
                $count = $count + $day;
            }
        }
        $entity->number_of_days = $count;
    }

    private function updateBackButton(ArrayObject $extra)
    {
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $toolbarButtonsArray['back']['url'] = [
            'plugin' => 'Directory',
            'controller' => 'Directories',
            'action' => 'StaffLeave',
            'type' => 'staff'
        ];
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
    }
}