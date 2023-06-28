<?php
namespace Report\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;

use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class SpecialNeedsTable extends AppTable
{
	use OptionsTrait;

    public function initialize(array $config)
    {
        $this->table('institution_class_students');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'joinType' => 'LEFT']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades', 'joinType' => 'LEFT']);
       
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'joinType' => 'LEFT']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'joinType' => 'LEFT']);
        
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
        $academic_period_id = $requestData->academic_period_id;
        $institution_id = $requestData->institution_id;
        $areaId = $requestData->area_education_id;
        $report_for = $requestData->report_for;

        $UserSpecialNeedsReferrals = TableRegistry::get('user_special_needs_referrals');
        $security_users = TableRegistry::get('security_users');
        $special_needs_referrer_types = TableRegistry::get('special_needs_referrer_types');
        $special_need_types = TableRegistry::get('special_need_types');
        $user_special_needs_assessments = TableRegistry::get('user_special_needs_assessments');
        $special_need_difficulties = TableRegistry::get('special_need_difficulties');
        $academic_periods = TableRegistry::get('academic_periods');
        $user_special_needs_services = TableRegistry::get('user_special_needs_services');
        $special_needs_service_types = TableRegistry::get('special_needs_service_types');
        $special_needs_service_classification = TableRegistry::get('special_needs_service_classification');
        $user_special_needs_devices = TableRegistry::get('user_special_needs_devices');
        $special_needs_device_types = TableRegistry::get('special_needs_device_types');
        $special_needs_plan_types = TableRegistry::get('special_needs_plan_types');
        $user_special_needs_plans = TableRegistry::get('user_special_needs_plans');
        $special_needs_diagnostics_types = TableRegistry::get('special_needs_diagnostics_types');
        $user_special_needs_diagnostics = TableRegistry::get('user_special_needs_diagnostics');
        $special_needs_diagnostics_degree = TableRegistry::get('special_needs_diagnostics_degree');
        
        $where = [];
        if ($institution_id > 0) {
            array_push($where, [$this->aliasField('institution_id') => $institution_id]);
        }
        // if ($areaId > 0) {
        //     array_push($where, [$this->aliasField('Institutions.area_id') => $areaId]);
        // }
        if($report_for == 'referral'){
            $query
            ->select([
                'date'=>$UserSpecialNeedsReferrals->aliasField('date'),
                'file_name'=>$UserSpecialNeedsReferrals->aliasField('file_name'),
                'comment'=>$UserSpecialNeedsReferrals->aliasField('comment'),
                'academic_period' => 'AcademicPeriods.name',                                
                'security_user_name' => $security_users->find()->func()->concat([
                    'SecurityUser.first_name' => 'literal',
                    " ",
                    'SecurityUser.last_name' => 'literal'
                    ]),
                'referrer_name' => $security_users->find()->func()->concat([
                                'Referrer.first_name' => 'literal',
                                " ",
                                'Referrer.last_name' => 'literal'
                                ]),
                'special_need_referrer_name'=>'SpecialNeedReferrer.name',
                'special_need_type' =>'SpecialNeedType.name'


            ])
            ->leftJoin([$UserSpecialNeedsReferrals->alias() => $UserSpecialNeedsReferrals->table()], [
                $UserSpecialNeedsReferrals->aliasField('security_user_id = ') . $this->aliasField('student_id')
            ])
            ->leftJoin(['SecurityUser' => $security_users->table()], [
                'SecurityUser.id = '.$UserSpecialNeedsReferrals->aliasField('security_user_id')
            ])
            ->leftJoin(['Referrer' => $security_users->table()], [
                'Referrer.id = '.$UserSpecialNeedsReferrals->aliasField('referrer_id')
            ])
            ->leftJoin(['SpecialNeedReferrer' => $special_needs_referrer_types->table()], [
                'SpecialNeedReferrer.id = '.$UserSpecialNeedsReferrals->aliasField('special_needs_referrer_type_id')
            ])
            ->leftJoin(['SpecialNeedType' => $special_need_types->table()], [
                'SpecialNeedType.id = '.$UserSpecialNeedsReferrals->aliasField('reason_type_id')
            ])
            ->leftJoin(['AcademicPeriods' => $academic_periods->table()], [
                'AcademicPeriods.id = '.$UserSpecialNeedsReferrals->aliasField('academic_period_id')
            ])
            ->contain([
                        'Institutions',
                    ])
            ->where([
                $UserSpecialNeedsReferrals->aliasField('academic_period_id') => $academic_period_id,
                $where
            ])
            ->group([
                $UserSpecialNeedsReferrals->aliasField('id')
            ])
            ->order([
                $UserSpecialNeedsReferrals->aliasField('date') => 'DESC'
            ]);
        }

        if($report_for == 'assessments'){
            $AcademicPeriods = TableRegistry::get('academic_periods');
            $periodsOptions = $AcademicPeriods
                        ->find('all')
                        ->where([
                            'id' => $academic_period_id,
                        ]);
            $res = $periodsOptions->toArray();
            $academic_period_year = $res[0]['name']; 
            $query
            ->select([
                'date'=>$user_special_needs_assessments->aliasField('date'),
                'file_name'=>$user_special_needs_assessments->aliasField('file_name'),
                'comment'=>$user_special_needs_assessments->aliasField('comment'),
                'special_need_type' =>'SpecialNeedType.name',                           
                'security_user_name' => $security_users->find()->func()->concat([
                    'SecurityUser.first_name' => 'literal',
                    " ",
                    'SecurityUser.last_name' => 'literal'
                    ]),
                'assessor_name' => $security_users->find()->func()->concat([
                                'Assessor.first_name' => 'literal',
                                " ",
                                'Assessor.last_name' => 'literal'
                                ]),
                'special_need_difficulty_name'=>'SpecialNeedDifficulty.name',


            ])
            ->leftJoin([$user_special_needs_assessments->alias() => $user_special_needs_assessments->table()], [
                $user_special_needs_assessments->aliasField('security_user_id = ') . $this->aliasField('student_id')
            ])
            ->leftJoin(['SpecialNeedType' => $special_need_types->table()], [
                'SpecialNeedType.id = '.$user_special_needs_assessments->aliasField('special_need_type_id')
            ])
            ->leftJoin(['SecurityUser' => $security_users->table()], [
                'SecurityUser.id = '.$user_special_needs_assessments->aliasField('security_user_id')
            ])
            ->leftJoin(['Assessor' => $security_users->table()], [
                'Assessor.id = '.$user_special_needs_assessments->aliasField('assessor_id')
            ])
            ->leftJoin(['SpecialNeedDifficulty' => $special_need_difficulties->table()], [
                'SpecialNeedDifficulty.id = '.$user_special_needs_assessments->aliasField('special_need_difficulty_id')
            ])
            ->contain([
                        'Institutions',
                        'AcademicPeriods'
                    ])
            ->where([
                $user_special_needs_assessments->aliasField('date >=') => $academic_period_year.'-01-01',
                $user_special_needs_assessments->aliasField('date <=') => $academic_period_year.'-12-31',
                $this->aliasField('academic_period_id') => $academic_period_id,
                $where
            ])
            ->group([
                $user_special_needs_assessments->aliasField('id')
            ])
            ->order([
                $user_special_needs_assessments->aliasField('date') => 'DESC'
            ]);
        }

        if($report_for == 'services'){
                $query
                    ->select([
                        'organization'=>$user_special_needs_services->aliasField('organization'),
                        'file_name'=>$user_special_needs_services->aliasField('file_name'),
                        'description'=>$user_special_needs_services->aliasField('description'),
                        'comment'=>$user_special_needs_services->aliasField('comment'),
                        'academic_period' => 'AcademicPeriods.name',                                
                        'security_user_name' => $security_users->find()->func()->concat([
                            'SecurityUser.first_name' => 'literal',
                            " ",
                            'SecurityUser.last_name' => 'literal'
                            ]),
                        'special_need_service_type'=>'SpecialNeedServiceType.name',
                        'special_need_service_classification'=>'SpecialNeedServiceClassification.name',


                    ])
                    ->leftJoin([$user_special_needs_services->alias() => $user_special_needs_services->table()], [
                        $user_special_needs_services->aliasField('security_user_id = ') . $this->aliasField('student_id')
                    ])
                    ->leftJoin(['SecurityUser' => $security_users->table()], [
                        'SecurityUser.id = '.$user_special_needs_services->aliasField('security_user_id')
                    ])
                    ->leftJoin(['SpecialNeedServiceType' => $special_needs_service_types->table()], [
                        'SpecialNeedServiceType.id = '.$user_special_needs_services->aliasField('special_needs_service_type_id')
                    ])
                    ->leftJoin(['SpecialNeedServiceClassification' => $special_needs_service_classification->table()], [
                        'SpecialNeedServiceClassification.id = '.$user_special_needs_services->aliasField('special_needs_service_classification_id')
                    ])
                    ->leftJoin(['AcademicPeriods' => $academic_periods->table()], [
                        'AcademicPeriods.id = '.$user_special_needs_services->aliasField('academic_period_id')
                    ])
                    ->contain([
                                'Institutions',
                            ])
                    ->where([
                        $user_special_needs_services->aliasField('academic_period_id') => $academic_period_id,
                        $where
                    ])
                    ->group([
                        $user_special_needs_services->aliasField('id')
                    ])
                    ->order([
                        $user_special_needs_services->aliasField('created') => 'DESC'
                    ]);
        }


        if($report_for == 'devices'){
                $query
                    ->select([
                        'comment'=>$user_special_needs_devices->aliasField('comment'),                               
                        'security_user_name' => $security_users->find()->func()->concat([
                            'SecurityUser.first_name' => 'literal',
                            " ",
                            'SecurityUser.last_name' => 'literal'
                            ]),
                        'special_need_device_type'=>'SpecialNeedDeviceType.name',


                    ])
                    ->leftJoin([$user_special_needs_devices->alias() => $user_special_needs_devices->table()], [
                        $user_special_needs_devices->aliasField('security_user_id = ') . $this->aliasField('student_id')
                    ])
                    ->leftJoin(['SecurityUser' => $security_users->table()], [
                        'SecurityUser.id = '.$user_special_needs_devices->aliasField('security_user_id')
                    ])
                    ->leftJoin(['SpecialNeedDeviceType' => $special_needs_device_types->table()], [
                        'SpecialNeedDeviceType.id = '.$user_special_needs_devices->aliasField('special_needs_device_type_id')
                    ])
                    ->contain([
                                'Institutions',
                                'AcademicPeriods'
                            ])
                    ->where([
                        $this->aliasField('academic_period_id') => $academic_period_id,
                        $where
                    ])
                    ->group([
                        $user_special_needs_devices->aliasField('id')
                    ])
                    ->order([
                        $user_special_needs_devices->aliasField('created') => 'DESC'
                    ]);
        }

        if($report_for == 'plans'){
                $query
                    ->select([
                        'comment'=>$user_special_needs_plans->aliasField('comment'),       
                        'plan_name'=>$user_special_needs_plans->aliasField('plan_name'),       
                        'file_name'=>$user_special_needs_plans->aliasField('file_name'),       
                        'academic_period' => 'AcademicPeriods.name',                            
                        'security_user_name' => $security_users->find()->func()->concat([
                            'SecurityUser.first_name' => 'literal',
                            " ",
                            'SecurityUser.last_name' => 'literal'
                            ]),
                        'special_need_plan_type'=>'SpecialNeedPlanType.name',


                    ])
                    ->leftJoin([$user_special_needs_plans->alias() => $user_special_needs_plans->table()], [
                        $user_special_needs_plans->aliasField('security_user_id = ') . $this->aliasField('student_id')
                    ])
                    ->leftJoin(['SecurityUser' => $security_users->table()], [
                        'SecurityUser.id = '.$user_special_needs_plans->aliasField('security_user_id')
                    ])
                    ->leftJoin(['SpecialNeedPlanType' => $special_needs_plan_types->table()], [
                        'SpecialNeedPlanType.id = '.$user_special_needs_plans->aliasField('special_needs_plan_types_id')
                    ])
                    ->leftJoin(['AcademicPeriods' => $academic_periods->table()], [
                        'AcademicPeriods.id = '.$user_special_needs_plans->aliasField('academic_period_id')
                    ])
                    ->contain([
                                'Institutions',
                            ])
                    ->where([
                        $user_special_needs_plans->aliasField('academic_period_id') => $academic_period_id,
                        $where
                    ])
                    ->group([
                        $user_special_needs_plans->aliasField('id')
                    ])
                    ->order([
                        $user_special_needs_plans->aliasField('created') => 'DESC'
                    ]);
        }

        if($report_for == 'diagnostics'){
            $query
                ->select([
                    'date'=>$user_special_needs_diagnostics->aliasField('date'),           
                    'comment'=>$user_special_needs_diagnostics->aliasField('comment'),       
                    'file_name'=>$user_special_needs_diagnostics->aliasField('file_name'),               
                    'security_user_name' => $security_users->find()->func()->concat([
                        'SecurityUser.first_name' => 'literal',
                        " ",
                        'SecurityUser.last_name' => 'literal'
                        ]),
                    'special_need_diagnostic_type'=>'SpecialNeedDiagnosticType.name',
                    'special_need_diagnostic_degree'=>'SpecialNeedDiagnosticDegree.name',


                ])
                ->leftJoin([$user_special_needs_diagnostics->alias() => $user_special_needs_diagnostics->table()], [
                    $user_special_needs_diagnostics->aliasField('security_user_id = ') . $this->aliasField('student_id')
                ])
                ->leftJoin(['SecurityUser' => $security_users->table()], [
                    'SecurityUser.id = '.$user_special_needs_diagnostics->aliasField('security_user_id')
                ])
                ->leftJoin(['SpecialNeedDiagnosticType' => $special_needs_diagnostics_types->table()], [
                    'SpecialNeedDiagnosticType.id = '.$user_special_needs_diagnostics->aliasField('special_needs_diagnostics_type_id')
                ])
                ->leftJoin(['SpecialNeedDiagnosticDegree' => $special_needs_diagnostics_degree->table()], [
                    'SpecialNeedDiagnosticDegree.id = '.$user_special_needs_diagnostics->aliasField('special_needs_diagnostics_degree_id')
                ])
                ->contain([
                            'Institutions',
                            'AcademicPeriods'
                        ])
                ->where([
                    $this->aliasField('academic_period_id') => $academic_period_id,
                    $where
                ])
                ->group([
                    $user_special_needs_diagnostics->aliasField('id')
                ])
                ->order([
                    $user_special_needs_diagnostics->aliasField('created') => 'DESC'
                ]);
        }
       
    }

    public function onExcelGetAge(Event $event, Entity $entity)
    {
        $age = '';
        if (!empty($entity->start_year) && !empty($entity->date_of_birth)) {
            $startYear = $entity->start_year;
            $dob = $entity->date_of_birth->format('Y');
            $age = $startYear - $dob;
        }

        return $age;
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) 
    {
        $report_for = json_decode($settings['process']['params'], true)['report_for'];
        if($report_for == 'referral'){
            $newFields[] = [
                'key' => '',
                'field' => 'date',
                'type' => 'date',
                'label' => __('Date')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'file_name',
                'type' => 'string',
                'label' => __('File Name')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'comment',
                'type' => 'string',
                'label' => __('Comment')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'academic_period',
                'type' => 'string',
                'label' => __('Academic Period')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'security_user_name',
                'type' => 'string',
                'label' => __('Security User')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'referrer_name',
                'type' => 'string',
                'label' => __('Referrer Name')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'special_need_referrer_name',
                'type' => 'string',
                'label' => __('Special Needs Referrer Type')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'special_need_type',
                'type' => 'string',
                'label' => __('Reason Type')
            ];
        }

        if($report_for == 'assessments'){

            $newFields[] = [
                'key' => '',
                'field' => 'date',
                'type' => 'date',
                'label' => __('Date')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'file_name',
                'type' => 'string',
                'label' => __('File Name')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'comment',
                'type' => 'string',
                'label' => __('Comment')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'special_need_type',
                'type' => 'string',
                'label' => __('Special Need Type')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'special_need_difficulty_name',
                'type' => 'string',
                'label' => __('Difficulty')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'security_user_name',
                'type' => 'string',
                'label' => __('Security User')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'assessor_name',
                'type' => 'string',
                'label' => __('Assessor Name')
            ];


        }

        if($report_for == 'services'){
            $newFields[] = [
                'key' => '',
                'field' => 'academic_period',
                'type' => 'string',
                'label' => __('Academic Period')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'special_need_service_type',
                'type' => 'string',
                'label' => __('Service Name')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'description',
                'type' => 'string',
                'label' => __('Description')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'special_need_service_classification',
                'type' => 'string',
                'label' => __('Classification')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'organization',
                'type' => 'string',
                'label' => __('Service Provider')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'file_name',
                'type' => 'string',
                'label' => __('File Name')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'comment',
                'type' => 'string',
                'label' => __('Comment')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'security_user_name',
                'type' => 'string',
                'label' => __('Security User')
            ];

        }

        if($report_for == 'devices'){
            $newFields[] = [
                'key' => '',
                'field' => 'comment',
                'type' => 'string',
                'label' => __('Comment')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'special_need_device_type',
                'type' => 'string',
                'label' => __('Device Name')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'security_user_name',
                'type' => 'string',
                'label' => __('Security User')
            ];

        }


        if($report_for == 'plans'){
            $newFields[] = [
                'key' => '',
                'field' => 'academic_period',
                'type' => 'string',
                'label' => __('Academic Period')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'special_need_plan_type',
                'type' => 'string',
                'label' => __('Plan Type')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'plan_name',
                'type' => 'string',
                'label' => __('Plan Name')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'file_name',
                'type' => 'string',
                'label' => __('Attachment')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'comment',
                'type' => 'string',
                'label' => __('Comment')
            ];          

            $newFields[] = [
                'key' => '',
                'field' => 'security_user_name',
                'type' => 'string',
                'label' => __('Security User')
            ];

        }

        if($report_for == 'diagnostics'){
            $newFields[] = [
                'key' => '',
                'field' => 'date',
                'type' => 'string',
                'label' => __('Date')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'special_need_diagnostic_type',
                'type' => 'string',
                'label' => __('Type of Disability')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'special_need_diagnostic_degree',
                'type' => 'string',
                'label' => __('Disability Degree')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'file_name',
                'type' => 'string',
                'label' => __('Attachment')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'comment',
                'type' => 'string',
                'label' => __('Comment')
            ];          

            $newFields[] = [
                'key' => '',
                'field' => 'security_user_name',
                'type' => 'string',
                'label' => __('Security User')
            ];
        }

        // $newFields[] = [
        //     'key' => 'Institutions.code',
        //     'field' => 'code',
        //     'type' => 'string',
        //     'label' => __('Institution Code')
        // ];

        // $newFields[] = [
        //     'key' => 'Institutions.name',
        //     'field' => 'institution_name',
        //     'type' => 'string',
        //     'label' => __('Institution Name')
        // ];

        // $newFields[] = [
        //     'key' => 'AcademicPeriods.name',
        //     'field' => 'academic_period',
        //     'type' => 'string',
        //     'label' => __('Academic Period')
        // ];

        // $newFields[] = [
        //     'key' => 'EducationGrades.name',
        //     'field' => 'education_grade',
        //     'type' => 'string',
        //     'label' => __('Education Grade')
        // ];

        // $newFields[] = [
        //     'key' => 'Users.openemis_no',
        //     'field' => 'openemis_no',
        //     'type' => 'string',
        //     'label' => __('OpenEMIS ID')
        // ];
        // $newFields[] = [
        //     'key' => '',
        //     'field' => 'student_name',
        //     'type' => 'string',
        //     'label' => __('Name')
        // ];
        // $newFields[] = [
        //     'key' => 'Users.age',
        //     'field' => 'age',
        //     'type' => 'string',
        //     'label' => __('Age')
        // ];
        // $newFields[] = [
        //     'key' => 'Genders.name',
        //     'field' => 'gender',
        //     'type' => 'string',
        //     'label' => __('Gender')
        // ];
        // $newFields[] = [
        //     'key' => 'IdentityTypes.name',
        //     'field' => 'identity_type',
        //     'type' => 'string',
        //     'label' => __('Identity Type')
        // ];
        // $newFields[] = [
        //     'key' => 'Users.identity_number',
        //     'field' => 'identity_number',
        //     'type' => 'integer',
        //     'label' => __('Identity Number')
        // ];
        // $newFields[] = [
        //     'key' => 'SpecialNeedsTypes.name',
        //     'field' => 'special_need_type',
        //     'type' => 'string',
        //     'label' => __('Disability Type')
        // ];
        // $newFields[] = [
        //     'key' => 'SpecialNeedsDifficulties.name',
        //     'field' => 'special_need_difficulty_type',
        //     'type' => 'string',
        //     'label' => __('Difficulty Type')
        // ];
        // $newFields[] = [
        //     'key' => 'SpecialNeedsServiceTypes.name',
        //     'field' => 'special_need_service_type',
        //     'type' => 'string',
        //     'label' => __('Program Assigned')
        // ];
        // $newFields[] = [
        //     'key' => 'SpecialNeedsServices.organization',
        //     'field' => 'organization',
        //     'type' => 'string',
        //     'label' => __('Organization')
        // ];
        // $newFields[] = [
        //     'key' => 'GuardianRelations.name',
        //     'field' => 'guardian_relation',
        //     'type' => 'string',
        //     'label' => __('Guardian Relations')
        // ];
        // $newFields[] = [
        //     'key' => 'GuardianRelations.openemis_no',
        //     'field' => 'guardian_openemis_no',
        //     'type' => 'string',
        //     'label' => __('Guardian OpenEMIS ID')
        // ];
        // $newFields[] = [
        //     'key' => '',
        //     'field' => 'guardian_name',
        //     'type' => 'string',
        //     'label' => __('Guardian Name')
        // ];
        // $newFields[] = [
        //     'key' => '',
        //     'field' => 'guardian_contact_number',
        //     'type' => 'string',
        //     'label' => __('Guardian Contact Number')
        // ];

        // $newFields[] = [
        //     'key' => '',
        //     'field' => 'staff_name',
        //     'type' => 'string',
        //     'label' => __('Referrer Staff Name')
        // ];

        // $newFields[] = [
        //     'key' => '',
        //     'field' => 'staff_contact',
        //     'type' => 'string',
        //     'label' => __('Referrer Staff Contact number')
        // ];

        $fields->exchangeArray($newFields);
    }
}
