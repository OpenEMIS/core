<?php
namespace Report\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Log\Log;
/*POCOR-6534 starts*/
use Cake\ORM\TableRegistry;
/*POCOR-6534 ends*/
class InstitutionPositionsTable extends AppTable
{
    use OptionsTrait;

    // position filter
    const ALL_POSITION = 0;
    const POSITION_WITH_STAFF = 1;
    
    //POCOR-6614 start
    const TEACHING = 1;
    const NON_TEACHING = 0;
    const ALL_STAFF = -1;
    //POCOR-6614 end

    public function initialize(array $config)
    {
        $this->table('institution_positions');
        parent::initialize($config);
        
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('StaffPositionTitles', ['className' => 'Institution.StaffPositionTitles']);
        $this->belongsTo('StaffPositionGrades', ['className' => 'Institution.StaffPositionGrades']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->hasMany('InstitutionStaff', ['className' => 'Institution.Staff']);

        $this->addBehavior('Excel', [
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.InstitutionSecurity');
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    { 
        /*POCOR-6534 starts*/
        $identity_types = TableRegistry::get('identity_types'); //POCOR-6887
        $StaffStatuses = TableRegistry::get('Staff.StaffStatuses');
        $IdentityTypesTable    = TableRegistry::get('FieldOption.IdentityTypes');
        $UserIdentitiesTable   = TableRegistry::get('User.Identities');
        $StaffPositionTitles = TableRegistry::get('Institution.StaffPositionTitles');
        //Start POCOR-6605  JO UAT environment is not working blank birthCertificateId
        //$birthCertificateId = $IdentityTypesTable->getIdByName('Birth Certificate');
        //$birth_certificate_code_id = !empty($birthCertificateId) ? $birthCertificateId : 0;
        //End POCOR-6605
        //Start POCOR-6887
        $birth_certificate_code_id = $IdentityTypesTable->find('all')
                                     ->select('id')   
                                     ->where(['visible' => 1,'editable' => 1,'default' => 1])
                                     ->first();
        //End POCOR-6887
        // Start POCOR-7203
        $identity_id = 0;
        if(!empty($birth_certificate_code_id)){
            $identity_id = $birth_certificate_code_id->id;
        }
        // END POCOR-7203

        $requestData = json_decode($settings['process']['params']);
        $positionFilter = $requestData->position_filter;
        $teachingFilter = $requestData->teaching_filter;
        $statusFilter = $requestData->status;  //POCOR-6869

        $institution_id = $requestData->institution_id;
        $areaId = $requestData->area_education_id;
        $where = [];
        if ($institution_id != 0) {
            $where[$this->aliasField('institution_id')] = $institution_id;
        }
        if ($teachingFilter != -1) {
            $where[$StaffPositionTitles->aliasField('type')] = $teachingFilter;
        }
        $where[$this->aliasField('status_id')] = $statusFilter; //POCOR-6869
        if ($areaId != -1) {
            $where['Institutions.area_id'] = $areaId;
        }
        $query
            ->select([
                'workflow_steps_name' => 'Statuses.name',
                'position_no' => $this->aliasField('position_no'),
                'staff_position_grade_name' => 'StaffPositionGrades.name',
                'area_code' => 'Areas.code',
                'area_name' => 'Areas.name',
                'area_administratives_code' => 'AreaAdministratives.code',
                'area_administratives_name' => 'AreaAdministratives.name',
                'assignee_id' => 'Assignees.id',
                'is_homeroom' => $this->aliasField('is_homeroom'),
                'institution_code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',
                'assignee_openemis_no' => 'SecurityUsersStaff.openemis_no',
                'staff_firstname' => 'SecurityUsersStaff.first_name',
                'staff_lastname' => 'SecurityUsersStaff.last_name',
                'birth_certificate' => 'Identities.number',
                'identity_types_name' => 'identity_types.name', //POCOR-6887
                'StaffStatuses_name' => 'StaffStatuses.name', //POCOR-6887
                'InstitutionStaffs_start_date' => 'InstitutionStaffs.start_date ', //POCOR-6887
                'InstitutionStaffs_end_date' => 'InstitutionStaffs.end_date', //POCOR-6887
                'InstitutionStaffs_FTE' => 'InstitutionStaffs.FTE', //POCOR-6887
                'staff_gender_name' => 'SecurityUsersGender.name', //POCOR-6951
            ])
            ->contain([
                'Statuses' => [
                    'fields' => [
                        'Statuses.name'
                    ]
                ],
                'StaffPositionTitles' => [
                    'fields' => [
                        'StaffPositionTitles.id',
                        'StaffPositionTitles.name',
                        'StaffPositionTitles.type',
                        'StaffPositionTitles.staff_position_categories_id'
                    ]
                ],
                'StaffPositionGrades' => [
                    'fields' => [
                        'StaffPositionGrades.name'
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
                ],
                'Institutions.AreaAdministratives' => [
                    'fields' => [
                        'AreaAdministratives.name',
                        'AreaAdministratives.code'
                    ]
                ],
                'Assignees' => [
                    'fields' => [
                        'Assignees.id',
                        'Assignees.first_name',
                        'Assignees.middle_name',
                        'Assignees.third_name',
                        'Assignees.last_name',
                        'Assignees.preferred_name',
                        'Assignees.openemis_no',
                    ]
                ],
            ]);// Start POCOR-6887
            $join['InstitutionStaffs'] = [
                'type' => 'left',
                'table' => 'institution_staff',
                'conditions' => [
                    'InstitutionStaffs.institution_position_id = ' . $this->aliasField('id'),
                    'AND' => [
                        'OR' => [
                            ['InstitutionStaffs.end_date'.' IS NULL'],
                            ['InstitutionStaffs.end_date'.' > DATE(NOW())']
                        ]
                    ]
                ],
            ];// End POCOR-6887
            $join['StaffStatuses'] = [
                'type' => 'left',
                'table' => 'staff_statuses',
                'conditions' => [
                    'StaffStatuses.id = InstitutionStaffs.staff_status_id',
                ],
            ];
            // Start POCOR-6887
            $join['SecurityUsersStaff'] = [
                'type' => 'left',
                'table' => 'security_users',
                'conditions' => [
                    'SecurityUsersStaff.id = InstitutionStaffs.staff_id',
                ],
            ];
            // End POCOR-6887
            $join['SecurityUsersGender'] = [
                'type' => 'left',
                'table' => 'genders',
                'conditions' => [
                    'SecurityUsersGender.id = SecurityUsersStaff.gender_id',
                ],
            ];  //POCOR-6951
            $query->join($join)
            ->leftJoin([$UserIdentitiesTable->alias() => $UserIdentitiesTable->table()], [
                    $UserIdentitiesTable->aliasField('security_user_id = ') . ' SecurityUsersStaff.id',
                    $UserIdentitiesTable->aliasField('identity_type_id') . " = $identity_id",  // POCOR-7203  //POCOR-6887
                ])
            ->leftJoin(
                [$identity_types->alias() => $identity_types->table()],
                [
                    $identity_types->aliasField('id') . ' = '. $UserIdentitiesTable->aliasField('identity_type_id')
                ]
            )
            
            ->where([$where])
            ->order(['institution_name', 'position_no']);
            // echo "<pre>"; print_r($query->sql()); die();
       
        // if ($positionFilter == self::POSITION_WITH_STAFF) {
        //     $query = $this->onExcelBeforePositionWithStaffQuery($query);
        // }

        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) 
        {
            return $results->map(function ($row)
            {
                $row['staff_user_full_name'] = $row['staff_firstname'] . ' ' .  $row['staff_lastname'];
                return $row;
            });
        });
        /*POCOR-6534 ends*/
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $requestData = json_decode($settings['process']['params']);
        $positionFilter = $requestData->position_filter;

        $IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
        $identity = $IdentityType->getDefaultEntity();

        $settings['identity'] = $identity;

        $newFields = [];

        $newFields[] = [
            'key' => 'Statuses.name',
            'field' => 'workflow_steps_name',
            'type' => 'string',
            'label' => __('Status')
        ];

        $newFields[] = [
            'key' => 'InstitutionPositions.position_no',
            'field' => 'position_no',
            'type' => 'string',
            'label' => __('Number')
        ];

        $newFields[] = [
            'key' => 'StaffPositionTitles.id',
            'field' => 'staff_position_id',
            'type' => 'string',
            'label' => __('Title')
        ];

        $newFields[] = [
            'key' => 'StaffPositionGrades.name',
            'field' => 'staff_position_grade_name',
            'type' => 'string',
            'label' => __('Grade')
        ];

        //Start POCOR-6887
        $newFields[] = [
            'key' => 'Institutions.id',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];

        //End POCOR-6887

        $newFields[] = [
            'key' => 'Institutions.id',
            'field' => 'institution_name',  //POCOR-6887
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
            'label' => __('Area')
        ];

        $newFields[] = [
            'key' => 'AreaAdministratives.code',
            'field' => 'area_administratives_code',
            'type' => 'string',
            'label' => __('Area Administrative Code')
        ];

        $newFields[] = [
            'key' => 'AreaAdministratives.name',
            'field' => 'area_administratives_name',
            'type' => 'string',
            'label' => __('Area Administrative')
        ];

        $newFields[] = [
            'key' => 'Assignees.id',
            'field' => 'assignee_id',
            'type' => 'string',
            'label' => __('Assignee')
        ];

        $newFields[] = [
            'key' => 'InstitutionPositions.is_homeroom',
            'field' => 'is_homeroom',
            'type' => 'string',
            'label' => __('Homeroom Teacher')
        ];
        /*POCOR-6534 starts*/
        $newFields[] = [
            'key' => 'Assignees.openemis_no',
            'field' => 'assignee_openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];
        $newFields[] = [
            'key' => 'staff_user_full_name',
            'field' => 'staff_user_full_name',
            'type' => 'string',
            'label' => __('Staff Name')
        ];
        //Start POCOR-6887
        $newFields[] = [
            'key' => 'identity_types_name',
            'field' => 'identity_types_name',
            'type' => 'string',
            'label' =>  __($identity->name)
        ];

        $newFields[] = [
            'key' => 'birth_certificate',
            'field' => 'birth_certificate',
            'type' => 'string',
            'label' => __('Identity Number')
        ];

        //End POCOR-6887

        //Start POCOR-6951
        $newFields[] = [
            'key' => 'staff_gender_name',
            'field' => 'staff_gender_name',
            'type' => 'string',
            'label' => __('Staff Gender')
        ];

        $newFields[] = [
            'key' => 'StaffPositionTitles.staff_position_categories_id',
            'field' => 'staff_position_categories_id',
            'type' => 'string',
            'label' => __('Position Category')
        ];
        //End POCOR-6951
    
        /*POCOR-6534 ends*/
        if ($positionFilter == self::POSITION_WITH_STAFF) {
            $staffFields = $this->onExcelUpdatePositionWithStaffFields();
            $newFields = array_merge($newFields, $staffFields);
        }

        $fields->exchangeArray($newFields);
    }

    public function onExcelGetStaffPositionId(Event $event, Entity $entity)
    {
        $options = $this->getSelectOptions('Staff.position_types');
        $staffPositionTitleType = '';

        if ($entity->has('staff_position_title')) {
            $staffPositionCategoriesType = $entity->staff_position_title->staff_position_categories_id;
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

    private function onExcelUpdatePositionWithStaffFields()
    {
        $newFields = [];

        //Start POCOR-6887

        // $newFields[] = [
        //     'key' => 'InstitutionStaff.openemis_no',
        //     'field' => 'staff_openemis_no',
        //     'type' => 'string',
        //     'label' => __('OpenEMIS ID')
        // ];

        // $newFields[] = [
        //     'key' => 'InstitutionStaff.name',
        //     'field' => 'staff_name',
        //     'type' => 'string',
        //     'label' => __('Staff Name')
        // ];

        //End POCOR-6887

        $newFields[] = [
            'key' => 'InstitutionStaffs_start_date',
            'field' => 'InstitutionStaffs_start_date',
            'type' => 'string',
            'label' => __('Start Date')
        ];

        $newFields[] = [
            'key' => 'InstitutionStaffs_end_date',
            'field' => 'InstitutionStaffs_end_date',
            'type' => 'string',
            'label' => __('End Date')
        ];

        $newFields[] = [
            'key' => 'InstitutionStaffs_FTE',
            'field' => 'InstitutionStaffs_FTE',
            'type' => 'string',
            'label' => __('FTE')
        ];

        $newFields[] = [
            'key' => 'StaffStatuses_name',
            'field' => 'StaffStatuses_name',
            'type' => 'string',
            'label' => __('Status')
        ];

        return $newFields;
    }

    private function onExcelBeforePositionWithStaffQuery($query)
    {
        $mainTable = $this;
        $query
            ->select([
                'staff_openemis_no' => 'Users.openemis_no',
                'staff_start_date' => 'InstitutionStaff.start_date',
                'staff_end_date' => 'InstitutionStaff.end_date',
                'staff_fte' => 'InstitutionStaff.FTE',
               // 'staff_status' => 'StaffStatuses.name'
            ])//Start POCOR-6887
            ->leftJoinWith('InstitutionStaff', function ($q) {
                return $q->select([
                    'InstitutionStaff.id',
                    'InstitutionStaff.start_date',
                    'InstitutionStaff.end_date',
                    'InstitutionStaff.FTE'
                ])->where([
                    'AND' => [
                            ['InstitutionStaff.end_date'.' IS NOT NULL'],
                            ['InstitutionStaff.end_date'.' < DATE(NOW())']
                        
                    ]
                ]);
            })//End POCOR-6887
            ->leftJoinWith('InstitutionStaff', function ($q) use ($mainTable) {
                return $q->select([
                    'InstitutionStaff.id',
                    'InstitutionStaff.start_date',
                    'InstitutionStaff.end_date',
                    'InstitutionStaff.FTE'
                ])
                ->where([$mainTable->aliasField('institution_id = ') . 'InstitutionStaff.institution_id']);
            })
            ->leftJoinWith('InstitutionStaff.Users', function ($q) {
                return $q->select([
                    'Users.openemis_no',
                    'Users.first_name',
                    'Users.middle_name',
                    'Users.third_name',
                    'Users.last_name',
                    'Users.preferred_name'
                ]);
            });
            // ->leftJoinWith('InstitutionStaff.StaffStatuses', function ($q) {
            //     return $q->select([
            //         //'StaffStatuses.name'
            //     ]);
            // });

        return $query;
    }

    /** Get the feature of value of gender
        * @author Rahul Singh <rahul.singh@mail.valuecoder.com>
        *return array
        *POCOR-6951
    */


    public function onExcelGetGender(Event $event, Entity $entity)
    {
        $gender = '';
        if (!empty($entity->user->gender->name) ) {
            $gender = $entity->user->gender->name;
        }

        return $gender;
    }

    /** Get the feature of value of Position Categories
        * @author Rahul Singh <rahul.singh@mail.valuecoder.com>
        *return array
        *POCOR-6951
    */

    public function onExcelGetStaffPositionCategoriesId(Event $event, Entity $entity)
    {
        $StaffPositionCategories =  TableRegistry::get('Staff.StaffPositionCategories');
        $categories = '';

        if ($entity->has('staff_position_title')) {
            $staffPositionCategoriesType = $entity->staff_position_title->staff_position_categories_id;
        $conditions = [
            $StaffPositionCategories->aliasField('id = ') => $staffPositionCategoriesType
        ];
            $categories = $StaffPositionCategories
            ->find()
            ->select([
                $StaffPositionCategories->aliasField('name')
            ])
            ->where($conditions)
            ->first();
        }
        // echo "<pre>"; print_r($categories); die(); 

        return $categories->name;
    }
}
