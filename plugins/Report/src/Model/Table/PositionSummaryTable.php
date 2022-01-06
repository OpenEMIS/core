<?php
namespace Report\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;

class PositionSummaryTable extends AppTable
{
    use OptionsTrait;

    public function initialize(array $config)
    {
        $this->table('institution_positions');
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

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $areaId = $requestData->area_education_id;
        $institutionId = $requestData->institution_id;
        $academicPeriodId = $requestData->academic_period_id;
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $periodEntity = $AcademicPeriods->get($academicPeriodId);
        $startDate = $periodEntity->start_date->format('Y-m-d');
        $endDate = $periodEntity->end_date->format('Y-m-d');
        $InstitutionStaff = TableRegistry::get('Institution.InstitutionStaff');
        $Staff = TableRegistry::get('Security.Users');
        $Genders = TableRegistry::get('User.Genders');
        $IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');
        $UserIdentities = TableRegistry::get('User.Identities');
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
                    [$InstitutionStaff->alias() => $InstitutionStaff->table()],
                    [
                        $InstitutionStaff->aliasField('institution_position_id = ') . $this->aliasField('id'),
                        $InstitutionStaff->aliasField('institution_id = ') . $this->aliasField('institution_id')
                    ]
                )
			->where([$conditions])
			->group(['institution_id',$this->aliasField('staff_position_title_id')])
			->order(['institution_name']);
			$query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
				return $results->map(function ($row) {
					
					$InstitutionStaff = TableRegistry::get('Institution.InstitutionStaff');
					$InstitutionPositions = TableRegistry::get('institution_positions');
					$Staff = TableRegistry::get('Security.Users');
					$Genders = TableRegistry::get('User.Genders');
					
					$positionData = $InstitutionPositions->find()
						->select([
							$InstitutionPositions->aliasField('id'),
							$InstitutionPositions->aliasField('staff_position_title_id')
						])
						->where([$InstitutionPositions->aliasField('institution_id') => $row['institution_id']])
						->where([$InstitutionPositions->aliasField('staff_position_title_id') => $row['staff_position_title_id']])
						->toArray();
						
					$positionIds = [];
					foreach($positionData as $data) {
						$positionIds[] = $data->id;
					}
					
					$staffData = $InstitutionStaff
						->find()
						->select([
							'gender_id' => $Genders->aliasField('id'),
							'gender' => $Genders->aliasField('name'),
						])
						->where([
							$InstitutionStaff->aliasField('institution_id') => $row['institution_id'],
							$InstitutionStaff->aliasField('institution_position_id').' IN' => $positionIds
						])
						->innerJoin(
							[$Staff->alias() => $Staff->table()],
							[
								$Staff->aliasField('id = ') . $InstitutionStaff->aliasField('staff_id')
							]
						)
						->innerJoin(
							[$Genders->alias() => $Genders->table()],
							[
								$Genders->aliasField('id = ') . $Staff->aliasField('gender_id')
							]
						)
						->toArray();
						
						foreach($staffData as $staff) {
							if($staff->gender_id == 1) {
								$male_occupancy[] = $staff->gender;
							}
							if($staff->gender_id == 2) {
								$female_occupancy[] = $staff->gender;
							}
						}
						
					$male_count = 0;
					$female_count = 0;
					if(!empty($male_occupancy)) {
						$male_count = count($male_occupancy);
					}
					if(!empty($female_occupancy)) {
						$female_count = count($female_occupancy);
					}
						
					$row['male_count'] = !empty($male_count) ? $male_count : ' 0';
					$row['female_count'] = !empty($female_count) ? $female_count : ' 0';
					return $row;
				});
			});
		
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
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

        $fields->exchangeArray($newFields);
    }

    public function onExcelGetStaffPositionId(Event $event, Entity $entity)
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

    public function onExcelGetInstitutionId(Event $event, Entity $entity)
    {
        return $entity->institution->code_name;
    }

    public function onExcelGetIsHomeroom(Event $event, Entity $entity)
    {
        $options = $this->getSelectOptions('general.yesno');
        return $options[$entity->is_homeroom];
    }

    public function onExcelGetStaffName(Event $event, Entity $entity)
    {
        if ($entity->has('_matchingData')) {
            return $entity->_matchingData['Users']->name;
        }
        return '';
    }
}
