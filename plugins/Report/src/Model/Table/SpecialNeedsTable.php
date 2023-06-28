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

    // public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    // {
    //     // Setting request data and modifying fetch condition
    //     $requestData = json_decode($settings['process']['params']);
    //     $academic_period_id = $requestData->academic_period_id;
    //     $institution_id = $requestData->institution_id;
    //     $areaId = $requestData->area_education_id;
    //     $Users = TableRegistry::get('User.Users');
    //     $Genders = TableRegistry::get('User.Genders');
    //     $SpecialNeedsAssessments = TableRegistry::get('SpecialNeeds.SpecialNeedsAssessments');
    //     $SpecialNeedsServices = TableRegistry::get('SpecialNeeds.SpecialNeedsServices');
    //     $SpecialNeedsTypes = TableRegistry::get('SpecialNeeds.SpecialNeedsTypes');
    //     $SpecialNeedsDifficulties = TableRegistry::get('SpecialNeeds.SpecialNeedsDifficulties');
    //     $SpecialNeedsServiceTypes = TableRegistry::get('SpecialNeeds.SpecialNeedsServiceTypes');
    //     $StudentGuardians = TableRegistry::get('Student.StudentGuardians');
    //     $InstitutionStudentRisks = TableRegistry::get('Institution.InstitutionStudentRisks');
    //     $GuardianRelations = TableRegistry::get('Student.GuardianRelations');
    //     $IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');
    //     $UserIdentities = TableRegistry::get('User.Identities');
    //     $UserContact = TableRegistry::get('user_contacts');
    //     $UserSpecialNeedsReferrals = TableRegistry::get('user_special_needs_referrals');
    //     if ($institution_id != 0) {
    //         $where = [$this->aliasField('institution_id') => $institution_id];
    //     } else {
    //         $where = [];
    //     }
    //     if ($areaId != -1) {
    //         $where = [$this->aliasField('Institutions.area_id') => $areaId];
    //     } else {
    //         $where = [];
    //     }
    //     $query
    //         ->select([
    //             'code' => 'Institutions.code',
    //             'institution_name' => 'Institutions.name',
    //             'academic_period' => 'AcademicPeriods.name',
    //             'education_grade' => 'EducationGrades.name',
    //             'institution_class' => 'InstitutionClasses.name',
    //             'openemis_no' => 'Users.openemis_no',
    //             'student_name' => $Users->find()->func()->concat([
    //                 'Users.first_name' => 'literal',
    //                 " - ",
    //                 'Users.last_name' => 'literal']),
    //             'gender' => 'Genders.name',
    //             'date_of_birth' => 'Users.date_of_birth',
    //             'start_year' => 'AcademicPeriods.start_year',
    //             'identity_type' => $IdentityTypes->aliasField('name'),
    //             'identity_number' => $UserIdentities->aliasField('number'),
    //             'special_need_type' => 'SpecialNeedsTypes.name',
    //             'special_need_difficulty_type' => 'SpecialNeedsDifficulties.name',
    //             'special_need_service_type' => 'SpecialNeedsServiceTypes.name',
    //             'organization' => 'SpecialNeedsServices.organization',
    //             'guardian_relation' => 'GuardianRelations.name',
    //             'guardian_openemis_no' => 'GuardianUser.openemis_no',
    //             'guardian_name' => $Users->find()->func()->concat([
    //                 'GuardianUser.first_name' => 'literal',
    //                 " - ",
    //                 'GuardianUser.last_name' => 'literal']),
    //             'guardian_contact_number' => $UserContact->aliasField('value'),
    //             'referred_user_id' => $UserSpecialNeedsReferrals->aliasField('security_user_id'),
    //             'referred_staff_id' => $UserSpecialNeedsReferrals->aliasField('referrer_id'),
    //         ])
    //         ->leftJoin(
    //                 [$Users->alias() => $Users->table()],
    //                 [
    //                     $Users->aliasField('id = ') . $this->aliasField('student_id')
    //                 ]
    //             )
    //         ->leftJoin(
    //                 [$UserIdentities->alias() => $UserIdentities->table()],
    //                 [
    //                     $UserIdentities->aliasField('security_user_id = ') . $Users->aliasField('id')
    //                 ]
    //             )
    //         ->leftJoin(
    //                 [$IdentityTypes->alias() => $IdentityTypes->table()],
    //                 [
    //                     $IdentityTypes->aliasField('id = ') . $UserIdentities->aliasField('identity_type_id')
    //                 ]
    //             )
    //         ->innerJoin(
    //                 [$SpecialNeedsAssessments->alias() => $SpecialNeedsAssessments->table()],
    //                 [
    //                     $SpecialNeedsAssessments->aliasField('security_user_id = ') . $this->aliasField('student_id')
    //                 ]
    //             )
    //         ->leftJoin(
    //                 [$SpecialNeedsTypes->alias() => $SpecialNeedsTypes->table()],
    //                 [
    //                     $SpecialNeedsTypes->aliasField('id = ') . $SpecialNeedsAssessments->aliasField('special_need_type_id')
    //                 ]
    //             )
    //         ->leftJoin(
    //                 [$SpecialNeedsDifficulties->alias() => $SpecialNeedsDifficulties->table()],
    //                 [
    //                     $SpecialNeedsDifficulties->aliasField('id = ') . $SpecialNeedsAssessments->aliasField('special_need_difficulty_id')
    //                 ]
    //             )
    //         ->innerJoin(
    //                 [$SpecialNeedsServices->alias() => $SpecialNeedsServices->table()],
    //                 [
    //                     $SpecialNeedsServices->aliasField('security_user_id = ') . $this->aliasField('student_id')
    //                 ]
    //             )
    //         ->leftJoin(
    //                 [$SpecialNeedsServiceTypes->alias() => $SpecialNeedsServiceTypes->table()],
    //                 [
    //                     $SpecialNeedsServiceTypes->aliasField('id = ') . $SpecialNeedsServices->aliasField('special_needs_service_type_id')
    //                 ]
    //             )
    //         ->leftJoin(
    //                 [$StudentGuardians->alias() => $StudentGuardians->table()],
    //                 [
    //                     $StudentGuardians->aliasField('student_id = ') . $this->aliasField('student_id')
    //                 ]
    //             )
    //         ->leftJoin(
    //                 [$GuardianRelations->alias() => $GuardianRelations->table()],
    //                 [
    //                     $GuardianRelations->aliasField('id = ') . $StudentGuardians->aliasField('guardian_relation_id')
    //                 ]
    //             )
    //         ->leftJoin(
    //                 [$InstitutionStudentRisks->alias() => $InstitutionStudentRisks->table()],
    //                 [
    //                     $InstitutionStudentRisks->aliasField('student_id = ') . $this->aliasField('student_id')
    //                 ]
    //             )
    //         ->leftJoin(['GuardianUser' => 'security_users'], [
    //                     'GuardianUser.id = '.$StudentGuardians->aliasField('guardian_id')
    //                 ])
    //         ->leftJoin([$UserContact->alias() => $UserContact->table()], [
    //             $UserContact->aliasField('security_user_id = ') . 'GuardianUser.id'
    //         ])
    //         ->leftJoin([$UserSpecialNeedsReferrals->alias() => $UserSpecialNeedsReferrals->table()], [
    //             $UserSpecialNeedsReferrals->aliasField('security_user_id = ') . $this->aliasField('student_id')
    //         ])
    //         ->contain([
    //             'Institutions',
    //             'AcademicPeriods',
    //             'EducationGrades',
    //             'InstitutionClasses',
    //             'Users.Genders'
    //         ])
    //         ->group([
    //             'Users.id'
    //         ])
    //         ->where([
    //                 $this->aliasField('academic_period_id') => $academic_period_id,
    //                 $where
    //             ])
    //         ->order([
    //             'EducationGrades.name'
    //         ]);

    //         $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
    //             return $results->map(function ($row) {
                    
    //                 $UserSpecialNeedsReferrals = TableRegistry::get('user_special_needs_referrals');
    //                 $staff_user_data = $UserSpecialNeedsReferrals
    //                             ->find()
    //                             ->where([$UserSpecialNeedsReferrals->alias('security_user_id')=>$row->referred_user_id])
    //                             ->toArray();
    //                 $security_users = TableRegistry::get('security_users');
    //                 foreach($staff_user_data AS $staff_user){
    //                     $val = $security_users
    //                                 ->find()
    //                                 ->select([
    //                                     $security_users->aliasField('first_name'),
    //                                     $security_users->aliasField('middle_name'),
    //                                     $security_users->aliasField('last_name'),
    //                                     ])  
    //                                 ->where([
    //                                     $security_users->aliasField('id') => $staff_user->referrer_id
    //                                 ])->first();
    //                     $name[] = $val->first_name." ".$val->middle_name." ".$val->last_name;
    //                 }
    //                 $name = array_unique($name);
    //                 $implodedArr = implode(",",$name);
    //                 $row['staff_name'] = $implodedArr;


    //                 $UserContact = TableRegistry::get('user_contacts');

    //                 foreach($staff_user_data AS $staff_user){
    //                     $val = $UserContact
    //                                 ->find()
    //                                 ->select([
    //                                     $UserContact->aliasField('value'),
    //                                     ])  
    //                                 ->where([
    //                                     $UserContact->aliasField('security_user_id') => $staff_user->referrer_id
    //                                 ])->first();
    //                     if(empty($val->value)){
    //                     }
    //                     else{
    //                         $contact[] = $val->value;
    //                     }
    //                 }
    //                 $contact = array_unique($contact);
    //                 $implodedContactArr = implode(",",$contact);
    //                 $row['staff_contact'] = $implodedContactArr;
                              
                    
    //                 return $row;
    //             });
    //         });
    // }


    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        // Setting request data and modifying fetch condition
        $requestData = json_decode($settings['process']['params']);
        $academic_period_id = $requestData->academic_period_id;
        $institution_id = $requestData->institution_id;
        $areaId = $requestData->area_education_id;
        $report_for = $requestData->special_needs_feature;

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
        $identity_types = TableRegistry::get('identity_types');

        $genders = TableRegistry::get('genders');
        
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
                'id'=>$UserSpecialNeedsReferrals->aliasField('id'),
                'academic_period' => 'AcademicPeriods.name',                                
                'institution_code' => 'Institutions.code',                                
                'institution_name' => 'Institutions.name',                                
                'education_grade' => 'EducationGrades.name',                                
                'openemis_id' => 'SecurityUser.openemis_no',                                
                'security_user_name' => $security_users->find()->func()->concat([
                    'SecurityUser.first_name' => 'literal',
                    " ",
                    'SecurityUser.last_name' => 'literal'
                    ]),
                'age' => 'SecurityUser.date_of_birth',                   
                'gender' => 'Gender.name',
                'identity_type' => 'IdentityType.name',
                'identity_number' => 'SecurityUser.identity_number',
                'date_of_referral'=>$UserSpecialNeedsReferrals->aliasField('date'),
                'referral_comment'=>$UserSpecialNeedsReferrals->aliasField('comment'),
                'referrer_openemis_id' => 'Referrer.openemis_no',
                'referrer_name' => $security_users->find()->func()->concat([
                                'Referrer.first_name' => 'literal',
                                " ",
                                'Referrer.last_name' => 'literal'
                                ]),
                'special_need_referrer_type_name'=>'SpecialNeedReferrer.name',
                'special_need_type' =>'SpecialNeedType.name'


            ])
            ->leftJoin([$UserSpecialNeedsReferrals->alias() => $UserSpecialNeedsReferrals->table()], [
                $UserSpecialNeedsReferrals->aliasField('security_user_id = ') . $this->aliasField('student_id')
            ])
            ->leftJoin(['SecurityUser' => $security_users->table()], [
                'SecurityUser.id = '.$UserSpecialNeedsReferrals->aliasField('security_user_id')
            ])
            ->leftJoin(['Gender' => $genders->table()], [
                'Gender.id = '.'SecurityUser.gender_id'
            ])
            ->leftJoin(['IdentityType' => $identity_types->table()], [
                'IdentityType.id = '.'SecurityUser.identity_type_id'
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
                        'EducationGrades',
                    ])
            ->where([
                $UserSpecialNeedsReferrals->aliasField('academic_period_id') => $academic_period_id,
                $where
            ])
            ->order([
                $UserSpecialNeedsReferrals->aliasField('id') => 'DESC'
            ]);

            $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
                $idarr = [];
                $final_opt = [];
                foreach($results as $key => $res){
                    if(!in_array($res->id,$idarr)){
                        array_push($idarr,$res->id);
                        $diff = abs(strtotime(date('Y-m-d'))-strtotime($res->age));  
                        $years = floor($diff / (365*60*60*24));
                        $res['age'] = $years;
                        array_push($final_opt,$res);
                    }
                }
                return $final_opt;
            });

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
                'id'=>$user_special_needs_assessments->aliasField('id'),
                'education_grade' => 'EducationGrades.name', 
                'openemis_id' => 'SecurityUser.openemis_no',      
                'security_user_name' => $security_users->find()->func()->concat([
                    'SecurityUser.first_name' => 'literal',
                    " ",
                    'SecurityUser.last_name' => 'literal'
                ]),
                'age' => 'SecurityUser.date_of_birth',                   
                'gender' => 'Gender.name',
                'identity_type' => 'IdentityType.name',
                'identity_number' => 'SecurityUser.identity_number',
                'date'=>$user_special_needs_assessments->aliasField('date'),
                'special_need_type' =>'SpecialNeedType.name',                           
                'special_need_difficulty_name'=>'SpecialNeedDifficulty.name',
                'assessor_openemis_id' => 'Assessor.openemis_no',      
                'assessor_name' => $security_users->find()->func()->concat([
                                'Assessor.first_name' => 'literal',
                                " ",
                                'Assessor.last_name' => 'literal'
                                ]),
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
            ->leftJoin(['Gender' => $genders->table()], [
                'Gender.id = '.'SecurityUser.gender_id'
            ])
            ->leftJoin(['IdentityType' => $identity_types->table()], [
                'IdentityType.id = '.'SecurityUser.identity_type_id'
            ])
            ->leftJoin(['Assessor' => $security_users->table()], [
                'Assessor.id = '.$user_special_needs_assessments->aliasField('assessor_id')
            ])
            ->leftJoin(['SpecialNeedDifficulty' => $special_need_difficulties->table()], [
                'SpecialNeedDifficulty.id = '.$user_special_needs_assessments->aliasField('special_need_difficulty_id')
            ])
            ->contain([
                        'Institutions',
                        'EducationGrades'
                    ])
            ->where([
                $user_special_needs_assessments->aliasField('date >=') => $academic_period_year.'-01-01',
                $user_special_needs_assessments->aliasField('date <=') => $academic_period_year.'-12-31',
                $user_special_needs_assessments->aliasField('date >=') => date('Y-m-d',strtotime($requestData->report_start_date)),
                $user_special_needs_assessments->aliasField('date <=') => date('Y-m-d',strtotime($requestData->report_end_date)),
                $this->aliasField('academic_period_id') => $academic_period_id,
                $where
            ])
            ->order([
                $user_special_needs_assessments->aliasField('id') => 'DESC'
            ]);

            $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
                $idarr = [];
                $final_opt = [];

                foreach($results as $key => $res){
                    if(!in_array($res->id,$idarr)){
                        array_push($idarr,$res->id);
                        $diff = abs(strtotime(date('Y-m-d'))-strtotime($res->age));  
                        $years = floor($diff / (365*60*60*24));
                        $res['age'] = $years;
                        array_push($final_opt,$res);
                    }
                }
                return $final_opt;
            });
        }

        if($report_for == 'services'){
                $query
                    ->select([
                        'id'=>$user_special_needs_services->aliasField('id'),
                        'academic_period' => 'AcademicPeriods.name',
                        'institution_code' => 'Institutions.code',                                
                        'institution_name' => 'Institutions.name',                                
                        'education_grade' => 'EducationGrades.name',                                
                        'openemis_id' => 'SecurityUser.openemis_no',                                
                        'security_user_name' => $security_users->find()->func()->concat([
                            'SecurityUser.first_name' => 'literal',
                            " ",
                            'SecurityUser.last_name' => 'literal'
                            ]),    
                        'age' => 'SecurityUser.date_of_birth',                   
                        'gender' => 'Gender.name',
                        'identity_type' => 'IdentityType.name',
                        'identity_number' => 'SecurityUser.identity_number',
                        'special_need_service_type'=>'SpecialNeedServiceType.name',
                        'special_need_service_classification'=>'SpecialNeedServiceClassification.name',


                    ])
                    ->leftJoin([$user_special_needs_services->alias() => $user_special_needs_services->table()], [
                        $user_special_needs_services->aliasField('security_user_id = ') . $this->aliasField('student_id')
                    ])
                    ->leftJoin(['SecurityUser' => $security_users->table()], [
                        'SecurityUser.id = '.$user_special_needs_services->aliasField('security_user_id')
                    ])
                    ->leftJoin(['Gender' => $genders->table()], [
                        'Gender.id = '.'SecurityUser.gender_id'
                    ])
                    ->leftJoin(['IdentityType' => $identity_types->table()], [
                        'IdentityType.id = '.'SecurityUser.identity_type_id'
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
                                'EducationGrades',
                            ])
                    ->where([
                        $user_special_needs_services->aliasField('academic_period_id') => $academic_period_id,
                        $where
                    ])
                    ->order([
                        $user_special_needs_services->aliasField('id') => 'DESC'
                    ]);

                    $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
                        $idarr = [];
                        $final_opt = [];
                        foreach($results as $key => $res){
                            if(!in_array($res->id,$idarr)){
                                array_push($idarr,$res->id);
                                $diff = abs(strtotime(date('Y-m-d'))-strtotime($res->age));  
                                $years = floor($diff / (365*60*60*24));
                                $res['age'] = $years;
                                array_push($final_opt,$res);
                            }
                        }
                        return $final_opt;
                    });
        }


        if($report_for == 'devices'){
                $query
                    ->select([
                        'id'=>$user_special_needs_devices->aliasField('id'),
                        'academic_period' => 'AcademicPeriods.name',                                
                        'institution_code' => 'Institutions.code',                                
                        'institution_name' => 'Institutions.name',                                
                        'education_grade' => 'EducationGrades.name',                                
                        'openemis_id' => 'SecurityUser.openemis_no',                                
                        'security_user_name' => $security_users->find()->func()->concat([
                            'SecurityUser.first_name' => 'literal',
                            " ",
                            'SecurityUser.last_name' => 'literal'
                            ]),
                        'age' => 'SecurityUser.date_of_birth',                   
                        'gender' => 'Gender.name',
                        'identity_type' => 'IdentityType.name',
                        'identity_number' => 'SecurityUser.identity_number',
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
                    ->leftJoin(['Gender' => $genders->table()], [
                        'Gender.id = '.'SecurityUser.gender_id'
                    ])
                    ->leftJoin(['IdentityType' => $identity_types->table()], [
                        'IdentityType.id = '.'SecurityUser.identity_type_id'
                    ])
                    ->contain([
                                'Institutions',
                                'EducationGrades',
                                'AcademicPeriods'
                            ])
                    ->where([
                        $this->aliasField('academic_period_id') => $academic_period_id,
                        $user_special_needs_devices->aliasField('created >=') => date('Y-m-d H:i:s',strtotime($requestData->report_start_date)),
                        $user_special_needs_devices->aliasField('created <=') => date('Y-m-d H:i:s',strtotime($requestData->report_end_date)),
                        $where
                    ])
                    ->order([
                        $user_special_needs_devices->aliasField('id') => 'DESC'
                    ]);
                   
                    $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
                        $idarr = [];
                        $final_opt = [];
                        foreach($results as $key => $res){
                            if(!in_array($res->id,$idarr)){
                                array_push($idarr,$res->id);
                                $diff = abs(strtotime(date('Y-m-d'))-strtotime($res->age));  
                                $years = floor($diff / (365*60*60*24));
                                $res['age'] = $years;
                                array_push($final_opt,$res);
                            }
                        }
                        return $final_opt;
                    });
        }

        if($report_for == 'plans'){
                $query
                    ->select([
                        'id'=>$user_special_needs_plans->aliasField('id'),
                        'academic_period' => 'AcademicPeriods.name',                                
                        'institution_code' => 'Institutions.code',                                
                        'institution_name' => 'Institutions.name',                                
                        'education_grade' => 'EducationGrades.name',                                
                        'openemis_id' => 'SecurityUser.openemis_no',                                
                        'security_user_name' => $security_users->find()->func()->concat([
                            'SecurityUser.first_name' => 'literal',
                            " ",
                            'SecurityUser.last_name' => 'literal'
                            ]),
                        'age' => 'SecurityUser.date_of_birth',                   
                        'gender' => 'Gender.name',
                        'identity_type' => 'IdentityType.name',
                        'identity_number' => 'SecurityUser.identity_number',
                        'plan_name'=>$user_special_needs_plans->aliasField('plan_name'),       
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
                    ->leftJoin(['Gender' => $genders->table()], [
                        'Gender.id = '.'SecurityUser.gender_id'
                    ])
                    ->leftJoin(['IdentityType' => $identity_types->table()], [
                        'IdentityType.id = '.'SecurityUser.identity_type_id'
                    ])
                    ->contain([
                                'Institutions',
                                'EducationGrades'
                            ])
                    ->where([
                        $user_special_needs_plans->aliasField('academic_period_id') => $academic_period_id,
                        $where
                    ])
                    ->order([
                        $user_special_needs_plans->aliasField('created') => 'DESC'
                    ]);

                    $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
                        $idarr = [];
                        $final_opt = [];
                        foreach($results as $key => $res){
                            if(!in_array($res->id,$idarr)){
                                array_push($idarr,$res->id);
                                $diff = abs(strtotime(date('Y-m-d'))-strtotime($res->age));  
                                $years = floor($diff / (365*60*60*24));
                                $res['age'] = $years;
                                array_push($final_opt,$res);
                            }
                        }
                        return $final_opt;
                    });

        }

        if($report_for == 'diagnostics'){
            $query
                ->select([
                    'id'=>$user_special_needs_diagnostics->aliasField('id'),
                    'academic_period' => 'AcademicPeriods.name',                                
                    'institution_code' => 'Institutions.code',                                
                    'institution_name' => 'Institutions.name',                                
                    'education_grade' => 'EducationGrades.name',                                
                    'openemis_id' => 'SecurityUser.openemis_no',                                
                    'security_user_name' => $security_users->find()->func()->concat([
                        'SecurityUser.first_name' => 'literal',
                        " ",
                        'SecurityUser.last_name' => 'literal'
                        ]),
                    'age' => 'SecurityUser.date_of_birth',                   
                    'gender' => 'Gender.name',
                    'identity_type' => 'IdentityType.name',
                    'identity_number' => 'SecurityUser.identity_number',
                    'date'=>$user_special_needs_diagnostics->aliasField('date'),           
                    'comment'=>$user_special_needs_diagnostics->aliasField('comment'),       
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
                ->leftJoin(['Gender' => $genders->table()], [
                    'Gender.id = '.'SecurityUser.gender_id'
                ])
                ->leftJoin(['IdentityType' => $identity_types->table()], [
                    'IdentityType.id = '.'SecurityUser.identity_type_id'
                ])
                ->contain([
                            'Institutions',
                            'AcademicPeriods',
                            'EducationGrades'
                        ])
                ->where([
                    $this->aliasField('academic_period_id') => $academic_period_id,
                    $user_special_needs_diagnostics->aliasField('date >=') => date('Y-m-d',strtotime($requestData->report_start_date)),
                    $user_special_needs_diagnostics->aliasField('date <=') => date('Y-m-d',strtotime($requestData->report_end_date)),
                    $where
                ])
                ->order([
                    $user_special_needs_diagnostics->aliasField('id') => 'DESC'
                ]);

                $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
                    $idarr = [];
                    $final_opt = [];
                    foreach($results as $key => $res){
                        if(!in_array($res->id,$idarr)){
                            array_push($idarr,$res->id);
                            $diff = abs(strtotime(date('Y-m-d'))-strtotime($res->age));  
                            $years = floor($diff / (365*60*60*24));
                            $res['age'] = $years;
                            array_push($final_opt,$res);
                        }
                    }
                    return $final_opt;
                });
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
        $report_for = json_decode($settings['process']['params'], true)['special_needs_feature'];
        
        if($report_for == 'referral'){
            $newFields[] = [
                'key' => '',
                'field' => 'academic_period',
                'type' => 'string',
                'label' => __('Academic Period')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'institution_code',
                'type' => 'string',
                'label' => __('Institution Code')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'institution_name',
                'type' => 'string',
                'label' => __('Institution Name')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'education_grade',
                'type' => 'string',
                'label' => __('Education Grade')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'openemis_id',
                'type' => 'string',
                'label' => __('openEMIS ID')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'security_user_name',
                'type' => 'string',
                'label' => __('Name')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'age',
                'type' => 'string',
                'label' => __('Age')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'gender',
                'type' => 'string',
                'label' => __('Gender')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'identity_type',
                'type' => 'string',
                'label' => __('Identity Type')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'identity_number',
                'type' => 'string',
                'label' => __('Identity Number')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'date_of_referral',
                'type' => 'date',
                'label' => __('Date of Referral')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'referral_comment',
                'type' => 'string',
                'label' => __('Referral Comment')
            ];
           
            $newFields[] = [
                'key' => '',
                'field' => 'referrer_openemis_id',
                'type' => 'string',
                'label' => __('Referrer OpenEMIS ID')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'referrer_name',
                'type' => 'string',
                'label' => __('Referrer Name')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'special_need_referrer_type_name',
                'type' => 'string',
                'label' => __('Referral Type')
            ];

        }

        if($report_for == 'assessments'){

           $newFields[] = [
                'key' => '',
                'field' => 'education_grade',
                'type' => 'string',
                'label' => __('Education Grade')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'openemis_id',
                'type' => 'string',
                'label' => __('openEMIS ID')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'security_user_name',
                'type' => 'string',
                'label' => __('Name')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'age',
                'type' => 'string',
                'label' => __('Age')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'gender',
                'type' => 'string',
                'label' => __('Gender')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'identity_type',
                'type' => 'string',
                'label' => __('Identity Type')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'identity_number',
                'type' => 'string',
                'label' => __('Identity Number')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'date',
                'type' => 'date',
                'label' => __('Date')
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
                'label' => __('Special Need Difficulty')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'assessor_openemis_id',
                'type' => 'string',
                'label' => __('Assesor OpenEMIS ID')
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
                'field' => 'institution_code',
                'type' => 'string',
                'label' => __('Institution Code')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'institution_name',
                'type' => 'string',
                'label' => __('Institution Name')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'education_grade',
                'type' => 'string',
                'label' => __('Education Grade')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'openemis_id',
                'type' => 'string',
                'label' => __('openEMIS ID')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'security_user_name',
                'type' => 'string',
                'label' => __('Name')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'age',
                'type' => 'string',
                'label' => __('Age')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'gender',
                'type' => 'string',
                'label' => __('Gender')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'identity_type',
                'type' => 'string',
                'label' => __('Identity Type')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'identity_number',
                'type' => 'string',
                'label' => __('Identity Number')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'special_need_service_type',
                'type' => 'string',
                'label' => __('Service Type')
            ];          

        }

        if($report_for == 'devices'){

            $newFields[] = [
                'key' => '',
                'field' => 'institution_code',
                'type' => 'string',
                'label' => __('Institution Code')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'institution_name',
                'type' => 'string',
                'label' => __('Institution Name')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'academic_period',
                'type' => 'string',
                'label' => __('Academic Period')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'education_grade',
                'type' => 'string',
                'label' => __('Education Grade')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'openemis_id',
                'type' => 'string',
                'label' => __('openEMIS ID')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'security_user_name',
                'type' => 'string',
                'label' => __('Name')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'age',
                'type' => 'string',
                'label' => __('Age')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'gender',
                'type' => 'string',
                'label' => __('Gender')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'identity_type',
                'type' => 'string',
                'label' => __('Identity Type')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'identity_number',
                'type' => 'string',
                'label' => __('Identity Number')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'special_need_device_type',
                'type' => 'string',
                'label' => __('Device Type')
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
                'field' => 'institution_code',
                'type' => 'string',
                'label' => __('Institution Code')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'institution_name',
                'type' => 'string',
                'label' => __('Institution Name')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'education_grade',
                'type' => 'string',
                'label' => __('Education Grade')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'openemis_id',
                'type' => 'string',
                'label' => __('openEMIS ID')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'security_user_name',
                'type' => 'string',
                'label' => __('Name')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'age',
                'type' => 'string',
                'label' => __('Age')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'gender',
                'type' => 'string',
                'label' => __('Gender')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'identity_type',
                'type' => 'string',
                'label' => __('Identity Type')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'identity_number',
                'type' => 'string',
                'label' => __('Identity Number')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'plan_name',
                'type' => 'string',
                'label' => __('Plan Name')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'special_need_plan_type',
                'type' => 'string',
                'label' => __('Plan Type')
            ];
        }

        if($report_for == 'diagnostics'){
            $newFields[] = [
                'key' => '',
                'field' => 'institution_code',
                'type' => 'string',
                'label' => __('Institution Code')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'institution_name',
                'type' => 'string',
                'label' => __('Institution Name')
            ];
            
            $newFields[] = [
                'key' => '',
                'field' => 'academic_period',
                'type' => 'string',
                'label' => __('Academic Period')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'education_grade',
                'type' => 'string',
                'label' => __('Education Grade')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'openemis_id',
                'type' => 'string',
                'label' => __('openEMIS ID')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'security_user_name',
                'type' => 'string',
                'label' => __('Name')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'age',
                'type' => 'string',
                'label' => __('Age')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'gender',
                'type' => 'string',
                'label' => __('Gender')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'identity_type',
                'type' => 'string',
                'label' => __('Identity Type')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'identity_number',
                'type' => 'string',
                'label' => __('Identity Number')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'date',
                'type' => 'date',
                'label' => __('Date')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'comment',
                'type' => 'string',
                'label' => __('Comment')
            ]; 
            
            $newFields[] = [
                'key' => '',
                'field' => 'special_need_diagnostic_type',
                'type' => 'string',
                'label' => __('Diagnostic Type')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'special_need_diagnostic_degree',
                'type' => 'string',
                'label' => __('Diagnostic Degree')
            ];
        }
        $fields->exchangeArray($newFields);
    }

    // public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) 
    // {
    //     $newFields[] = [
    //         'key' => 'Institutions.code',
    //         'field' => 'code',
    //         'type' => 'string',
    //         'label' => __('Institution Code')
    //     ];

    //     $newFields[] = [
    //         'key' => 'Institutions.name',
    //         'field' => 'institution_name',
    //         'type' => 'string',
    //         'label' => __('Institution Name')
    //     ];

    //     $newFields[] = [
    //         'key' => 'AcademicPeriods.name',
    //         'field' => 'academic_period',
    //         'type' => 'string',
    //         'label' => __('Academic Period')
    //     ];

    //     $newFields[] = [
    //         'key' => 'EducationGrades.name',
    //         'field' => 'education_grade',
    //         'type' => 'string',
    //         'label' => __('Education Grade')
    //     ];

    //     $newFields[] = [
    //         'key' => 'Users.openemis_no',
    //         'field' => 'openemis_no',
    //         'type' => 'string',
    //         'label' => __('OpenEMIS ID')
    //     ];
    //     $newFields[] = [
    //         'key' => '',
    //         'field' => 'student_name',
    //         'type' => 'string',
    //         'label' => __('Name')
    //     ];
    //     $newFields[] = [
    //         'key' => 'Users.age',
    //         'field' => 'age',
    //         'type' => 'string',
    //         'label' => __('Age')
    //     ];
    //     $newFields[] = [
    //         'key' => 'Genders.name',
    //         'field' => 'gender',
    //         'type' => 'string',
    //         'label' => __('Gender')
    //     ];
    //     $newFields[] = [
    //         'key' => 'IdentityTypes.name',
    //         'field' => 'identity_type',
    //         'type' => 'string',
    //         'label' => __('Identity Type')
    //     ];
    //     $newFields[] = [
    //         'key' => 'Users.identity_number',
    //         'field' => 'identity_number',
    //         'type' => 'integer',
    //         'label' => __('Identity Number')
    //     ];
    //     $newFields[] = [
    //         'key' => 'SpecialNeedsTypes.name',
    //         'field' => 'special_need_type',
    //         'type' => 'string',
    //         'label' => __('Disability Type')
    //     ];
    //     $newFields[] = [
    //         'key' => 'SpecialNeedsDifficulties.name',
    //         'field' => 'special_need_difficulty_type',
    //         'type' => 'string',
    //         'label' => __('Difficulty Type')
    //     ];
    //     $newFields[] = [
    //         'key' => 'SpecialNeedsServiceTypes.name',
    //         'field' => 'special_need_service_type',
    //         'type' => 'string',
    //         'label' => __('Program Assigned')
    //     ];
    //     $newFields[] = [
    //         'key' => 'SpecialNeedsServices.organization',
    //         'field' => 'organization',
    //         'type' => 'string',
    //         'label' => __('Organization')
    //     ];
    //     $newFields[] = [
    //         'key' => 'GuardianRelations.name',
    //         'field' => 'guardian_relation',
    //         'type' => 'string',
    //         'label' => __('Guardian Relations')
    //     ];
    //     $newFields[] = [
    //         'key' => 'GuardianRelations.openemis_no',
    //         'field' => 'guardian_openemis_no',
    //         'type' => 'string',
    //         'label' => __('Guardian OpenEMIS ID')
    //     ];
    //     $newFields[] = [
    //         'key' => '',
    //         'field' => 'guardian_name',
    //         'type' => 'string',
    //         'label' => __('Guardian Name')
    //     ];
    //     $newFields[] = [
    //         'key' => '',
    //         'field' => 'guardian_contact_number',
    //         'type' => 'string',
    //         'label' => __('Guardian Contact Number')
    //     ];

    //     $newFields[] = [
    //         'key' => '',
    //         'field' => 'staff_name',
    //         'type' => 'string',
    //         'label' => __('Referrer Staff Name')
    //     ];

    //     $newFields[] = [
    //         'key' => '',
    //         'field' => 'staff_contact',
    //         'type' => 'string',
    //         'label' => __('Referrer Staff Contact number')
    //     ];

    //     $fields->exchangeArray($newFields);
    // }
}
