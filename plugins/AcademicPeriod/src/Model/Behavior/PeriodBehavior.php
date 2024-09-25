<?php
namespace AcademicPeriod\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;
use Cake\I18n\Date;

class PeriodBehavior extends Behavior
{
    public function findAcademicPeriod(Query $query, array $options)
    {
        $table = $this->_table;

        if (isset($options['academic_period_id'])) {
            $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $periodObj = $AcademicPeriods
                ->findById($options['academic_period_id'])
                ->first();
            if (!empty($periodObj)) {
                if ($periodObj->start_date instanceof Time || $periodObj->start_date instanceof Date) {
                    $startDate = $periodObj->start_date->format('Y-m-d');
                } else {
                    //$startDate = date('Y-m-d', strtotime($periodObj->start_date->format('Y-m-d')));
                    $startDate = is_object($periodObj->start_date) ? date('Y-m-d', strtotime($periodObj->start_date->format('Y-m-d'))) : date('Y-m-d', strtotime($periodObj->start_date)); //POCOR-8602
                }

                if ($periodObj->end_date instanceof Time || $periodObj->end_date instanceof Date) {
                    $endDate = $periodObj->end_date->format('Y-m-d');
                } else {
                    //$endDate = date('Y-m-d', strtotime($periodObj->end_date));
                    $endDate =  is_object($periodObj->end_date) ? date('Y-m-d', strtotime($periodObj->end_date->format('Y-m-d'))) :date('Y-m-d', strtotime($periodObj->end_date)); //POCOR-8602
                }

                if (isset($options['beforeEndDate'])) {
                    $conditions = [];
                    $conditions['OR'] = [
                        [
                            $options['beforeEndDate'] . ' <=' => $endDate
                        ]
                    ];
                    return $query->where($conditions);
                } else {
                    return $query->find('InDateRange', ['start_date' => $startDate, 'end_date' => $endDate]);
                }
            } else {
                return $query->where(['0 = 1']);
            }
        } else {
            return $query;
        }
    }

    public function findInDateRange(Query $query, array $options)
    {
        $table = $this->_table;

        // allow start_date_field and end_date_field to be defined
        $startDateField = isset($options['start_date_field']) ? $options['start_date_field'] : 'start_date';
        $endDateField = isset($options['end_date_field']) ? $options['end_date_field'] : 'end_date';

        if (isset($options['start_date']) && isset($options['end_date'])) {
            $startDate = $options['start_date'];
            $endDate = $options['end_date'];

            if ($startDate instanceof Time || $startDate instanceof Date) {
                $startDate = $startDate->format('Y-m-d');
            } else {
                $startDate = date('Y-m-d', strtotime($startDate));
            }

            $conditions = [];

            if (!empty($endDate)) {
                if ($endDate instanceof Time || $endDate instanceof Date) {
                    $endDate = $endDate->format('Y-m-d');
                } else {
                    $endDate = date('Y-m-d', strtotime($endDate));
                }
                $conditions['OR'] = [
                    'OR' => [
                        [
                            $table->aliasField($endDateField) . ' IS NOT NULL',
                            $table->aliasField($startDateField) . ' <=' => $startDate,
                            $table->aliasField($endDateField) . ' >=' => $startDate
                        ],
                        [
                            $table->aliasField($endDateField) . ' IS NOT NULL',
                            $table->aliasField($startDateField) . ' <=' => $endDate,
                            $table->aliasField($endDateField) . ' >=' => $endDate
                        ],
                        [
                            $table->aliasField($endDateField) . ' IS NOT NULL',
                            $table->aliasField($startDateField) . ' >=' => $startDate,
                            $table->aliasField($endDateField) . ' <=' => $endDate
                        ]
                    ],
                    [
                        $table->aliasField($endDateField) . ' IS NULL',
                        $table->aliasField($startDateField) . ' <=' => $endDate
                    ]
                ];
            } else {
                // For records that are still valid after the start date
                $conditions['OR'] = [
                    [
                        $table->aliasField($endDateField) . ' IS NULL'
                    ],
                    [
                        $table->aliasField($endDateField) . ' IS NOT NULL',
                        $table->aliasField($startDateField) . ' <=' => $startDate,
                        $table->aliasField($endDateField) . ' >=' => $startDate
                    ],
                    [
                        $table->aliasField($endDateField) . ' IS NOT NULL',
                        $table->aliasField($startDateField) . ' >=' => $startDate
                    ]
                ];
            }

            return $query->where($conditions);
        } else {
            return $query;
        }
    }

    public function findInPeriod(Query $query, array $options)
    {
        $table = $this->_table;
        $field = $options['field'];
        $academicPeriodId = $options['academic_period_id'];

        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $periodEntity = $AcademicPeriods->get($academicPeriodId);

        $startDate = $periodEntity->start_date->format('Y-m-d');
        $endDate = $periodEntity->end_date->format('Y-m-d');

        $conditions = [
            $table->aliasField($field) . ' <=' => $endDate,
            $table->aliasField($field) . ' >=' => $startDate
        ];

        return $query->where($conditions);
    }
}
