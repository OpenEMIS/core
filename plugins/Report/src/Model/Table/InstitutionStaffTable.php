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

class InstitutionStaffTable extends AppTable
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
        $this->addBehavior('Report.AreaList');//POCOR-7794
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
        $statusId = $requestData->status;
        $typeId = $requestData->type;
        $institutionId = $requestData->institution_id;
        $areaId = $requestData->area_education_id;
        $areaLevelId = $requestData->area_level_id;//POCOR-7794
        $academicPeriodId = $requestData->academic_period_id;

        if ($statusId != 0) {
            $query->where([
                $this->aliasField('staff_status_id') => $statusId
            ]);
        }

        if ($typeId != 0) {
            $query->where([
                $this->aliasField('staff_type_id') => $typeId
            ]);
        }
        if ($institutionId != 0) {
            $query->where([
                $this->aliasField('institution_id') => $institutionId
            ]);
        }
        //POCOR-7794 start
        $areaList = [];
        if (
            $areaLevelId > 1 && $areaId > 1
        ) {
            $areaList = $this->getAreaList($areaLevelId, $areaId);
        } elseif ($areaLevelId > 1) {

            $areaList = $this->getAreaList($areaLevelId, 0);
        } elseif ($areaId > 1) {
            $areaList = $this->getAreaList(0, $areaId);
        }
        if (!empty($areaList)) {
            $query->where(['Institutions.area_id IN' => $areaList]);
        }
        //POCOR-7794 end

        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('FTE'),
                $this->aliasField('start_date'),
                $this->aliasField('end_date'),
                $this->aliasField('staff_id'),  // this field is required to build value for Education Grades
                $this->aliasField('staff_type_id'),
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
                'Institutions.AreaAdministratives' => [
                    'fields' => [
                        'area_administrative_code' => 'AreaAdministratives.code',
                        'area_administrative_name' => 'AreaAdministratives.name'
                    ]
                ],//POCOR-5388 starts
                'Institutions.Localities' => [
                    'fields' => [
                        'locality_name' => 'Localities.name'
                    ]
                ],//POCOR-5388 ends
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
                'Users.Contacts' => [
                    'fields' => [
                        'Contacts.value',
                        'Contacts.contact_type_id',
                        'Contacts.security_user_id'
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
                ]
            ]);
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) use ($academicPeriodId) {
            return $results->map(function ($row) use ($academicPeriodId){
                $row['academic_period_id'] = $academicPeriodId;

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

    public function onExcelGetFTE(Event $event, Entity $entity)
    {
        return $entity->FTE*100;
    }

    public function onExcelRenderAge(Event $event, Entity $entity, $attr)
    {
        $age = '';
        if ($entity->has('user')) {
            if ($entity->user->has('date_of_birth')) {
                if (!empty($entity->user->date_of_birth)) {
                    $dateOfBirth = $entity->user->date_of_birth->format('Y-m-d');
                    $today = date('Y-m-d');
                    $age = date_diff(date_create($dateOfBirth), date_create($today))->y;
                }
            }
        }
        return $age;
    }
    

    public function onExcelGetEducationGrades(Event $event, Entity $entity)
    {
        $grades = [];

        if ($entity->has('staff_id')) {
            $staffId = $entity->staff_id;
            $academicPeriodId = $entity->academic_period_id;
            $ClassesTable = TableRegistry::get('Institution.InstitutionClasses');
            $ClassesSecondaryStaffTable = TableRegistry::get('Institution.InstitutionClassesSecondaryStaff');
            //Start:POCOR-6714
            $EducationGrades = TableRegistry::get('education_grades');
            $subStaffTable = TableRegistry::get('institution_subject_staff');
            $InsSubTable = TableRegistry::get('institution_subjects');
            $AcademicTable = TableRegistry::get('academic_periods');
	     
	        $AcademicData = $AcademicTable->find()->where(['id'=> $entity->academic_period_id])->first();
            $startDateYear = $AcademicData->start_year;
            $endDateYear = $AcademicData->end_year; // POCOR-7544
            $edGrade = [];
            if($entity->end_date == null){
                $subStaffData = $subStaffTable->find()->where(['staff_id'=>$staffId,'institution_id'=>$entity->institution_id,'start_date >' => "$startDateYear-01-01",'start_date <' => "$startDateYear-12-31"])->toArray();
            }else{
                $startDateYear = $AcademicData->end_year;
                $subStaffData = $subStaffTable->find()->where(['staff_id'=>$staffId,'institution_id'=>$entity->institution_id,'start_date >' => "$startDateYear-01-01",'end_date <' => "$endDateYear-12-31"])->toArray();
            }
            foreach ($subStaffData as $key => $value) { 
                $insSubData = $InsSubTable->find()->where(['id' => $value->institution_subject_id])->first();
                $EducationGradeData = $EducationGrades->find()->where(['id'=> $insSubData->education_grade_id])->first();
                $edGrade[$key] = $EducationGradeData->name;
            }
            //END:POCOR-6714
            
            //echo "<pre>"; print_r($value->start_date->format('Y')); 
            //die();
            // $connection = ConnectionManager::get('default');
            // $institutionClassesData = $connection->execute("SELECT academic_period_id,homeroom_or_secondary.institution_class_id,homeroom_or_secondary.staff_id,education_grade_id FROM
            //     institution_classes 
            //     INNER JOIN
            //     (SELECT id institution_class_id,staff_id FROM institution_classes

            //     UNION
            //     SELECT institution_class_id,secondary_staff_id staff_id FROM institution_classes_secondary_staff) homeroom_or_secondary
            //     ON institution_classes.id = homeroom_or_secondary.institution_class_id
            //     INNER JOIN institution_class_grades ON institution_class_grades.institution_class_id = institution_classes.id
            //     WHERE homeroom_or_secondary.staff_id = '".$staffId."' AND institution_classes.academic_period_id = '".$academicPeriodId."'")->fetchAll(\PDO::FETCH_ASSOC);
            //  $query = [];
            // foreach ($institutionClassesData as $key => $value) {
            //     $query [$key] = $EducationGrades
            //     ->find('all')
            //     ->where([$EducationGrades->aliasField('id') => $value['education_grade_id']])->toArray();
            // }


            // $query = $ClassesTable
            //     ->find()
            //     ->select([
            //         $ClassesTable->aliasField('id'),
            //         $ClassesTable->aliasField('staff_id'),
            //         $ClassesSecondaryStaffTable->aliasField('secondary_staff_id')
            //     ])
            //     ->innerJoin([$ClassesSecondaryStaffTable->alias() => $ClassesSecondaryStaffTable->table()], [
            //         $ClassesSecondaryStaffTable->aliasField('institution_class_id = ') . $ClassesTable->aliasField('id')
            //     ])
            //     ->contain([
            //         'EducationGrades' => [
            //             'fields' => [
            //                 'InstitutionClassGrades.institution_class_id',
            //                 'EducationGrades.id',
            //                 'EducationGrades.code',
            //                 'EducationGrades.name'
            //             ]
            //         ]
            //     ])
            //     ->hydrate(false)
            //     ->where([
            //         'OR' => [
            //             [$ClassesTable->aliasField('staff_id') => $staffId],
            //             [$ClassesSecondaryStaffTable->aliasField('secondary_staff_id') => $staffId]
            //         ]
            //     ]);

            // foreach ($query as $grade) {
            //     foreach ($grade as $key => $gradeName) {
            //         $grades[$gradeName['id']] = $gradeName['name'];
            //     }
            // }
        }

        return implode(', ', array_values(array_unique($edGrade))); //POCOR-6714
    }

    public function onExcelGetPositionTitleTeaching(Event $event, Entity $entity)
    {
        $yesno = $this->getSelectOptions('general.yesno');
        return (array_key_exists($entity->position_title_teaching, $yesno))? $yesno[$entity->position_title_teaching]: '';
    }

    public function onExcelRenderContactOption(Event $event, Entity $entity, array $attr)
    {
        $contactTypes = $attr['contactTypes'];

        $result = [];
        if ($entity->has('user')) {
            if ($entity->user->has('contacts')) {
                $userContacts = $entity->user->contacts;
                foreach ($userContacts as $key => $obj) {
                    if (in_array($obj->contact_type_id, $contactTypes)) {
                        $result[] = $obj->value;
                    }
                }
            }
        }

        return implode(', ', $result);
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
            'key' => 'Users.nationality_id',
            'field' => 'nationality',
            'type' => 'string',
            'label' => ''
        ];
        $newFields[] = [
            'key' => 'Users.identity_number',
            'field' => 'user_identities_default',
            'type' => 'string',
            'label' => __('Default Identity Number') //POCOR-6827
        ];

        $newFields[] = [
            'key' => 'Users.identities',
            'field' => 'user_identities',
            'type' => 'string',
            'label' =>  __('Other Identity Numbers') //POCOR-6827
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
            'key' => 'Education.name', //POCOR-6614
            'field' => 'education_grades',
            'type' => 'string',
            'label' => __('Education grades')
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

        $displayContactOptions = ['MOBILE', 'PHONE', 'EMAIL'];
        $ContactOptionsTable = TableRegistry::get('User.ContactOptions');
        $options = $ContactOptionsTable->find('list')
            ->where([$ContactOptionsTable->aliasField('code IN') => $displayContactOptions])
            ->order('order')
            ->toArray();

        $ContactTypesTable = TableRegistry::get('User.ContactTypes');
        foreach ($options as $id => $name) {
            $contactTypes = $ContactTypesTable->find()
                ->where([$ContactTypesTable->aliasField('contact_option_id') => $id])
                ->extract('id')
                ->toArray();

            $newFields[] = [
                'key' => 'contact_option',
                'field' => 'contact_option',
                'type' => 'contact_option',
                'label' => __($name),
                'formatting' => 'string',
                'contactTypes' => $contactTypes
            ];
        }

        $fields->exchangeArray($newFields);
    }
}
