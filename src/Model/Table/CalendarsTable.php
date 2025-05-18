<?php
namespace App\Model\Table;

use ArrayObject;
use Cake\I18n\Date;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use DatePeriod;
use DateInterval;
use Cake\ORM\Locator\TableLocator;
use App\Model\Table\ControllerActionTable;

class CalendarsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('calendar_events');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('CalendarTypes', ['className' => 'CalendarTypes', 'foreignKey' => 'calendar_type_id']);
        $this->hasOne('InstitutionShifts', ['className' => 'Institution.InstitutionShifts', 'foreignKey' => 'institution_shift_id']);

        $this->hasMany('CalendarEventDates', ['className' => 'CalendarEventDates', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('ContactExcel', ['pages' => ['index']]); //POCOR-6898 change Excel to ContactExcel Behaviour
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['InstitutionCalendars' => ['id', 'institution_id']]
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        return $validator
            ->notEmptyString('name', __('This field cannot be left empty'))
            ->notEmptyString('calendar_type_id', __('This field cannot be left empty'))
            ->notEmptyString('academic_period_id', __('This field cannot be left empty'))
            ->add('start_date', 'dateWithinPeriod', [
                'rule' => function ($value, $context) {
                    $inputDate = new Date ($value);

                    if (!empty($context['data']['academic_period_id'])) {
                        $academicPeriodEntity = $this->AcademicPeriods->get($context['data']['academic_period_id']);
                        $academicStartDate = $academicPeriodEntity->start_date;
                        $academicEndDate = $academicPeriodEntity->end_date;

                        if ($inputDate >= $academicStartDate && $inputDate <= $academicEndDate) {
                            return true;
                        } else {
                            $startDate = date('d-m-Y', strtotime($academicStartDate));
                            $endDate = date('d-m-Y', strtotime($academicEndDate));
                            return $this->getMessage('Calendars.dateNotWithinPeriod', ['sprintf' => [$startDate, $endDate]]);
                        }
                    } else {
                        return true;
                    }
                }
            ])
            ->add('end_date', 'dateWithinPeriod', [
                'rule' => function ($value, $context) {
                    $inputDate = new Date ($value);

                    if (!empty($context['data']['academic_period_id'])) {
                        $academicPeriodEntity = $this->AcademicPeriods->get($context['data']['academic_period_id']);
                        $academicStartDate = $academicPeriodEntity->start_date;
                        $academicEndDate = $academicPeriodEntity->end_date;

                        if ($inputDate >= $academicStartDate && $inputDate <= $academicEndDate) {
                            return true;
                        } else {
                            $startDate = date('d-m-Y', strtotime($academicStartDate));
                            $endDate = date('d-m-Y', strtotime($academicEndDate));

                            return $this->getMessage('Calendars.dateNotWithinPeriod', ['sprintf' => [$startDate, $endDate]]);
                        }
                    } else {
                        return true;
                    }
                },
                'last' => true
            ])
            ->add('end_date', 'compareDate', [
                'rule' => function ($value, $context) {
                    $startDate = new Date($context['data']['start_date']);
                    $endDate = new Date($context['data']['end_date']);

                    if ($endDate >= $startDate) {
                        return true;
                    } else {
                        return $this->getMessage('Calendars.endDate.compareWithStartDate');
                    }
                },
            ])
        ;
    }

    public function findIndex(Query $query, array $options)
    {
        $query->contain(['CalendarEventDates']);

        if (isset($options['querystring']) && !empty($options['querystring']['institution_id'])) {
            $academicPeriodId = $options['querystring']['academic_period_id'];

            // Adding or condition refer to https://book.cakephp.org/3.0/en/orm/query-builder.html
            $query->orwhere([
                $this->aliasField('institution_id') => -1, // all institution shown (-1)
                $this->aliasField('academic_period_id') => $academicPeriodId,
            ]);
        }

        return $query;
    }

    // POCOR-6122
    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        //for showing start date and end date on edit page
        if(!$entity->getErrors()){
            $calendarEventId = $entity->id;
            $query = $this->CalendarEventDates->find();

            if($calendarEventId){
                $calendarEventDate = $query
                ->where([
                    $this->CalendarEventDates->aliasField('calendar_event_id') => $calendarEventId
                ])
                ->enableHydration(false)
                ->toArray();

                $startDate = min($calendarEventDate)['date'];
                $endDate = max($calendarEventDate)['date'];

                $startDate = date("Y-m-d", strtotime($startDate));
                $endDate = date("Y-m-d", strtotime($endDate));
            }else{
                $startDate = date('Y-m-d');
                $endDate = date('Y-m-d');
            }

            $entity['start_date'] = $startDate;
            $entity['end_date'] = $endDate;
        }

    }

    public function beforeDelete(Event $event, Entity $entity)
    {
        $maincontroller = $this->controller;
        $controllerName = $maincontroller->getName();
        if ($controllerName == 'Institutions') {
            if ($entity->institution_id == -1) {
                $message = __('Delete operation is not allowed as there are other information linked to this record.');
                $this->Alert->error($message, ['type' => 'string', 'reset' => true]);

                $url = $this->request->referer();
                $event->stopPropagation();
                return $this->controller->redirect($url);
            }
        }
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if(empty($entity->institution_id)){
            $entity->institution_id = -1;
        }
        if(empty($entity->institution_shift_id)){
            $entity->institution_shift_id = 0;
        }
        if ($entity->id) {
            $original_entity = $this->find()->where([$this->aliasField('id') => $entity->id])->first();
        }
        if ($original_entity) {
            if ($original_entity->institution_id == -1) {
                if ($entity->institution_id != -1) {
                    $other_entity = $this->find()->where([
                        $this->aliasField('id !=') => $entity->id,
                        $this->aliasField('institution_id') => $entity->institution_id,
                        $this->aliasField('name') => $entity->name
                    ])->first();
                    if ($other_entity) {
                        $entity->id = $other_entity->id;
                        $entity->isNew(false);
                    }else {
                        $entity->id = null;
                        $entity->isNew(true);
                    }
                }
            }

        }

//        dd($options);
    }

    // POCOR-6122
    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            $startDate = new Date($entity->start_date);
            $endDate = new Date($entity->end_date);
            $endDate = $endDate->modify('+1 day');
            $interval = new DateInterval('P1D');
            $calendarEventId = $entity->id;

            $datePeriod = new DatePeriod($startDate, $interval, $endDate);
            //POCOR-6359 starts
            if(!empty($datePeriod)){
                foreach ($datePeriod as $date) {
                    $dateEntity = $this->CalendarEventDates->newEntity([
                        'calendar_event_id' => $calendarEventId,
                        'date' => $date
                    ]);
                    $this->CalendarEventDates->save($dateEntity);
                }
            }//POCOR-6359 ends
        }

        if(!$entity->isNew()){
            if ($entity->has('start_date') && $entity->has('end_date')) {
                $startDate = new Date($entity->start_date);
                $endDate = new Date($entity->end_date);
            } else {
                $dateData = $entity->calendar_event_dates;
                $startDate = min($dateData)['date'];
                $endDate = max($dateData)['date'];
            }

            $endDate = $endDate->modify('+1 day');
            $interval = new DateInterval('P1D');
            $calendarEventId = $entity->id;

            $datePeriod = new DatePeriod($startDate, $interval, $endDate);

            // delete all the date and re add the date
            $this->CalendarEventDates->deleteAll([
                'calendar_event_id' => $calendarEventId
            ]);
            //POCOR-6359 starts
            if(!empty($datePeriod)){
                foreach ($datePeriod as $date) {
                    $dateEntity = $this->CalendarEventDates->newEntity([
                        'calendar_event_id' => $calendarEventId,
                        'date' => $date
                    ]);
                    $this->CalendarEventDates->save($dateEntity);
                }
            }//POCOR-6359 ends
        }
    }
    // POCOR-6122

    public function findEdit(Query $query, array $options)
    {
        $query->contain(['CalendarEventDates', 'CalendarTypes']);

        return $query;
    }

    public function findDelete(Query $query, array $options)
    {
        $query->contain(['CalendarEventDates', 'CalendarTypes']);

        return $query;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {

        $institutionId  = $this->getQueryString('institution_id');
        $academicPeriod = ($this->request->getQuery('period')) ? $this->request->getQuery('period') : $this->AcademicPeriods->getCurrent() ;
        $calendarEventDates = TableRegistry::getTableLocator()->get('CalendarEventDates');
        $CalendarTypes = TableRegistry::getTableLocator()->get('CalendarTypes');

        if($academicPeriod != '' && isset($academicPeriod)){
            $query->select([
                $this->aliasField('id') ,
                $this->aliasField('name'),
                $this->aliasField('comment'),
                $this->aliasField('academic_period_id'),
                $this->aliasField('institution_id'),
                'start_date' => $query->func()->min($calendarEventDates->aliasField('date')),
                'end_date' => $query->func()->max($calendarEventDates->aliasField('date')),
                'type' => $CalendarTypes->aliasField('name'),
                $this->aliasField('modified_user_id'),
                $this->aliasField('modified'),
                $this->aliasField('created_user_id'),
                $this->aliasField('created')
            ])
            ->leftJoin([$calendarEventDates->getAlias() => $calendarEventDates->getTable()], [
                [$calendarEventDates->aliasField('calendar_event_id ='). $this->aliasField('id')],
            ])
            ->innerJoin([$CalendarTypes->getAlias() => $CalendarTypes->getTable()], [
                [$CalendarTypes->aliasField('id ='). $this->aliasField('calendar_type_id')],
            ])
            ->group($this->aliasField('id'))
            ->where([
                //'institution_id IS =' .$institutionId,
                $this->aliasField('academic_period_id') => $academicPeriod
            ]);
        }

    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();

        $ShiftOptionTable = TableRegistry::getTableLocator()->get('Institution.ShiftOptions');
        $institutionID = $this->getInstitutionID();
        if(empty($institutionId) && isset($this->request->getParam('pass')[1])) {
            $params = $this->paramsDecode($this->request->getParam('pass')[1]);
            $institutionId  = $params['institution_id'];
        }

        $this->field('name', ['attr' => ['label' => __('Name')]]);

        $this->fields['calendar_type_id']['type'] = 'select';
        $this->field('calendar_type_id', ['attr' => ['label' => __('Type')]]);

        $this->fields['academic_period_id']['type'] = 'select';
        $this->fields['academic_period_id']['options'] = $academicPeriodOptions;
        $this->field('academic_period_id', ['attr' => ['label' => __('Academic Period')]]);

        $this->field('start_date', ['type' => 'date','attr' => ['label' => __('Start Date')]]);

        $this->field('end_date', ['type' => 'date','attr' => ['label' => __('End Date')]]);
        //POCOR-5280 : Start
        $this->field('start_time', ['type' => 'time','attr' => ['label' => __('Start Time')]]);

        $this->field('end_time', ['type' => 'time','attr' => ['label' => __('End Time')]]);

        $this->fields['institution_shift_id']['type'] = 'select';

        $this->field('institution_shift_id', ['attr' => ['label' => __('Shift')]]);

        $this->field('institution_id', ['type' => 'hidden', 'value' => $institutionID]);
        //POCOR-5280 : End
    }
    //POCOR-5280 : Start
    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, ServerRequest $request){
        $attr['options'] = $this->AcademicPeriods->getYearList();
        $attr['onChangeReload'] = true;

        return $attr;
    }

    public function onUpdateFieldInstitutionShiftId(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($this->action == 'add' || $this->action == 'edit') {

                $ShiftOptionTable = TableRegistry::getTableLocator()->get('Institution.ShiftOptions');
                $InstitutionShiftsTable = TableRegistry::getTableLocator()->get('Institution.InstitutionShifts');
                $shiftOptions = $ShiftOptionTable->find('list')->toArray();
                $attr['options'] = $shiftOptions;
                $attr['attr']['required'] = true;
        }
        return $attr;
    }

    //POCOR-5280 : End

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        // POCOR-6122 start
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $extra['selectedAcademicPeriodOptions'] = $this->getSelectedAcademicPeriod($this->request);
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $extra['elements']['control'] = [
            'name' => 'Institution.Calendar/controls',
            'data' => [
                'encodedQueryString' => $encodedQueryString,
                'periodOptions'=> $academicPeriodOptions,
                'selectedPeriod'=> $extra['selectedAcademicPeriodOptions']
            ],
            'order' => 3
        ];

        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        // POCOR-6122 end

        $this->field('calendar_type_id', ['visible' => true, 'attr' => ['label' => __('Type')]]);
        $this->field('name', ['visible' => true, 'attr' => ['label' => __('Name')]]);
        $this->field('start_date', ['type' => 'date','attr' => ['label' => __('Start Date')]]);
        $this->field('end_date', ['type' => 'date','attr' => ['label' => __('End Date')]]);
        $this->field('shift', ['visible' => true, 'attr' => ['label' => __('Shift')]]);

        $this->field('institution', ['visible' => true, 'attr' => ['label' => __('institution')]]);
        $this->field('institution_id', ['visible' => false, 'attr' => ['label' => __('institution')]]);

        $this->field('institution_shift_id', ['visible' => false]);
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('comment', ['visible' => false]);
        $this->field('calendar_type_id', ['visible' => false]);
        $this->setFieldOrder(['type', 'name','start_time', 'end_time', 'shift']);
    }

    // POCOR-6122 start
    private function getSelectedAcademicPeriod($request)
    {
        $selectedAcademicPeriod = '';
        if ($this->action == 'index' || $this->action == 'view' || $this->action == 'edit') {
            $selectedAcademicPeriod = $request->getQuery('period');
            if(!is_numeric($selectedAcademicPeriod)){
                $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
            }
        } elseif ($this->action == 'add') {
            $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
        }

        return $selectedAcademicPeriod;
    }
    // POCOR-6122 end
    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
