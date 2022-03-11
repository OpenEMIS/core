<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
/**
 * Get details of all Employee Qualification 
 * POCOR-6598
 * @author divyaa
*/
class TrainingEmployeeQualificationTable extends AppTable
{
    private $trainingSessionResults = [];
    private $institutionDetails = [];

    CONST ACTIVE_STATUS = 1;

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

        $this->addBehavior('Excel');
        $this->addBehavior('Report.ReportList');
    }

    public function onExcelBeforeStart (Event $event, ArrayObject $settings, ArrayObject $sheets)
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
        $requestData = json_decode($settings['process']['params']);

        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('start_date'),
                $this->aliasField('staff_id'),  // this field is required to build value for Education Grades
                $this->aliasField('staff_status_id'),
                $this->aliasField('institution_id')
            ])
            ->contain([
                'Institutions' => [
                    'fields' => [
                        'code' => 'Institutions.code',
                        'Institutions.name'
                    ]
                ],
                'Institutions.Types' => [
                    'fields' => [
                        'institution_type' => 'Types.name'
                    ]
                ],
                'Institutions.Sectors' => [
                    'fields' => [
                        'institution_sector' => 'Sectors.name',
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
                
                'Users' => [
                    'fields' => [
                        'Users.id', // this field is required for Identities and IdentityTypes to appear
                        'openemis_no' => 'Users.openemis_no',
                        'first_name' => 'Users.first_name',
                        'middle_name' => 'Users.middle_name',
                        'third_name' => 'Users.third_name',
                        'last_name' => 'Users.last_name',
                        'preferred_name' => 'Users.preferred_name',
                        'number' => 'Users.identity_number',
                        'dob' => 'Users.date_of_birth', // for Date Of Birth field
                        'Users.date_of_birth',  // for Age field
                        'username' => 'Users.username'
                    ]
                ],
                /*'Users.Identities.IdentityTypes' => [
                    'fields' => [
                        'Identities.number',
                        'Identities.issue_date',
                        'Identities.expiry_date',
                        'Identities.issue_location',
                        'IdentityTypes.name',
                        'IdentityTypes.default'
                    ]
                ],*/
                'Users.Genders' => [
                    'fields' => [
                        'gender' => 'Genders.name'
                    ]
                ],
                
                'StaffStatuses' => [
                    'fields' => [
                        'StaffStatuses.name'
                    ]
                ],
                'Positions' => [
                    'fields' => [
                        'position_no' => 'Positions.position_no'
                    ]
                ],
                'Positions.StaffPositionTitles' => [
                    'fields' => [
                        'position_title' => 'StaffPositionTitles.name',
                        'position_title_teaching' => 'StaffPositionTitles.type'
                    ]
                ],
                'Positions.StaffPositionGrades' => [
                    'fields' => [
                        'position_grade' => 'StaffPositionGrades.name',
                        
                    ]
                ],
                'Positions.StaffPositionGrades' => [
                    'fields' => [
                        'position_grade' => 'StaffPositionGrades.name',
                        
                    ]
                ],
                'Positions.WorkflowSteps' => [
                    'fields' => [
                        'hiring_status' => 'WorkflowSteps.name',
                        
                    ]
                ]
            ])
			
            ->group([
                $this->aliasField('staff_id')
            ]);
        print_r($query->Sql());die('pk');
        
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) 
    {
        $IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
        $identity = $IdentityType->getDefaultEntity();

        $settings['identity'] = $identity;

        $newFields[] = [
            'key' => 'Institutions.code',
            'field' => 'code',
            'type' => 'string',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'InstitutionStaff.institution_id',
            'field' => 'institution_id',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Institutions.institution_type_id',
            'field' => 'institution_type',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Institutions.institution_sector_id',
            'field' => 'institution_sector',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Institutions.institution_provider_id',
            'field' => 'institution_provider',
            'type' => 'integer',
            'label' => '',
        ];
        //POCOR-5388 starts
        $newFields[] = [
            'key' => 'Institutions.locality_name',
            'field' => 'locality_name',
            'type' => 'string',
            'label' => __('Locality')
        ];
        //POCOR-5388 ends
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
            'key' => 'IdentityType',
            'field' => 'IdentityType',
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
            'key' => 'Institutions.area_code',
            'field' => 'area_code',
            'type' => 'string',
            'label' => __('Area Education Code')
        ];

        $newFields[] = [
            'key' => 'Institutions.area',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __('Area Education')
        ];

        $newFields[] = [
            'key' => 'Institutions.area_administrative_code',
            'field' => 'area_administrative_code',
            'type' => 'string',
            'label' => __('Area Administrative Code')
        ];

        $newFields[] = [
            'key' => 'Institutions.area_administrative_name',
            'field' => 'area_administrative_name',
            'type' => 'string',
            'label' => __('Area Administrative')
        ];

        $newFields[] = [
            'key' => 'InstitutionStaff.FTE',
            'field' => 'FTE',
            'type' => 'integer',
            'label' => 'FTE (%)',
        ];

        $newFields[] = [
            'key' => 'Users.date_of_birth',
            'field' => 'dob',
            'type' => 'date',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Age',
            'field' => 'Age',
            'type' => 'Age',
            'label' => __('Age'),
        ];

        $newFields[] = [
            'key' => 'InstitutionStaff.start_date',
            'field' => 'start_date',
            'type' => 'date',
            'label' => ''
        ];

         $newFields[] = [
            'key' => 'InstitutionStaff.end_date',
            'field' => 'end_date',
            'type' => 'date',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'InstitutionStaff.staff_type_id',
            'field' => 'staff_type_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Education.education_grades',
            'field' => 'education_grades',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'InstitutionStaff.staff_status_id',
            'field' => 'staff_status_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Positions.position_no',
            'field' => 'position_no',
            'type' => 'string',
            'label' => __('Position Number')
        ];

        $newFields[] = [
            'key' => 'Positions.position_title',
            'field' => 'position_title',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Positions.position_title_teaching',
            'field' => 'position_title_teaching',
            'type' => 'string',
            'label' => __('Teaching')
        ];

        $newFields[] = [
            'key' => 'Users.username',
            'field' => 'username',
            'type' => 'string',
            'label' => __('Username')
        ];

        $fields->exchangeArray($newFields);
    }

    public function onExcelGetIdentityType(Event $event, Entity $entity)
    {
        $userIdentities = TableRegistry::get('user_identities');
        $userIdentitiesResult = $userIdentities->find()
                ->leftJoin(['IdentityTypes' => 'identity_types'], ['IdentityTypes.id = '. $userIdentities->aliasField('identity_type_id')])
                ->select([
                    'identity_number' => $userIdentities->aliasField('number'),
                    'identity_type_name' => 'IdentityTypes.name',
                ])
                ->where([$userIdentities->aliasField('security_user_id') => $entity->user_id_id]) // POCOR-6597
                ->order([$userIdentities->aliasField('id DESC')])
                ->hydrate(false)->toArray();
                $entity->custom_identity_number = '';
                $other_identity_array = [];
                if (!empty($userIdentitiesResult)) {
                    foreach ( $userIdentitiesResult as $index => $user_identities_data ) {
                        if ($index == 0) {
                            $entity->custom_identity_number = $user_identities_data['identity_number'];
                            $entity->custom_identity_name   = $user_identities_data['identity_type_name'];
                        } else {
                            $other_identity_array[] = '(['.$user_identities_data['identity_type_name'].'] - '.$user_identities_data['identity_number'].')';
                        }
                    }
                }
        $entity->custom_identity_other_data = implode(',', $other_identity_array);
        return $entity->custom_identity_name;
    }

    public function onExcelGetAreaName(Event $event, Entity $entity)
    {
        // if ($entity->has('staff') && !empty($entity->staff)) { // POCOR-6597
            $InstitutionStaff = TableRegistry::get('Institution.Staff');
            $StaffStatuses = TableRegistry::get('Staff.StaffStatuses');
            $statuses = $StaffStatuses->findCodeList();
            $query = $InstitutionStaff->find('all')
                    ->contain(['Institutions'])
                    ->where([
                        $InstitutionStaff->aliasField('staff_id') => $entity->user_id_id, // POCOR-6597
                        $InstitutionStaff->aliasField('staff_status_id') => $statuses['ASSIGNED']
                    ])
                    ->order([
                        $InstitutionStaff->aliasField('start_date DESC'),
                        $InstitutionStaff->aliasField('created DESC')
                    ])
                    ->first();
            if (!empty($query)) {
                $AreaTable = TableRegistry::get('Area.Areas');
                $value = $AreaTable->find()->where([$AreaTable->aliasField('id') => $query->institution->area_id])->first();
                if (empty($value)) {
                    return ' - ';
                } else {
                    return $value->name;
                }
            }
        // } // POCOR-6597
    }

    

    
}
