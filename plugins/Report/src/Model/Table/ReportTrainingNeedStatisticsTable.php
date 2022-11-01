<?php

namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\Event\Event;
use App\Model\Table\AppTable;

/**
 * Get the Training Needs Statistics details in excel file
 * @author Anand Malvi <anand.malvi@mail.valuecoders.com>
 * @ticket POCOR-6591
 */
class ReportTrainingNeedStatisticsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('staff_training_needs');
        parent::initialize($config);
        $this->addBehavior('Excel', ['excludes' => []]);
        $this->addBehavior('Report.ReportList');
        
    }

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $sheets[] = [
            'name' => $this->alias(),
            'table' => $this,
            'query' => $this->find(),
            'orientation' => 'landscape'
        ];
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData        = json_decode($settings['process']['params']);
        $institution_status = $requestData->institution_status;
        $academic_period    = $requestData->academic_period_id;
        $where              = [];

        $where['AcademicPeriods.id'] = $academic_period;
        if ($institution_status > 0) {
            $where['Institutions.institution_status_id'] = $institution_status;
        }

        $join = [];
        $join['TrainingCourses'] = [
            'type' => 'inner',
            'table' => 'training_courses',
            'conditions' => ['TrainingCourses.id = ' . $this->aliasField('training_course_id')],
        ];
        $join['SecurityStaffUsers'] = [
            'type' => 'inner',
            'table' => 'security_users',
            'conditions' => ['SecurityStaffUsers.id = ' . $this->aliasField('staff_id')],
        ];
        $join['Genders'] = [
            'type' => 'inner',
            'table' => 'genders',
            'conditions' => ['Genders.id = SecurityStaffUsers.gender_id'],
        ];
        $join[' '] = [
            'type' => 'left',
            'table' => '(SELECT * FROM `institution_staff` GROUP BY institution_staff.staff_id) AS inst_staff',
            'conditions' => ['inst_staff.staff_id = ' . $this->aliasField('staff_id')],
        ];
        $join['Institutions'] = [
            'type' => 'left',
            'table' => 'institutions',
            'conditions' => ['Institutions.id = inst_staff.institution_id'],
        ];
        $join['Areas'] = [
            'type' => 'left',
            'table' => 'areas',
            'conditions' => ['Areas.id = Institutions.area_id'],
        ];

        $query->join($join)
        ->innerJoin(['AcademicPeriods' => 'academic_periods'], [
            'OR' => 
            [
                [
                    'OR' => [
                        [
                            'Institutions.date_closed IS NOT NULL',
                            'Institutions.date_opened <= AcademicPeriods.start_date',
                            'Institutions.date_closed >= AcademicPeriods.start_date'
                        ],
                        [
                            'Institutions.date_closed IS NOT NULL',
                            'Institutions.date_opened <= AcademicPeriods.end_date',
                            'Institutions.date_closed >= AcademicPeriods.end_date'
                        ],
                        [
                            'Institutions.date_closed IS NOT NULL',
                            'Institutions.date_opened >= AcademicPeriods.start_date',
                            'Institutions.date_closed <= AcademicPeriods.end_date'
                        ],
                    ]
                ],
                [
                    'OR' => [
                        [
                            'Institutions.date_closed IS NULL',
                            'Institutions.date_opened <= AcademicPeriods.end_date',
                        ]
                    ]
                ]
            ]
        ])
        ->select([
            'area_name'             => 'Areas.name',
            'training_courses_name' => 'TrainingCourses.name',
            'gender'                => 'Genders.name',
            'total_count'           => 'COUNT(*)'
        ])
        ->where($where)
        ->group([
            $this->aliasField('training_course_id'),
            'Genders.id',
            'Areas.id'
        ]);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];
        $newFields[] = [
            'key' => 'area_name',
            'field' => 'area_name',
            'type' => 'text',
            'label' => 'Area'
        ];
        $newFields[] = [
            'key' => 'training_courses_name',
            'field' => 'training_courses_name',
            'type' => 'text',
            'label' => 'Training Courses'
        ];
        $newFields[] = [
            'key' => 'gender',
            'field' => 'gender',
            'type' => 'text',
            'label' => 'Gender'
        ];
        $newFields[] = [
            'key' => 'total_count',
            'field' => 'total_count',
            'type' => 'text',
            'label' => 'Total'
        ];
        $fields->exchangeArray($newFields);
    }
}
