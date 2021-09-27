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

        $d = $this->hasMany('CalendarEventDates', ['className' => 'CalendarEventDates', 'dependent' => true, 'cascadeCallbacks' => true]);

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
            'key'   => 'calendar_type_id',
            'field' => 'calendar_type_id',
            'type'  => 'string',
            'label' => __('Calendar Type')
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

        // dump($fields); die;

        $fields->exchangeArray($extraField);
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $session = $this->request->session();
        $institutionId  = $session->read('Institution.Institutions.id');

        // $query = 'SELECT `Calendars`.`id` AS `Calendars__id`, `Calendars`.`name` AS `Calendars__name`, `Calendars`.`comment` AS `Calendars__comment`, `Calendars`.`academic_period_id` AS `Calendars__academic_period_id`, `Calendars`.`institution_id` AS `Calendars__institution_id`, MIN(calendar_event_dates.date) AS `start_date`, MAX(calendar_event_dates.date) AS `end_date`, `Calendars`.`calendar_type_id` AS `Calendars__calendar_type_id`, `Calendars`.`modified_user_id` AS `Calendars__modified_user_id`, `Calendars`.`modified` AS `Calendars__modified`, `Calendars`.`created_user_id` AS `Calendars__created_user_id`, `Calendars`.`created` AS `Calendars__created` FROM `calendar_events` `Calendars` INNER JOIN `calendar_event_dates` ON calendar_event_dates.calendar_event_id = Calendars.id GROUP BY Calendars.id';
        
        $query
        ->select([
            'Calendars.id' , 'Calendars.name', 
            'Calendars.comment', 'Calendars.academic_period_id', 'Calendars.institution_id',
            'start_date' => $query->func()->min('calendar_event_dates.date'),
			'end_date' => $query->func()->max('calendar_event_dates.date'),
            'Calendars.calendar_type_id', 'Calendars.modified_user_id', 'Calendars.modified', 
            'Calendars.created_user_id','Calendars.created'
        ])
        ->innerJoin(
            ['calendar_event_dates' => 'calendar_event_dates'],
            ['calendar_event_dates.calendar_event_id = ' . $this->aliasField('id')]
        )
        ->group($this->aliasField('id'))
        ->where([
            'institution_id =' .$institutionId
        ]);

        // dump($query); die;
        
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
}
