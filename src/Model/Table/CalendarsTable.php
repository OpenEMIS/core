<?php
namespace App\Model\Table;

use ArrayObject;
use Cake\I18n\Date;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;

class CalendarsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('calendar_events');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('CalendarTypes', ['className' => 'CalendarTypes', 'foreignKey' => 'calendar_type_id']);

        $this->hasMany('CalendarEventDates', ['className' => 'CalendarEventDates', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('Excel', [
            'excludes' => ['comment', 'security_group_user_id'],
            'pages' => ['index']
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
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

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $extraField[] = [
            'key'   => 'type',
            'field' => 'type',
            'type'  => 'string',
            'label' => __('Type')
        ];

        $extraField[] = [
            'key'   => 'name',
            'field' => 'name',
            'type'  => 'string',
            'label' => __('Name')
        ];

        // $extraField[] = [
        //     'key'   => 'comment',
        //     'field' => 'comment',
        //     'type'  => 'string',
        //     'label' => __('Comment')
        // ];

        $extraField[] = [
            'key'   => 'start_date',
            'field' => 'start_date',
            'type'  => 'date',
            'label' => __('Start Date')
        ];

        $extraField[] = [
            'key'   => 'end_date',
            'field' => 'end_date',
            'type'  => 'date',
            'label' => __('End Date')
        ];

        $fields->exchangeArray($extraField);
    }

    // POCOR-6122 update code
    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $session = $this->request->session();
        $institutionId  = $session->read('Institution.Institutions.id');

        $calendarEventDates = TableRegistry::get('calendar_event_dates');
        $CalendarTypes = TableRegistry::get('CalendarTypes');

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
        ->innerJoin([$calendarEventDates->alias() => $calendarEventDates->table()], [
            [$calendarEventDates->aliasField('calendar_event_id ='). $this->aliasField('id')],
        ])
        ->innerJoin([$CalendarTypes->alias() => $CalendarTypes->table()], [
            [$CalendarTypes->aliasField('id ='). $this->aliasField('calendar_type_id')],
        ])
        ->group($this->aliasField('id'))
        ->where([
            'institution_id =' .$institutionId
        ]);
        
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();

        $this->field('name', ['attr' => ['label' => __('Name')]]);

        $this->fields['calendar_type_id']['type'] = 'select';
        $this->field('calendar_type_id', ['attr' => ['label' => __('Type')]]);

        $this->fields['academic_period_id']['type'] = 'select';
        $this->fields['academic_period_id']['options'] = $academicPeriodOptions;
        $this->field('academic_period_id', ['attr' => ['label' => __('Academic Period')]]);

        $this->field('start_date', ['type' => 'date','attr' => ['label' => __('Start Date')]]);

        $this->field('end_date', ['type' => 'date','attr' => ['label' => __('End Date')]]);
    }

   
    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        // POCOR-6122 start
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $extra['selectedAcademicPeriodOptions'] = $this->getSelectedAcademicPeriod($this->request);

        $extra['elements']['control'] = [
            'name' => 'Institution.Calendar/controls',
            'data' => [
                'periodOptions'=> $academicPeriodOptions,
                'selectedPeriod'=> $extra['selectedAcademicPeriodOptions']
            ],
            'order' => 3
        ];

        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        // POCOR-6122 end

        $this->field('type', ['visible' => true, 'attr' => ['label' => __('Type')]]);
        $this->field('name', ['visible' => true, 'attr' => ['label' => __('Name')]]);
        $this->field('start_date', ['type' => 'date','attr' => ['label' => __('Start Date')]]);
        $this->field('end_date', ['type' => 'date','attr' => ['label' => __('End Date')]]);
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('comment', ['visible' => false]);
        $this->field('calendar_type_id', ['visible' => false]);
        //$this->setFieldOrder(['generated_on', 'generated_by']);
    }

    // POCOR-6122 start
    private function getSelectedAcademicPeriod($request)
    {
        $selectedAcademicPeriod = '';

        if ($this->action == 'index' || $this->action == 'view' || $this->action == 'edit') {
            if (isset($request->query) && array_key_exists('period', $request->query)) {
                $selectedAcademicPeriod = $request->query['period'];
            } else {
                $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
            }
        } elseif ($this->action == 'add') {
            $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
        }

        return $selectedAcademicPeriod;
    } 
    // POCOR-6122 end

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // POCOR-6122 start
        if (array_key_exists('selectedAcademicPeriodOptions', $extra)) {
            $query->where([
                        $this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodOptions']
                    ], [], true); //this parameter will remove all where before this and replace it with new where.
        }
        // POCOR-6122 end

        $session = $this->request->session();
        $institutionId  = $session->read('Institution.Institutions.id');

        $calendarEventDates = TableRegistry::get('calendar_event_dates');
        $CalendarTypes = TableRegistry::get('CalendarTypes');

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
        ->innerJoin([$calendarEventDates->alias() => $calendarEventDates->table()], [
            [$calendarEventDates->aliasField('calendar_event_id ='). $this->aliasField('id')],
        ])
        ->innerJoin([$CalendarTypes->alias() => $CalendarTypes->table()], [
            [$CalendarTypes->aliasField('id ='). $this->aliasField('calendar_type_id')],
        ])
        ->group($this->aliasField('id'))
        ->where([
            'institution_id =' .$institutionId
        ]);
    }
    // POCOR-6122 update code
}
