<?php
namespace Report\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Network\Session;

use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\Datasource\ConnectionManager;

/**
 * POCOR-6662
 * get staff detailed data
*/
class InstitutionStaffDetailedTable extends AppTable
{
    use OptionsTrait;

    public function initialize(array $config)
    {
        $this->table('institution_staff');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('Positions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'institution_position_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('StaffTypes', ['className' => 'Staff.StaffTypes']);
        $this->belongsTo('StaffStatuses', ['className' => 'Staff.StaffStatuses']);
        $this->belongsTo('SecurityGroupUsers', ['className' => 'Security.SecurityGroupUsers']);
        $this->hasMany('StaffPositionProfiles', ['className' => 'Institution.StaffPositionProfiles', 'foreignKey' => 'institution_staff_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StaffTransferOut', ['className' => 'Institution.StaffTransferOut', 'foreignKey' => 'previous_institution_staff_id', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Excel', [
            'excludes' => ['start_year', 'end_year', 'security_group_user_id'],
            'pages' => false,
            'autoFields' => false
        ]);
        $this->addBehavior('Report.InstitutionSecurity');
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
        // Setting request data and modifying fetch condition
        $requestData = json_decode($settings['process']['params']);
        $institutionId = $requestData->institution_id;
        $areaId = $requestData->area_education_id;
        $academicPeriodId = $requestData->academic_period_id;
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $InstitutionStaffTable = TableRegistry::get('institution_staff');
        $periodEntity = $AcademicPeriods->get($academicPeriodId);
        $startDate = $periodEntity->start_date->format('Y-m-d');
        $endDate = $periodEntity->end_date->format('Y-m-d');
        $getyear = $AcademicPeriods->find('all')
                   ->select(['name'=>$AcademicPeriods->aliasField('name')])
                   ->where(['id'=>$academicPeriodId])
                   ->limit(1);
        foreach($getyear->toArray() as $val) {
            $year  = $val['name'];
        }
        $custom_field = TableRegistry::get('StaffCustomField.StaffCustomFieldValues');
        $StaffCustomFields = TableRegistry::get('staff_custom_fields');
        $conditions = [];
        if ($institutionId != 0) {
            $conditions[$this->aliasField('institution_id')]=$institutionId;
        }
        if ($areaId != -1) {
            $conditions[$this->aliasField('Institutions.area_id')]=$areaId;
        }
        if (!empty($academicPeriodId)) {
                $conditions['OR'] = [
                    'OR' => [
                        [
                            $this->aliasField('end_date') . ' IS NOT NULL',
                            $this->aliasField('start_date') . ' <=' => $startDate,
                            $this->aliasField('end_date') . ' >=' => $startDate
                        ],
                        [
                            $this->aliasField('end_date') . ' IS NOT NULL',
                            $this->aliasField('start_date') . ' <=' => $endDate,
                            $this->aliasField('end_date') . ' >=' => $endDate
                        ],
                        [
                            $this->aliasField('end_date') . ' IS NOT NULL',
                            $this->aliasField('start_date') . ' >=' => $startDate,
                            $this->aliasField('end_date') . ' <=' => $endDate
                        ]
                    ],
                    [
                        $this->aliasField('end_date') . ' IS NULL',
                        $this->aliasField('start_date') . ' <=' => $endDate
                    ]
                ];
        }

        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('FTE'),
                $this->aliasField('start_date'),
                $this->aliasField('end_date'),
                $this->aliasField('staff_id'),
                $this->aliasField('institution_id'), 
                'number_value'=>$custom_field->aliasField('number_value'),
                'text_value'=>$custom_field->aliasField('text_value'),
                'decimal_value'=>$custom_field->aliasField('decimal_value'),
                'textarea_value'=>$custom_field->aliasField('textarea_value'),
                'date_value'=>$custom_field->aliasField('date_value'),
                'staff_custom_name'=>$StaffCustomFields->aliasField('name'),
                'staff_custom_description'=>$StaffCustomFields->aliasField('description'),
                
            ])
            ->contain([
                'Institutions' => [
                    'fields' => [
                        'institutions_code' => 'Institutions.code',
                        'institutions_name'=>'Institutions.name'
                    ]
                ],
                
                'Institutions.Providers' => [
                    'fields' => [
                        'institution_provider' => 'Providers.name',
                    ]
                ],
                'Institutions.Areas' => [
                    'fields' => [
                        'area_code' => 'Areas.code',
                        'area_name' => 'Areas.name'
                    ]
                ],
                'Institutions.AreaAdministratives' => [
                    'fields' => [
                        'area_administrative_code' => 'AreaAdministratives.code',
                        'area_administrative_name' => 'AreaAdministratives.name'
                    ]
                ],
                'Users' => [
                    'fields' => [
                        'Users.id',
                        'openemis_no' => 'Users.openemis_no',
                        'first_name' => 'Users.first_name',
                        'middle_name' => 'Users.middle_name',
                        'third_name' => 'Users.third_name',
                        'last_name' => 'Users.last_name',
                        'number' => 'Users.identity_number',
                    ]
                ],
                'Users.Identities.IdentityTypes' => [
                    'fields' => [
                        'Identities.number',
                        'Identities.issue_date',
                        'Identities.expiry_date',
                        'Identities.issue_location',
                        'IdentityTypes.name',
                        'IdentityTypes.default'
                    ]
                ],
                'Users.Genders' => [
                    'fields' => [
                        'gender' => 'Genders.name'
                    ]
                ],
                'Users.MainNationalities' => [
                    'fields' => [
                        'nationality' => 'MainNationalities.name'
                    ]
                ],
                
                'Positions.StaffPositionTitles' => [
                    'fields' => [
                        'position_title' => 'StaffPositionTitles.name',
                        'position_title_teaching' => 'StaffPositionTitles.type'
                    ]
                ]
            ])->leftJoin([$custom_field->alias() => $custom_field->table()],
                        [$custom_field->aliasField('staff_id  = ') . $this->aliasField('staff_id')])
            ->leftJoin([$StaffCustomFields->alias() => $StaffCustomFields->table()],
                        [$StaffCustomFields->aliasField('id  = ') . $custom_field->aliasField('staff_custom_field_id')])
            ->where($conditions);
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) use ($year) {
            return $results->map(function ($row) use ($year){
                $row['academic_period'] = $year;
                return $row;
            });
        });
    }

    public function onExcelGetUserIdentitiesDefault(Event $event, Entity $entity)
    {
        $return = [];
        if ($entity->has('user')) {
            if ($entity->user->has('identities')) {
                if (!empty($entity->user->identities)) {
                    $identities = $entity->user->identities;
                    foreach ($identities as $key => $value) {
                        if ($value->identity_type->default == 1) {
                            $return[] = $value->number;
                        }
                    }
                }
            }
        }
        return implode(', ', array_values($return));
    }

    public function onExcelGetUserIdentities(Event $event, Entity $entity)
    {
        $return = [];
        if ($entity->has('user')) {
            if ($entity->user->has('identities')) {
                if (!empty($entity->user->identities)) {
                    $identities = $entity->user->identities;
                    foreach ($identities as $key => $value) {
                        if ($value->identity_type->default == 0) {                            
                            $return[] = '([' . $value->identity_type->name . ']' . ' - ' . $value->number . ')';
                        }
                    }
                }
            }
        }

        return implode(', ', array_values($return));
    }

    

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) 
    {
        $IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
        $identity = $IdentityType->getDefaultEntity();

        $settings['identity'] = $identity;
        $newFields[] = [
            'key' => 'academic_period',
            'field' => 'academic_period',
            'type' => 'integer',
            'label' =>  __('Academic Period'),
        ];

        $newFields[] = [
            'key' => 'Institutions.institution_provider_id',
            'field' => 'institution_provider',
            'type' => 'integer',
            'label' => '',
        ];
        $newFields[] = [
            'key' => 'institutions_code',
            'field' => 'institutions_code',
            'type' => 'string',
            'label' => __('Institution Code'),
        ];
        $newFields[] = [
            'key' => 'institutions_name',
            'field' => 'institutions_name',
            'type' => 'string',
            'label' => __('Institution Name'),
        ];
        
        $newFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Users.first_name',
            'field' => 'first_name',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Users.middle_name',
            'field' => 'middle_name',
            'type' => 'string',
            'label' => ''
        ];
         $newFields[] = [
            'key' => 'Users.third_name',
            'field' => 'third_name',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Users.last_name',
            'field' => 'last_name',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Users.gender_id',
            'field' => 'gender',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Users.nationality_id',
            'field' => 'nationality',
            'type' => 'string',
            'label' => ''
        ];
        $newFields[] = [
            'key' => 'Users.identity_number',
            'field' => 'user_identities_default',
            'type' => 'string',
            'label' => __($identity->name)
        ];

        $newFields[] = [
            'key' => 'Users.identities',
            'field' => 'user_identities',
            'type' => 'string',
            'label' => __('Other Identities')
        ];

        $newFields[] = [
            'key' => 'Institutions.area_administrative_name',
            'field' => 'area_administrative_name',
            'type' => 'string',
            'label' => __('Area Administrative Name')
        ];

        $newFields[] = [
            'key' => 'InstitutionStaff.start_date',
            'field' => 'start_date',
            'type' => 'date',
            'label' => ''
        ];
        $newFields[] = [
            'key' => 'Positions.position_title',
            'field' => 'position_title',
            'type' => 'string',
            'label' => ''
        ];
        $newFields[] = [
            'key' => 'staff_custom_name',
            'field' => 'staff_custom_name',
            'type' => 'string',
            'label' => __('Custom Field Name')
        ];
        $newFields[] = [
            'key' => 'staff_custom_description',
            'field' => 'staff_custom_description',
            'type' => 'string',
            'label' => __('Custom Field Description')
        ];
        $newFields[] = [
            'key' => 'number_value',
            'field' => 'number_value',
            'type' => 'string',
            'label' => __('Number Value')
        ];
        $newFields[] = [
            'key' => 'text_value',
            'field' => 'text_value',
            'type' => 'string',
            'label' => __('Text Value')
        ];
        $newFields[] = [
            'key' => 'decimal_value',
            'field' => 'decimal_value',
            'type' => 'string',
            'label' => __('Decimal Value')
        ];
        $newFields[] = [
            'key' => 'date_value',
            'field' => 'date_value',
            'type' => 'string',
            'label' => __('Date Value')
        ];
        $newFields[] = [
            'key' => 'textarea_value',
            'field' => 'textarea_value',
            'type' => 'string',
            'label' => __('textarea Value')
        ];

        $fields->exchangeArray($newFields);
    }
}
