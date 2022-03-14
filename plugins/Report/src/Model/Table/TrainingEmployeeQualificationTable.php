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

class TrainingEmployeeQualificationTable extends AppTable
{

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
        $qualification = TableRegistry::get('staff_qualifications');
        print_r($qualification);die;
        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('start_date'),
                $this->aliasField('staff_id'),  // this field is required to build value for Education Grades
                $this->aliasField('staff_type_id'),
                $this->aliasField('staff_status_id'),
                $this->aliasField('institution_id'),
                //'qualification_name' => 'StaffQualifications.name',
                //'document_no' => 'StaffQualifications.document_no',
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
                //POCOR-5388 ends
                'Users' => [
                    'fields' => [
                        'Users.id', // this field is required for Identities and IdentityTypes to appear
                        'openemis_no' => 'Users.openemis_no',
                        'first_name' => 'Users.first_name',
                        'middle_name' => 'Users.middle_name',
                        'third_name' => 'Users.third_name',
                        'last_name' => 'Users.last_name',
                    ]
                ],
                'Users.Genders' => [
                    'fields' => [
                        'gender' => 'Genders.name'
                    ]
                ],
                
                'StaffTypes' => [
                    'fields' => [
                        'StaffTypes.name'
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
                'Positions.WorkflowSteps' => [
                    'fields' => [
                        'workflow_step' => 'WorkflowSteps.name',
                        
                    ]
                ]
            ])
            ->leftJoin(
                [$qualification->alias() => $qualification->table()],
                [$qualification->aliasField('staff_id = ') . $this->aliasfield('staff_id')]
            )
            
        print_r($query->Sql());die;
    
    }

    

    
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) 
    {

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
            'key' => 'InstitutionStaff.start_date',
            'field' => 'start_date',
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


        $fields->exchangeArray($newFields);
    }
}
