<?php
namespace Historical\Model\Table;

use ArrayObject;
use DatePeriod;
use DateInterval;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use App\Model\Traits\OptionsTrait;
use App\Model\Table\ControllerActionTable;
use Cake\Http\ServerRequest;

class HistoricalStaffLeaveTable extends ControllerActionTable
{
    use OptionsTrait;
    public function initialize(array $config): void
    {
        $this->setTable('historical_staff_leave');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('StaffLeaveTypes', ['className' => 'Staff.StaffLeaveTypes']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
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
    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        $validator
            ->add('date_to', 'ruleCompareDateReverse', [
                'rule' => ['compareDateReverse', 'date_from', true]
            ])
            ->add('date_to', 'ruleLessThanToday', [
                'rule' => ['lessThanToday', false]
            ])
            ->add('date_from', 'ruleLessThanToday', [
                'rule' => ['lessThanToday', false]
            ])
            ->add('end_time', 'ruleCompareDateReverse', [
                'rule' => ['compareDateReverse', 'start_time', true]
            ])
            ->allowEmpty('file_content');
        return $validator;
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
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
        $this->setFieldOrder(['status','assignee','institution_id', 'staff_leave_type_id', 'date_from', 'date_to', 'start_time', 'end_time','full_day', 'number_of_days', 'comments', 'academic_period_id', 'file_name', 'file_content']);
    }

    public function editBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain([
                'Institutions'
            ]);
    }

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('full_day');
        $this->field('institution_id', ['entity' => $entity]);
        $this->field('institution_type_id');
        $this->field('staff_leave_type_id');
        $this->field('start_time', ['entity' => $entity]);
        $this->field('end_time', ['entity' => $entity]);

        $this->setFieldOrder(['staff_leave_type_id', 'institution_type_id', 'institution_id', 'date_from', 'date_to', 'full_day', 'start_time', 'end_time', 'number_of_days', 'comments', 'file_name', 'file_content']);
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $firstDayOfWeek = $ConfigItems->value('first_day_of_week');
        $daysPerWeek = $ConfigItems->value('days_per_week');

        $numericWorkingDaysArray = [];
        for ($i = 0; $i < $daysPerWeek; ++$i) {
            $day = ($firstDayOfWeek + $i) % 7;
            $numericWorkingDaysArray[] = $day;
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
            if (in_array($numericDay, $numericWorkingDaysArray)) {
                ++$dayCount;
            }
        }

        $entity->number_of_days = $dayCount * $day;
    }

    public function onGetStatus(EventInterface $event, Entity $entity)
    {
        return '<span class="status highlight">Historical</span>';
    }

    public function onGetAssignee(EventInterface $event, Entity $entity)
    {
        return '-';
    }

    public function onGetFullDay(EventInterface $event, Entity $entity)
    {
        return $this->getSelectOptions('general.yesno')[$entity->full_day];
    }

    public function onGetInstitutionId(EventInterface $event, Entity $entity)
    {
        return $entity->institution->code_name;
    }

    public function onUpdateFieldInstitutionTypeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'edit'){
            $attr['visible'] = false;
        } elseif ($action == 'add') {
            $TypesTable = TableRegistry::getTableLocator()->get('Institution.Types');
            $typeOptions = $TypesTable
                ->find('list')
                ->find('visible')
                ->find('order')
                ->toArray();
            
            $attr['type'] = 'select';
            $attr['onChangeReload'] = true;
            $attr['options'] = $typeOptions;
            $attr['attr']['required'] = true;
        }
        return $attr;
    }

    public function onUpdateFieldInstitutionId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'edit'){
            $entity = $attr['entity'];
            $attr['type'] = 'readonly';
            $attr['value'] = $entity->institution_id;
            $attr['attr']['value'] = $entity->institution->code_name;
        } elseif ($action == 'add') {
            $requestData = $request->getData();
            $institutionList = [];
            if ($requestData[$this->getAlias()]) {
                if (array_key_exists('institution_type_id', $requestData[$this->getAlias()]) && !empty($requestData[$this->getAlias()]['institution_type_id'])) {
                    $institutionTypeId = $requestData[$this->getAlias()]['institution_type_id'];
                    
                    $InstitutionsTable = TableRegistry::getTableLocator()->get('Institution.Institutions');
                    $institutionQuery = $InstitutionsTable
                        ->find('list', [
                            'keyField' => 'id',
                            'valueField' => 'code_name'
                        ])
                        ->where([
                            $InstitutionsTable->aliasField('institution_type_id') => $institutionTypeId
                        ])
                        ->order([
                            $InstitutionsTable->aliasField('code') => 'ASC',
                            $InstitutionsTable->aliasField('name') => 'ASC'
                        ]);
                    $institutionList = $institutionQuery->toArray();
                }
            }

            if (empty($institutionList)) {
                $institutionOptions = ['' => $this->getMessage('general.select.noOptions')];

                $attr['type'] = 'select';
                $attr['options'] = $institutionOptions;
                $attr['attr']['required'] = true;
            } else {
                $institutionOptions = ['' => '-- '.__('Select').' --'] + $institutionList;
                $attr['type'] = 'chosenSelect';
                $attr['onChangeReload'] = true;
                $attr['attr']['multiple'] = false;
                $attr['options'] = $institutionOptions;
                $attr['attr']['required'] = true;
            }
        }
        return $attr;
    }

    public function onUpdateFieldStaffLeaveTypeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'select';
        }
        return $attr;
    }

    public function onUpdateFieldFullDay(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['select'] = false;
            $attr['options'] = $this->getSelectOptions('general.yesno');
            $attr['onChangeReload'] = true;
        }
        return $attr;
    }

    public function onUpdateFieldStartTime(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $attr = $this->_setupTimeField($event, $attr, $action, $request);
        return $attr;
    }

    public function onUpdateFieldEndTime(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $attr = $this->_setupTimeField($event, $attr, $action, $request);
        return $attr;
    }

    private function _setupTimeField(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $attr['visible'] = false;
        switch ($action) {
        case 'add':
            if (isset($request->data[$this->getAlias()]['full_day']) && !$request->data[$this->getAlias()]['full_day']) {
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