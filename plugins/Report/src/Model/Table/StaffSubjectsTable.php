<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;

class StaffSubjectsTable extends AppTable  {
	public function initialize(array $config) {
		$this->table('institution_subject_staff');
		parent::initialize($config);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('InstitutionSubjects', ['className' => 'Institution.InstitutionSubjects']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
		//$this->belongsTo('Institution.EducationGrades', ['className' => 'Institution.EducationGrades']);
		
		$this->addBehavior('Excel',[
            'excludes' => [],
            'pages' => ['index'],
        ]);
		$this->addBehavior('Report.ReportList');
	}

	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
		$requestData = json_decode($settings['process']['params']);
        $areaId = $requestData->area_education_id;
        $institutionId = $requestData->institution_id;
        $academicPeriodId = $requestData->academic_period_id;
        //Start:POCOR-6779
        $indSubjectId = $requestData->education_subject_id;
        $education_grade_id = $requestData->education_grade_id;
        //End:POCOR-6779

        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $periodEntity = $AcademicPeriods->get($academicPeriodId);
        $startDate = $periodEntity->start_date->format('Y-m-d');
        $endDate = $periodEntity->end_date->format('Y-m-d');
        $InstitutionStaff = TableRegistry::get('Institution.InstitutionStaff');
        $Staff = TableRegistry::get('Security.Users');
        $Genders = TableRegistry::get('User.Genders');
        $MainNationalities = TableRegistry::get('FieldOption.Nationalities');
        $InstitutionSubjj = TableRegistry::get('institution_subjects'); //POCOR-6779
        //Start:POCOR-6779
        if(!empty($academicPeriodId) && !empty($institutionId) && !empty($education_grade_id) && !empty($indSubjectId)){
            $institutionStaffaa = $InstitutionSubjj->find()->where(['institution_id'=>$institutionId,'education_grade_id'=>$education_grade_id,'education_subject_id'=>$indSubjectId,'academic_period_id'=>$academicPeriodId])->first();
        }else if(!empty($academicPeriodId) && !empty($education_grade_id) && !empty($indSubjectId)){
            $institutionStaffaa = $InstitutionSubjj->find()->where(['education_grade_id'=>$education_grade_id,'education_subject_id'=>$indSubjectId,'academic_period_id'=>$academicPeriodId])->first();
        }else if(!empty($academicPeriodId) && !empty($indSubjectId)){
            $institutionStaffaa = $InstitutionSubjj->find()->where(['education_subject_id'=>$indSubjectId,'academic_period_id'=>$academicPeriodId])->first();
        }
        $ins_SubId = $institutionStaffaa->id;
        //End:POCOR-6779
        $conditions = [];
        if (!empty($academicPeriodId)) {
            if($this->aliasField('end_date') == null){
                $conditions = [
                    $this->aliasField('start_date') . ' >=' => $startDate,
                    $this->aliasField('end_date') . ' <=' => $endDate
                ];
            }else{
                $conditions = [
                    $this->aliasField('start_date') . ' >=' => $startDate,
                    $this->aliasField('start_date') . ' <=' => $endDate
                ];
            }
            
        }
        //Start:POCOR-6779
        if(!empty($indSubjectId)){
            $conditions['institution_subject_id'] = $ins_SubId; 
        }
        //End:POCOR-6779
        if (!empty($institutionId) && $institutionId > 0) {
            $conditions['Institutions.id'] = $institutionId; 
        }
        if (!empty($areaId) && $areaId != -1) {
            $conditions['Institutions.area_id'] = $areaId; 
        }
        $query
            ->select([
                // 'academic_period_id' => 'InstitutionSubjects.id',
                'institution_code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',  
                'nationality_id' => 'Users.nationality_id',             
                'institution_subject_name' => 'InstitutionSubjects.name',
                'teacher_empd_no' => $Staff->aliasField('openemis_no'),
                'institution_subject_id'=> $this->aliasField('institution_subject_id'),
                'openemis_no' => $Staff->aliasField('openemis_no'),
                'staff_id' => $Staff->aliasField('id'),
                'first_name' => $Staff->aliasField('first_name'),
                'middle_name' => $Staff->aliasField('middle_name'),
                'third_name' => $Staff->aliasField('third_name'),
                'last_name' => $Staff->aliasField('last_name'),
                'gender' => $Genders->aliasField('name'),
                'nationality_name' => $MainNationalities->aliasField('name'),
                'identity_type_id' => $Staff->aliasField('identity_type_id'),
                'identity_number' => 'Users.identity_number',
                'start_date' => $this->aliasField('start_date'),
                'end_date' => $this->aliasField('end_date'),
                
            ])
            ->contain([
              
                'Institutions' => [
                    'fields' => [
                        'Institutions.id',
                        'Institutions.name',
                        'Institutions.code',
                        'Institutions.area_id'//POCOR-6779
                    ]
                ],
                'InstitutionSubjects' => [
                    'fields' => [
                        'InstitutionSubjects.name',
                        'InstitutionSubjects.id'
                    ]
                ],
                'Users'=>[
                    'fields' =>[
                        'identity_number' => 'Users.identity_number',
                        'nationality_id' => 'Users.nationality_id',
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
            ->leftJoin(
                    [$Staff->alias() => $Staff->table()],
                    [
                        $Staff->aliasField('id = ') . $InstitutionStaff->aliasField('staff_id')
                    ]
                )
            ->leftJoin(
                    [$Genders->alias() => $Genders->table()],
                    [
                        $Genders->aliasField('id = ') . $Staff->aliasField('gender_id')
                    ]
                )
            ->leftJoin(
                [$MainNationalities->alias() => $MainNationalities->table()],
                [
                    $MainNationalities->aliasField('id = ') . $Staff->aliasField('nationality_id')
                ]
            )
            ->where([$conditions])
            ->order(['institution_name']);

            $query->formatResults(function (\Cake\Collection\CollectionInterface $results) { 
                return $results->map(function ($row) { 
                        //for identity type *********
                        $IdentityTypesss = TableRegistry::get('identity_types');
                        //Dynamic fields*******identity_types*****
                        $user_identities_table = TableRegistry::get('user_identities');
                        $userIdTypes = $user_identities_table->find()->where(['security_user_id'=>$row->staff_id])->all();
                        //Start:POCOR-6779
                        $defaultIdType = $IdentityTypesss->find()->where(['default' =>1 ])->first();
                        $row['userIdentityTypes'] =[];
                        foreach($userIdTypes as $ss =>$userIDType){
                            if($userIDType->identity_type_id == $defaultIdType->id){   
                                $row[str_replace(' ', '_',$defaultIdType->name)] = $userIDType->number;
                            }else{
                                $idTypeData = $IdentityTypesss->find()->where(['id'=>$userIDType->identity_type_id])->first();
                                $row['other_ids'] .=  '(['.$idTypeData->name.'] - '.$userIDType->number.'),';
                            }
                        }
                        $row['other_ids'] = rtrim( $row['other_ids'],',');
                        //End:POCOR-6779
                        //assign value in column
                        foreach($row['userIdentityTypes'] as $sss =>$useroneIDType){
                            $row[str_replace(" ","_",$useroneIDType->name)] = $useroneIDType->number;
                        }
                        //staff qulification staff_qualifications************
                        $staffQualificationss = TableRegistry::get('staff_qualifications');
                        $Qualificationss = TableRegistry::get('qualification_titles');
                        $sQu= $staffQualificationss->find()->where(['staff_id'=>$row->staff_id])->order(['qualification_title_id'=>'DESC'])->first(); //POCOR-6779

                        $qulifi = $Qualificationss->find()->where(['id'=>$sQu->qualification_title_id])->first();
                        $row['qualification'] = $qulifi->name;

                        //Field of stydy****************
                        $sfieldofStydyT = TableRegistry::get('education_field_of_studies');
                        $sfieldofStydy = $sfieldofStydyT->find()->where(['id'=>$sQu->education_field_of_study_id])->first();
                        $row['field_of_study'] = $sfieldofStydy->name;

                        
                       //grade
                       $InstitutionSubjectT = TableRegistry::get('institution_subjects');
                       $GradeT = TableRegistry::get('education_grades');
                       $InstitutionSubjectDta = $InstitutionSubjectT->find()->where(['id' => $row->institution_subject_id])->first();
                       $Grade = $GradeT->find()->where(['id'=>$InstitutionSubjectDta->education_grade_id])->first();
                       $row['grades'] = $Grade->name;

                       //class***********
                       $InstitutionClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');
                       $Classes = TableRegistry::get('Institution.InstitutionClasses');
                       $GradesClassData = $InstitutionClassSubjects->find()->where(['institution_subject_id'=>$row->institution_subject_id])->first();
                       $classData = $Classes->find()->where(['id'=>$GradesClassData->institution_class_id])->first();
                       $row['classes'] = $classData->name;
                        
                       //Start:POCOR-6779
                       //Staff Area Name**
                       $AreaT = TableRegistry::get('areas');
                       $AreaData = $AreaT->find()->where(['id' => $row->institution->area_id])->first();
                       $row['area_name'] = $AreaData->name;

                       //Staff Status Name**
                       $institution_staffT = TableRegistry::get('institution_staff');
                       $StaffStatusT = TableRegistry::get('staff_statuses');
                       $insStaff = $institution_staffT->find()->where(['staff_id' => $row->staff_id,'institution_id'=>$row->institution->id])->first();
                       $staffStatusss = $StaffStatusT->find()->where(['id' => $insStaff->staff_status_id])->first();
                       
                       $row['staff_status'] = $staffStatusss->name;
                       //End:POCOR-6779
                    return $row;
                });
            });
	}


    function array_map_assoc( $callback , $array ){
        $r = array();
        foreach ($array as $key=>$value)
          $r[$key] = $callback($key,$value);
        return $r;
    }

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $IdentityTypesss = TableRegistry::get('identity_types');
        $userIdTypes = $IdentityTypesss->find()->all();
        $defaultIdType = $IdentityTypesss->find()->where(['default' =>1 ])->first();

        $newFields = [];
        //Start:POCOR-6779
        $newFields[] = [
            'key' => '',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __('Area Education')
        ];
        //End:POCOR-6779
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
            'label' => __('Institution')
        ];
        //Start:POCOR-6779
        $newFields[] = [
            'key' => 'openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];
        

        $newFields[] = [
            'key' => '',
            'field' => str_replace(' ', '_',$defaultIdType->name),
            'type' => 'string',
            'label' => __($defaultIdType->name)
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'other_ids',
            'type' => 'string',
            'label' => __('Other Identities')
        ];
        //End:POCOR-6779
        
		$newFields[] = [
            'key' => '',
            'field' => 'first_name',
            'type' => 'string',
            'label' => __('First Name')
        ];

		$newFields[] = [
            'key' => 'middle_name',
            'field' => 'middle_name',
            'type' => 'string',
            'label' => __('Middle Name'),
        ];

        $newFields[] = [
            'key' => 'third_name',
            'field' => 'third_name',
            'type' => 'string',
            'label' => __('Third Name'),
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'last_name',
            'type' => 'string',
            'label' => __('Last Name')
        ];

		$newFields[] = [
            'key' => '',
            'field' => 'gender',
            'type' => 'string',
            'label' => __('Gender')
        ];

		$newFields[] = [
            'key' => '',
            'field' => 'nationality_name',
            'type' => 'string',
            'label' => __('Nationality')
        ];
        //Start:POCOR-6779
        $newFields[] = [
            'key' => '',
            'field' => 'staff_status',
            'type' => 'string',
            'label' => __('Staff Status')
        ];
        //End:POCOR-6779
		$newFields[] = [
            'key' => '',
            'field' => 'qualification',
            'type' => 'string',
            'label' => __('Qualification Title')
        ];
		$newFields[] = [
            'key' => '',
            'field' => 'field_of_study',
            'type' => 'string',
            'label' => __('Field Of Study')
        ];	

		$newFields[] = [
            'key' => 'InstitutionSubjects.name',
            'field' => 'institution_subject_name',
            'type' => 'string',
            'label' => __('Subject')
        ];	
		$newFields[] = [
            'key' => '',
            'field' => 'grades',
            'type' => 'string',
            'label' => __('Grade')
        ];	
		$newFields[] = [
            'key' => '',
            'field' => 'classes',
            'type' => 'string',
            'label' => __('Class')
        ];	
        
        $fields->exchangeArray($newFields);
    }
}
