<?php

namespace Report\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;

class PositionSummaryTable extends AppTable
{
    use OptionsTrait;

    public function initialize(array $config): void
    {
        $this->setTable('institution_positions');
        parent::initialize($config);

        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('StaffPositionTitles', ['className' => 'Institution.StaffPositionTitles']);
        $this->belongsTo('StaffPositionGrades', ['className' => 'Institution.StaffPositionGrades']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Assignees', ['className' => 'Security.Users']);
        $this->hasMany('InstitutionStaff', ['className' => 'Institution.Staff']);

        $this->addBehavior('Excel', [
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.InstitutionSecurity');
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $areaId = $requestData->area_education_id;
        $institutionId = $requestData->institution_id;
        $academicPeriodId = $requestData->academic_period_id;
        $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $periodEntity = $AcademicPeriods->get($academicPeriodId);
        $startDate = $periodEntity->start_date->format('Y-m-d');
        $endDate = $periodEntity->end_date->format('Y-m-d');
        $InstitutionStaff = TableRegistry::getTableLocator()->get('Institution.InstitutionStaff');
        $Staff = TableRegistry::getTableLocator()->get('Security.Users');
        $Genders = TableRegistry::getTableLocator()->get('User.Genders');
        $IdentityTypes = TableRegistry::getTableLocator()->get('FieldOption.IdentityTypes');
        $UserIdentities = TableRegistry::getTableLocator()->get('User.Identities');
        $conditions = [];
        if (!empty($academicPeriodId)) {
            $conditions['OR'] = [
                'OR' => [
                    [
                        $InstitutionStaff->aliasField('end_date') . ' IS NOT NULL',
                        $InstitutionStaff->aliasField('start_date') . ' <=' => $startDate,
                        $InstitutionStaff->aliasField('end_date') . ' >=' => $startDate
                    ],
                    [
                        $InstitutionStaff->aliasField('end_date') . ' IS NOT NULL',
                        $InstitutionStaff->aliasField('start_date') . ' <=' => $endDate,
                        $InstitutionStaff->aliasField('end_date') . ' >=' => $endDate
                    ],
                    [
                        $InstitutionStaff->aliasField('end_date') . ' IS NOT NULL',
                        $InstitutionStaff->aliasField('start_date') . ' >=' => $startDate,
                        $InstitutionStaff->aliasField('end_date') . ' <=' => $endDate
                    ]
                ],
                [
                    $InstitutionStaff->aliasField('end_date') . ' IS NULL',
                    $InstitutionStaff->aliasField('start_date') . ' <=' => $endDate
                ]
            ];
        }
        if (!empty($institutionId) && $institutionId > 0) {
            $conditions['Institutions.id'] = $institutionId;
        }
        if (!empty($areaId) && $areaId != -1) {
            $conditions['Institutions.area_id'] = $areaId;
        }
        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('staff_position_title_id'),
                'institution_id' => 'Institutions.id',
                'institution_code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',
                'area_code' => 'Areas.code',
                'area_name' => 'Areas.name',
                'is_homeroom' => $InstitutionStaff->aliasField('is_homeroom'), //POCOR-7229
            ])
            ->contain([
                'StaffPositionTitles' => [
                    'fields' => [
                        'StaffPositionTitles.id',
                        'StaffPositionTitles.name',
                        'StaffPositionTitles.type'
                    ]
                ],
                'Institutions' => [
                    'fields' => [
                        'Institutions.id',
                        'Institutions.name',
                        'Institutions.code'
                    ]
                ],
                'Institutions.Areas' => [
                    'fields' => [
                        'Areas.name',
                        'Areas.code'
                    ]
                ]
            ])
            ->leftJoin(
                [$InstitutionStaff->getAlias() => $InstitutionStaff->getTable()],
                [
                    $InstitutionStaff->aliasField('institution_position_id = ') . $this->aliasField('id'),
                    $InstitutionStaff->aliasField('institution_id = ') . $this->aliasField('institution_id')
                ]
            )
            ->where([$conditions])
            ->andWhere([$this->aliasField('institution_id !=') => 0]) //POCOR-6777
            ->group(['institution_id', $this->aliasField('staff_position_title_id')])
            ->order(['institution_name']);
        //POCOR-9124 start
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                $InstitutionStaff = TableRegistry::getTableLocator()->get('Institution.InstitutionStaff');
                $InstitutionPositions = TableRegistry::getTableLocator()->get('institution_positions');
                $Staff = TableRegistry::getTableLocator()->get('Security.Users');
                $Genders = TableRegistry::getTableLocator()->get('User.Genders');

                $male_occupancy = [];
                $female_occupancy = [];

                if (!isset($row['institution_id'])) {
                    $row['male_count'] = '0';
                    $row['female_count'] = '0';
                    return $row;
                }

                $instCond = is_null($row['institution_id'])
                    ? [$InstitutionPositions->aliasField('institution_id') . ' IS' => null]
                    : [$InstitutionPositions->aliasField('institution_id') => $row['institution_id']];

                $titleCond = is_null($row['staff_position_title_id'])
                    ? [$InstitutionPositions->aliasField('staff_position_title_id') . ' IS' => null]
                    : [$InstitutionPositions->aliasField('staff_position_title_id') => $row['staff_position_title_id']];

                $positionData = $InstitutionPositions->find()
                    ->select([
                        $InstitutionPositions->aliasField('id'),
                        $InstitutionPositions->aliasField('staff_position_title_id')
                    ])
                    ->where(array_merge($instCond, $titleCond))
                    ->toArray();

                $positionIds = collection($positionData)->extract('id')->toList();

                
                $institutionIdCond = is_null($row['institution_id'])
                    ? [$InstitutionStaff->aliasField('institution_id') . ' IS' => null]
                    : [$InstitutionStaff->aliasField('institution_id') => $row['institution_id']];

                $baseQuery = $InstitutionStaff
                    ->find()
                    ->select([
                        'gender_id' => $Genders->aliasField('id'),
                        'gender' => $Genders->aliasField('name'),
                    ])
                    ->where($institutionIdCond)
                    ->innerJoin(
                        [$Staff->getAlias() => $Staff->getTable()],
                        [
                            $Staff->aliasField('id = ') . $InstitutionStaff->aliasField('staff_id')
                        ]
                    )
                    ->innerJoin(
                        [$Genders->getAlias() => $Genders->getTable()],
                        [
                            $Genders->aliasField('id = ') . $Staff->aliasField('gender_id')
                        ]
                    );

                if (!empty($positionIds)) {
                    $baseQuery->andWhere([
                        $InstitutionStaff->aliasField('institution_position_id') . ' IN' => $positionIds
                    ]);
                }

                $staffData = $baseQuery->toArray();

                foreach ($staffData as $staff) {
                    if ($staff->gender_id == 1) {
                        $male_occupancy[] = $staff->gender;
                    } elseif ($staff->gender_id == 2) {
                        $female_occupancy[] = $staff->gender;
                    }
                }

                $row['male_count'] = count($male_occupancy);
                $row['female_count'] = count($female_occupancy);

                return $row;
            });
        });
         //POCOR-9124 end
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'Institutions.code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];

        $newFields[] = [
            'key' => 'Institutions.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];

        $newFields[] = [
            'key' => 'Areas.code',
            'field' => 'area_code',
            'type' => 'string',
            'label' => __('Area Code')
        ];

        $newFields[] = [
            'key' => 'Areas.name',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __('Area Name')
        ];

        $newFields[] = [
            'key' => 'StaffPositionTitles.id',
            'field' => 'staff_position_id',
            'type' => 'string',
            'label' => __('Position Title')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'male_count',
            'type' => 'string',
            'label' => __('Male')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'female_count',
            'type' => 'string',
            'label' => __('Female')
        ];
        $newFields[] = [
            'key' => 'is_homeroom',
            'field' => 'is_homeroom',
            'type' => 'string',
            'label' => __('Is Homeroom')
        ]; //POCOR-7229

        $fields->exchangeArray($newFields);
    }

    public function onExcelGetStaffPositionId(EventInterface $event, Entity $entity)
    {
        $options = $this->getSelectOptions('Staff.position_types');
        $staffPositionTitleType = '';

        if ($entity->has('staff_position_title')) {
            $staffPositionTitleType = $entity->staff_position_title->name;
            $staffType = $entity->staff_position_title->type;
            $type = array_key_exists($staffType, $options) ? $options[$staffType] : '';

            if (!empty($type)) {
                $staffPositionTitleType .= ' - ' . $type;
            }
        } else {
            Log::write('debug', $entity->name . ' has no staff_position_title...');
        }

        return $staffPositionTitleType;
    }

    public function onExcelGetInstitutionId(EventInterface $event, Entity $entity)
    {
        return $entity->institution->code_name;
    }

    public function onExcelGetIsHomeroom(EventInterface $event, Entity $entity)
    {
        $options = $this->getSelectOptions('general.yesno');
        return $options[$entity->is_homeroom];
    }

    public function onExcelGetStaffName(EventInterface $event, Entity $entity)
    {
        if ($entity->has('_matchingData')) {
            return $entity->_matchingData['Users']->name;
        }
        return '';
    }
}
