<?php
namespace App\Model\Table;

use ArrayObject;
use Cake\I18n\Date;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use Cake\Event\Event;

class CalendarsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('calendar_events');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('CalendarTypes', ['className' => 'CalendarTypes', 'foreignKey' => 'calendar_type_id']);

        $this->hasMany('CalendarEventDates', ['className' => 'CalendarEventDates', 'dependent' => true, 'cascadeCallbacks' => true]);
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
}
