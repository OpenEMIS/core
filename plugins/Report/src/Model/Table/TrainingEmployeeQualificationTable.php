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
        $qualificationtitle = TableRegistry::get('FieldOption.QualificationTitles');
        $qualificationlevel = TableRegistry::get('FieldOption.QualificationLevels');
        $qualificationCountry = TableRegistry::get('FieldOption.Countries');
        $educationFieldOfStudy = TableRegistry::get('Education.EducationFieldOfStudies');
        //print_r($qualification);die;
        $query
            ->select([
                $this->aliasField('id'),
               'date_of_hiring' => $this->aliasField('start_date'),
                $this->aliasField('staff_id'),  // this field is required to build value for Education Grades
                $this->aliasField('staff_type_id'),
                $this->aliasField('staff_status_id'),
                $this->aliasField('institution_id'),
                'document_no' => 'staff_qualifications.document_no',
                'graduate_year' => 'staff_qualifications.graduate_year',
                'qualification_institution' => 'staff_qualifications.qualification_institution',
                'avg' => 'staff_qualifications.gpa',
                'EducationFieldOfStudies' => 'EducationFieldOfStudies.name',
                'Qualification_title' => 'QualificationTitles.name',
                'country' => 'Countries.name',
                'level' => 'QualificationLevels.name',
            ])
            ->contain([
                'Institutions' => [
                    'fields' => [
                        'code' => 'Institutions.code',
                        'name'=>'Institutions.name'
                    ]
                ],
                /*'Institutions.Sectors' => [
                    'fields' => [
                        'institution_sector' => 'Sectors.name',
                    ]
                ],*/
                'Institutions.Providers' => [
                    'fields' => [
                        'institution_provider' => 'Providers.name',
                    ]
                ],
                'Institutions.Areas' => [
                    'fields' => [
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
                        'address' => 'Users.address',
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
                        'employment_status'=>'StaffStatuses.name'
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
                    ]
                ],
                'Positions.StaffPositionGrades' => [
                    'fields' => [
                        'functional_class' => 'StaffPositionGrades.name',
                        
                    ]
                ],
                /*'Positions.WorkflowSteps' => [
                    'fields' => [
                        'status_hiring' => 'WorkflowSteps.name',
                        
                    ]
                ]*/
            ])
            ->leftJoin(
                [$qualification->alias() => $qualification->table()],
                [$qualification->aliasField('staff_id = ') . $this->aliasfield('staff_id')]
            )->leftJoin(
                [$qualificationtitle->alias() => $qualificationtitle->table()],
                [$qualificationtitle->aliasField('id = ') . $qualification->aliasField('qualification_title_id')]
            )->leftJoin(
                [$qualificationCountry->alias() => $qualificationCountry->table()],
                [$qualificationCountry->aliasField('id = ') . $qualification->aliasField('qualification_country_id')]
            )->leftJoin(
                [$educationFieldOfStudy->alias() => $educationFieldOfStudy->table()],
                [$educationFieldOfStudy->aliasField('id = ') . $qualification->aliasField('education_field_of_study_id')]
            )->leftJoin(
                [$qualificationlevel->alias() => $qualificationlevel->table()],
                [$qualificationlevel->aliasField('id = ') . $qualificationtitle->aliasField('qualification_level_id')]
            );
            
       // print_r($query->Sql());die;
    
    }

    

    
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) 
    {

        $newFields[] = [
            'key' => 'institution_provider',
            'field' => 'institution_provider',
            'type' => 'string',
            'label' => __('Institution Provider')
        ];
        $newFields[] = [
            'key' => 'area_name',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __('Area Education')
        ];
        $newFields[] = [
            'key' => 'Institutions.code',
            'field' => 'code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];
        $newFields[] = [
            'key' => 'Institutions.name',
            'field' => 'name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];

    
        $newFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];
        $newFields[] = [
            'key' => 'identity_type',
            'field' => 'identity_type',
            'type' => 'string',
            'label' => __('Identity Type')
        ];
        $newFields[] = [
            'key' => 'identity_number',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => __('Identity Number')
        ];
        $newFields[] = [
            'key' => 'other_identity',
            'field' => 'other_identity',
            'type' => 'string',
            'label' => __('Other Identities')
        ];

        $newFields[] = [
            'key' => 'Users.first_name',
            'field' => 'first_name',
            'type' => 'string',
            'label' => __('First Name')
        ];

        $newFields[] = [
            'key' => 'Users.middle_name',
            'field' => 'middle_name',
            'type' => 'string',
            'label' => __('Second Name')
        ];
        $newFields[] = [
            'key' => 'Users.third_name',
            'field' => 'third_name',
            'type' => 'string',
            'label' => __('Third Name')
        ];

        $newFields[] = [
            'key' => 'Users.last_name',
            'field' => 'last_name',
            'type' => 'string',
            'label' => __('Last Name')
        ];
        $newFields[] = [
            'key' => 'Users.address',
            'field' => 'address',
            'type' => 'string',
            'label' => __('Address')
        ];

        $newFields[] = [
            'key' => 'Users.gender_id',
            'field' => 'gender',
            'type' => 'string',
            'label' => __('Gender')
        ];

        $newFields[] = [
            'key' => 'functional_class',
            'field' => 'functional_class',
            'type' => 'string',
            'label' => __('Functional class')
        ];

        /*$newFields[] = [
            'key' => 'Institutions.area',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __('Area Education')
        ];*/

        $newFields[] = [
            'key' => 'employment_status',
            'field' => 'employment_status',
            'type' => 'string',
            'label' => __('Employment Status')
        ];

        /*$newFields[] = [
            'key' => 'status_of_hiring',
            'field' => 'status_of_hiring',
            'type' => 'date',
            'label' => __('Date Of Hiring')
        ];*/ 

        $newFields[] = [
            'key' => 'InstitutionStaff.start_date',
            'field' => 'start_date',
            'type' => 'date',
            'label' => __('Date Of Hiring')
        ];

        $newFields[] = [
            'key' => 'level',
            'field' => 'level',
            'type' => 'string',
            'label' => __('Level')
        ];
        $newFields[] = [
            'key' => 'country',
            'field' => 'country',
            'type' => 'string',
            'label' => __('Country')
        ];
        $newFields[] = [
            'key' => 'qualification_institution',
            'field' => 'qualification_institution',
            'type' => 'string',
            'label' => __('Qualification Institution')
        ];

        $newFields[] = [
            'key' => 'document_no',
            'field' => 'document_no',
            'type' => 'integer',
            'label' => __('Document Number')
        ];
        $newFields[] = [
            'key' => 'graduate_year',
            'field' => 'graduate_year',
            'type' => 'integer',
            'label' => __('Graduate Year')
        ];
        $newFields[] = [
            'key' => 'avg',
            'field' => 'avg',
            'type' => 'integer',
            'label' => __('The Avg')
        ];

        /*$newFields[] = [
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
        ];*/

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
            ->where([$userIdentities->aliasField('security_user_id') => $entity->trainee_id])
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
    public function onExcelGetIdentityNumber(Event $event, Entity $entity)
    {
        return $entity->custom_identity_number;
    }
    public function onExcelGetOtherIdentity(Event $event, Entity $entity)
    {
        return $entity->custom_identity_other_data;
    }
}
