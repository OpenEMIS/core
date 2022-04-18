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
 * POCOR-6598
 * Generate Employee Qualification Report
 * get array data
 */ 
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
        $qualification = TableRegistry::get('Staff.Qualifications');
        $qualificationtitle = TableRegistry::get('FieldOption.QualificationTitles');
        $qualificationlevel = TableRegistry::get('FieldOption.QualificationLevels');
        $qualificationCountry = TableRegistry::get('FieldOption.Countries');
        $educationFieldOfStudy = TableRegistry::get('Education.EducationFieldOfStudies');
        /*$position = TableRegistry::get('Institution.InstitutionPositions');*/
        $query
            ->select([
                $this->aliasField('id'),
               'date_of_hiring' => $this->aliasField('start_date'),
                $this->aliasField('staff_id'),  // this field is required to build value for Education Grades
                $this->aliasField('staff_type_id'),
                $this->aliasField('staff_status_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('institution_position_id'),
                /*'document_no' => 'Qualifications.document_no',
                'graduate_year' => 'Qualifications.graduate_year',
                'qualification_institution' => 'Qualifications.qualification_institution',
                'avg' => 'Qualifications.gpa',*/
                'EducationFieldOfStudies' => 'EducationFieldOfStudies.name',
                'country' => 'Countries.name',
                'level' => 'QualificationLevels.name',
               // 'subject' => 'EducationFieldOfStudies.name',
                'status_of_hiring' => 'Statuses.name',
              
            ])
            ->contain([
                'Institutions' => [
                    'fields' => [
                        'code' => 'Institutions.code',
                        'name'=>'Institutions.name'
                    ]
                ],
                
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
                        //'Users.id', // this field is required for Identities and IdentityTypes to appear
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
                'Positions.Statuses' => [
                    'fields' => [
                        'status_of_hiring'=>'Statuses.name'
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
                
            ])/*->innerJoin(
                [$workflows->alias() => $workflows->table()],
                [$workflows->aliasField('id = ') . $position->aliasField('status_id')]
            )*/
            ->group(['Users.id'])
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
            )
            ->leftJoin(
                [$qualificationlevel->alias() => $qualificationlevel->table()],
                [$qualificationlevel->aliasField('id = ') . $qualificationtitle->aliasField('qualification_level_id')]
            );
    
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
            'label' => __('Institution')
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
        

        $newFields[] = [
            'key' => 'employment_status',
            'field' => 'employment_status',
            'type' => 'string',
            'label' => __('Employment Status')
        ];

        $newFields[] = [
            'key' => 'status_of_hiring',
            'field' => 'status_of_hiring',
            'type' => 'string',
            'label' => __('Status Of Hiring')
        ];

        $newFields[] = [
            'key' => 'date_of_hiring',
            'field' => 'date_of_hiring',
            'type' => 'date',
            'label' => __('Date Of Hiring')
        ];
        $newFields[] = [
            'key' => 'staff_qualifications',
            'field' => 'staff_qualifications',
            'type' => 'string',
            'label' => __('Staff Qualifications')
        ];
        $newFields[] = [
            'key' => 'Users.address',
            'field' => 'address',
            'type' => 'string',
            'label' => __('Address')
        ];

        $newFields[] = [
            'key' => 'staff_level',
            'field' => 'staff_level',
            'type' => 'string',
            'label' => __('Level')
        ];
        $newFields[] = [
            'key' => 'sub_major',
            'field' => 'sub_major',
            'type' => 'string',
            'label' => __('Sub Major')
        ];
        $newFields[] = [
            'key' => 'staff_subject',
            'field' => 'staff_subject',
            'type' => 'string',
            'label' => __('Subject')
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
            'label' => __('Institution')
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
            'key' => 'gpa',
            'field' => 'gpa',
            'type' => 'integer',
            'label' => __('The Average')
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
            ->where([$userIdentities->aliasField('security_user_id') => $entity->staff_id])
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

    /**
    * Get staff highest qualification 
    */
    public function onExcelGetStaffQualifications(Event $event, Entity $entity)
    {
        $userid =  $entity->staff_id;
        $qualification = TableRegistry::get('Staff.Qualifications');
        $qualificationtitle = TableRegistry::get('FieldOption.QualificationTitles');
        $qualificationLevel= TableRegistry::get('FieldOption.QualificationLevels');
        $staffQualification = $qualification->find()
            ->leftJoin(['QualificationTitles' => 'qualification_titles'], ['QualificationTitles.id = '. $qualification->aliasField('qualification_title_id')])
            ->leftJoin(['QualificationLevels' => 'qualification_levels'], ['QualificationLevels.id = '. $qualificationtitle->aliasField('qualification_level_id')])
            ->select([
                'order' => $qualificationLevel->aliasField('order'),
                'id' => $qualificationtitle->aliasField('id'),
                'level_id' => $qualificationtitle->aliasField('qualification_level_id'),
            ])
            ->where([$qualification->aliasField('staff_id') => $entity->staff_id]);
            $level = [];
            $titleid = [];
            $level_id =[];
            if(!empty($staffQualification)){
                $data = $staffQualification->toArray();
                $entity->staff_qualification = '';
                $entity->document_no = '';
                $entity->graduate_year = '';
                $entity->qualification_institution = '';
                $entity->gpa = '';
                foreach($data as $key=>$val){
                    $level[] = $val['order'];
                    $titleid[] = $val['id'];
                    $level_id[] = $val['level_id'];
                }
                
               // if(count(array_unique($level)) < count($level)){
                if((count(array_unique($level)) === 1 && !empty($titleid))){
                    $staffQualificationdata = $qualificationtitle->find('all')
                    ->select([
                            'id' => $qualificationtitle->aliasField('id'),
                            'name' => $qualificationtitle->aliasField('name'),
                        ])
                    ->where([$qualificationtitle->aliasField('id IN ') => $titleid])
                    ->order([$qualificationtitle->aliasField('id')=>'DESC'])
                    ->limit(1);
                    if($staffQualificationdata!=null){
                        $staffdata = $staffQualificationdata->toArray();
                        foreach($staffdata as $val){
                            $entity->staff_qualification = $val['name'];
                            $title_id = $val['id'];
                             $entity->staff_qualification;
                        }
                        $staff_qualification_higher = $qualification->find('all')
                                        ->where([$qualification->aliasField('staff_id') => $entity->staff_id,
                                            $qualification->aliasField('qualification_title_id') => $title_id ])
                                        ->limit(1);
                        $get_qualification_data = $staff_qualification_higher->toArray();
                        foreach($get_qualification_data as $value) {
                            $entity->document_no = $value['document_no'];
                            $entity->graduate_year = $value['graduate_year'];
                            $entity->qualification_institution = $value['qualification_institution'];
                            $entity->gpa = $value['gpa'];
                        }
                        
                    }

                }else{
                    if(!empty($titleid)){
                        if(array_unique(array_diff_assoc($level_id, array_unique($level_id)))){
                       $staffQualificationdata1 = $qualificationtitle->find()
                                    ->select([
                                            'title_id' => "MAX(".$qualificationtitle->aliasField('id').")"
                                        ])
                                    ->where([$qualificationtitle->aliasField('qualification_level_id IN') => $level_id])
                                    ->limit(1);
                                    if(!empty($staffQualificationdata1))
                            {
                                $staffdata_s = $staffQualificationdata1->toArray();
                                foreach($staffdata_s as $key=>$val)
                                {
                                    $tittle = $val['title_id'];
                                }
                                   $staffQualificationdata2 = $qualificationtitle->find()
                                    ->select([
                                            'title_name' => $qualificationtitle->aliasField('name'),
                                            'title_id' => $qualificationtitle->aliasField('id')
                                            
                                        ])
                                    ->where([$qualificationtitle->aliasField('id') => $tittle]);
                                    
                                $staffdata_ss = $staffQualificationdata2->toArray();
                                foreach($staffdata_ss as $key=>$val)
                                {
                                    $entity->staff_qualification = $val['title_name'];
                                    $title_id = $val['title_id'];
                                     $entity->staff_qualification;
                                }
                                $staff_qualification_higher = $qualification->find('all')
                                            ->where([$qualification->aliasField('staff_id') => $entity->staff_id,
                                                $qualification->aliasField('qualification_title_id') => $title_id ])
                                            ->limit(1);
                                $get_qualification_data = $staff_qualification_higher->toArray();
                                foreach($staff_qualification_higher as $value) {
                                    $entity->document_no = $value['document_no'];
                                    $entity->graduate_year = $value['graduate_year'];
                                    $entity->qualification_institution = $value['qualification_institution'];
                                    $entity->gpa = $value['gpa'];
                                }
                            }

                        }else{
                        
                   $staffQualificationdata = $qualificationtitle->find()
                    ->leftJoin(['QualificationLevels' => 'qualification_levels'], ['QualificationLevels.id = '. $qualificationtitle->aliasField('qualification_level_id')])
                    ->select([
                            'quali_id' => $qualificationtitle->aliasField('id'),
                            'level_id' => $qualificationLevel->aliasField('id'),
                           
                        ])
                    ->where([$qualificationLevel->aliasField('id IN') => $level_id])
                    ->order([$qualificationLevel->aliasField('order')=>'ASC'])
                    ->limit(1);
                    if($staffQualificationdata!=null)
                    {
                        $staff_qualification_data = $staffQualificationdata->toArray();
                        foreach($staff_qualification_data as $key=>$val)
                        {
                            $levelval = $val['level_id'];
                            $quali_id = $val['quali_id'];
                            $staffQualificationvals = $qualificationtitle->find()
                            ->select([
                                    'name' => $qualificationtitle->aliasField('name'),
                                    'id' => $qualificationtitle->aliasField('id'),
                                ])
                            ->where([$qualificationtitle->aliasField('qualification_level_id') => $levelval,
                                $qualificationtitle->aliasField('id') => $quali_id
                        ]);
                            if(!empty($staffQualificationvals))
                            {
                                $staffdata = $staffQualificationvals->toArray();
                                foreach($staffdata as $key=>$val)
                                {
                                    $entity->staff_qualification = $val['name'];
                                    $title_id = $val['id'];
                                     $entity->staff_qualification;
                                }
                                $staff_qualification_higher = $qualification->find('all')
                                            ->where([$qualification->aliasField('staff_id') => $entity->staff_id,
                                                $qualification->aliasField('qualification_title_id') => $title_id ])
                                            ->limit(1);
                                $get_qualification_data = $staff_qualification_higher->toArray();
                                foreach($staff_qualification_higher as $value) {
                                    $entity->document_no = $value['document_no'];
                                    $entity->graduate_year = $value['graduate_year'];
                                    $entity->qualification_institution = $value['qualification_institution'];
                                    $entity->gpa = $value['gpa'];
                                }
                            }   
                         }

                }  } }

            }
                
        }
            return $entity->staff_qualification;
    }
    public function onExcelGetDocumentNo(Event $event, Entity $entity)
    {
        return $entity->document_no;
    }
    public function onExcelGetGraduateYear(Event $event, Entity $entity)
    {
        return $entity->graduate_year;
    }
    public function onExcelGetQualificationInstitution(Event $event, Entity $entity)
    {
        return $entity->qualification_institution;
    }
    public function onExcelGetGpa(Event $event, Entity $entity)
    {
        return $entity->gpa;
    }

    /*
    * Get staff highest level 
    */
    public function onExcelGetStaffLevel(Event $event, Entity $entity)
    {
        $userid =  $entity->staff_id;
        $qualification = TableRegistry::get('Staff.Qualifications');
        $qualificationtitle = TableRegistry::get('FieldOption.QualificationTitles');
        $qualificationLevel= TableRegistry::get('FieldOption.QualificationLevels');
        $staffQualification = $qualification->find()
            ->leftJoin(['QualificationTitles' => 'qualification_titles'], ['QualificationTitles.id = '. $qualification->aliasField('qualification_title_id')])
            ->leftJoin(['QualificationLevels' => 'qualification_levels'], ['QualificationLevels.id = '. $qualificationtitle->aliasField('qualification_level_id')])
            ->select([
                'order' => $qualificationLevel->aliasField('order'),
                'id' => $qualificationtitle->aliasField('id'),
                'level_id' => 'QualificationLevels.id',
                //'max' => "MAX(".$qualificationtitle->aliasField('order').")"
            ])
            ->where([$qualification->aliasField('staff_id') => $entity->staff_id]);
            $level = [];
            $titleid = [];
            $level_ids = [];
            if(!empty($staffQualification)){
                $data = $staffQualification->toArray();
                $entity->staff_level = '';
                foreach($data as $key=>$val){
                    $level[] = $val['order'];
                    $titleid[] = $val['id'];
                    $level_ids[] = $val['level_id'];
                }
               // if(count(array_unique($level)) < count($level)){
                if((count(array_unique($level)) === 1 && !empty($titleid))){
                    $staffQualificationdata = $qualificationLevel->find('all')
                    ->select([
                            'id' => $qualificationLevel->aliasField('id'),
                            'name' => $qualificationLevel->aliasField('name'),
                        ])
                    ->where([$qualificationLevel->aliasField('id IN ') => $level_ids])
                    ->order([$qualificationLevel->aliasField('order')=>'ASC'])
                    ->limit(1);
                    if($staffQualificationdata!=null){
                        $staffdata = $staffQualificationdata->toArray();
                        foreach($staffdata as $val){
                            $entity->staff_level = $val['name'];
                            return $entity->staff_level;
                        }
                    }
                }else{
                    if(!empty($titleid)){
                    $staffQualificationdata = $qualificationtitle->find()
                    ->leftJoin(['QualificationLevels' => 'qualification_levels'], ['QualificationLevels.id = '. $qualificationtitle->aliasField('qualification_level_id')])
                    ->select([
                            'level' => $qualificationtitle->aliasField('qualification_level_id'),
                            'id' => $qualificationLevel->aliasField('id'),
                            'level_name' => $qualificationLevel->aliasField('name'),
                        ])
                    ->where([$qualificationtitle->aliasField('id IN') => $titleid])
                    ->order([$qualificationLevel->aliasField('id')=>'ASC'])
                    ->limit(1);
                    if($staffQualificationdata!=null)
                    {
                        $staff_qualification_data = $staffQualificationdata->toArray();
                        foreach($staff_qualification_data as $key=>$val)
                        {
                            $entity->staff_level = $val['level_name'];
                            return $entity->staff_level;
                        }   
                    }

                }
            }
                
            }
            return '';
    }
    /**
    * Get staff other qualification 
    */
    public function onExcelGetSubMajor(Event $event, Entity $entity)
    {
        $qualification = TableRegistry::get('Staff.Qualifications');
        $qualificationtitlespecial = TableRegistry::get('FieldOption.QualificationSpecialisations');
        $qualificationspecial = TableRegistry::get('staff_qualifications_specialisations');
        $submajor = $qualificationspecial->find()
            ->innerJoin(
                [$qualification->alias() => $qualification->table()],
                [$qualification->aliasField('id = ') . $qualificationspecial->aliasField('staff_qualification_id')]
            )
            ->innerJoin(
                [$qualificationtitlespecial->alias() => $qualificationtitlespecial->table()],
                [$qualificationtitlespecial->aliasField('id = ') .$qualificationspecial->aliasField('qualification_specialisation_id')]
            )
            ->select([
                'id' => $qualificationtitlespecial->aliasField('id'),
                'name' => $qualificationtitlespecial->aliasField('name'),
            ])
            ->where([$qualification->aliasField('staff_id') => $entity->staff_id])
            ->order([$qualificationtitlespecial->aliasField('order')=>'DESC'])
            ->limit(1);
            $specialisationss = [];
            if($submajor!=null){
                $data = $submajor->toArray();
                $entity->sub_major = '';
                foreach ($data as $key => $val) {
                    $specialisationss[$val['id']] = $val['name'];
                
                }
            $entity->sub_major =  implode(', ', array_values($specialisationss));
            return $entity->sub_major;
            }
            return '';
    }

    /**
    * Get staff subject 
    */
    public function onExcelGetStaffSubject(Event $event, Entity $entity)
    {
        $qualification = TableRegistry::get('Staff.Qualifications');
        $StaffSubjects = TableRegistry::get('Staff.StaffQualificationsSubjects');
        $educationSubjects = TableRegistry::get('Education.EducationSubjects');
        $subjectRecord = $StaffSubjects->find()
            ->innerJoin(
                [$qualification->alias() => $qualification->table()],
                [$qualification->aliasField('id = ') . $StaffSubjects->aliasField('staff_qualification_id')]
            )
            ->innerJoin(
                [$educationSubjects->alias() => $educationSubjects->table()],
                [$educationSubjects->aliasField('id = ') .$StaffSubjects->aliasField('education_subject_id')]
            )
            ->select([
                'id' => $educationSubjects->aliasField('id'),
                'name' => $educationSubjects->aliasField('name'),
            ])
            ->where([$qualification->aliasField('staff_id') => $entity->staff_id]);
            $staffSubjects = [];
            if($subjectRecord!=null){
                $data = $subjectRecord->toArray();
                $entity->subject = '';
                foreach ($data as $key => $val) {
                    $staffSubjects[$val['name']] = $val['name'];
                
                }
            $entity->subject =  implode(', ', array_values($staffSubjects));
            return $entity->subject;
            }
            return '';

    }
}