//        dd($buttons);
        parent::onUpdateActionButtons($event, $entity, $buttons);
//        $entity = $entity->toArray();
        if($entity->institution_id == -1){
            $entity->institution_id = $this->getInstitutionID();
        }
        unset($buttons['remove']);

        return $buttons;
    }
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // POCOR-6122 start
        if (isset($extra['selectedAcademicPeriodOptions']) && !empty($extra['selectedAcademicPeriodOptions'])) {
            $query->where([
                        $this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodOptions']
                    ], [], true); //this parameter will remove all where before this and replace it with new where.
        }
        // POCOR-6122 end

        $session = $this->request->getSession();
        $institutionId  = $this->getInstitutionID();
        if(empty($institutionId)) {
            $institutionId  = $session->read('Institution.Institutions.id');
        }
        $calendarEventDates = TableRegistry::getTableLocator()->get('CalendarEventDates');
        $institutionShifts = TableRegistry::getTableLocator()->get('Institution.ShiftOptions');//institution_shifts
        $CalendarTypes = TableRegistry::getTableLocator()->get('CalendarTypes');

        $subquery = $calendarEventDates->find()
                    ->select([
                        'calendar_event_id' => $calendarEventDates->aliasField('calendar_event_id'),
                        'min_date' => $query->func()->min('date'),
                        'max_date' => $query->func()->max('date')
                    ])
                    ->group([$calendarEventDates->aliasField('calendar_event_id')]);

        $query->select([
            $this->aliasField('id') ,
            $this->aliasField('name'),
            $this->aliasField('comment'),
            $this->aliasField('academic_period_id'),
            $this->aliasField('institution_id'),
            //'start_date' => $query->func()->min($calendarEventDates->aliasField('date')),
            //'end_date' => $query->func()->max($calendarEventDates->aliasField('date')),
            'start_date' => 'IFNULL((SELECT min_date FROM (' . $subquery->sql() . ') AS subquery WHERE subquery.calendar_event_id = ' . $this->aliasField('id') . '), "")',
            'end_date' => 'IFNULL((SELECT max_date FROM (' . $subquery->sql() . ') AS subquery WHERE subquery.calendar_event_id = ' . $this->aliasField('id') . '), "")',
            'type' => $CalendarTypes->aliasField('name'),
            'shift'=>$institutionShifts->aliasField('name'),
            'start_time' => $this->aliasField('start_time'),
            'end_time'=> $this->aliasField('end_time'),
            $this->aliasField('institution_shift_id'),
            $this->aliasField('modified_user_id'),
            $this->aliasField('modified'),
            $this->aliasField('created_user_id'),
            $this->aliasField('created')
        ])
        ->leftJoin([$institutionShifts->getAlias() => $institutionShifts->getTable()], [
            [$institutionShifts->aliasField('id ='). $this->aliasField('institution_shift_id')],
        ])
        // ->leftJoin([$calendarEventDates->getAlias() => $calendarEventDates->getTable()], [
        //     [$calendarEventDates->aliasField('calendar_event_id ='). $this->aliasField('id')],
        // ])
        ->innerJoin([$CalendarTypes->getAlias() => $CalendarTypes->getTable()], [
            [$CalendarTypes->aliasField('id ='). $this->aliasField('calendar_type_id')],
        ]);
        //->group($this->aliasField('id'))
        if(!empty($institutionId)) {
            $query->where([
                'institution_id IN (-1, ' . $institutionId . ')'
            ]);
        }
    }

    //POCOR-7696
    public function onGetInstitutionShiftId(Event $event, Entity $entity)
    {
        $ShiftOptionTable = TableRegistry::getTableLocator()->get('Institution.ShiftOptions');
        $InstitutionShiftsTable = TableRegistry::getTableLocator()->get('Institution.InstitutionShifts');

        // Correct usage of where clause
        $shiftOptionsName = $ShiftOptionTable->find()
            ->where(['id' => $entity->institution_shift_id])
            ->first()->name;
        return $shiftOptionsName;
    }

    public function onGetInstitution(Event $event, Entity $entity)
    {
//        dd($entity);
        if ($entity->institution_id == -1) {
            return __('All Institutions');
        } else {
            return $entity->institution->name;
        }
    }

}
