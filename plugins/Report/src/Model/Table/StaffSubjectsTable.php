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

        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $periodEntity = $AcademicPeriods->get($academicPeriodId);
        $startDate = $periodEntity->start_date->format('Y-m-d');
        $endDate = $periodEntity->end_date->format('Y-m-d');
        $InstitutionStaff = TableRegistry::get('Institution.InstitutionStaff');
        $Staff = TableRegistry::get('Security.Users');
        $Genders = TableRegistry::get('User.Genders');

        //$Staff = TableRegistry::get('Institution.InstitutionSubjects');
        $MainNationalities = TableRegistry::get('FieldOption.Nationalities');
        $UserIdentities = TableRegistry::get('User.Identities');
        //$StaffQualifications = TableRegistry::get('StaffQualifications');
        $eduGrade = TableRegistry::get('education_grades');
        $conditions = [];
        if (!empty($academicPeriodId)) {
                $conditions['OR'] = [
                    'OR' => [
                        [
                            $InstitutionStaff->aliasField('end_date') . ' IS NOT NULL',
                            $InstitutionStaff->aliasField('start_date') . ' <=' => $startDate,
                            $InstitutionStaff->aliasField('end_date') . ' >=' => $startDate
                        ],
                        [
                            $InstitutionStaff->aliasField('end_date') . ' IS NOT NULL',
                            $InstitutionStaff->aliasField('start_date') . ' <=' => $endDate,
                            $InstitutionStaff->aliasField('end_date') . ' >=' => $endDate
                        ],
                        [
                            $InstitutionStaff->aliasField('end_date') . ' IS NOT NULL',
                            $InstitutionStaff->aliasField('start_date') . ' >=' => $startDate,
                            $InstitutionStaff->aliasField('end_date') . ' <=' => $endDate
                        ]
                    ],
                    [
                        $InstitutionStaff->aliasField('end_date') . ' IS NULL',
                        $InstitutionStaff->aliasField('start_date') . ' <=' => $endDate
                    ]
                ];
        }
        if (!empty($institutionId) && $institutionId > 0) {
            $conditions['Institutions.id'] = $institutionId; 
        }
        if (!empty($areaId) && $areaId != -1) {
            $conditions['Institutions.area_id'] = $areaId; 
        }
        $query
            ->select([
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
                
                'identity_number' => 'Users.identity_number'
                //'qualification'=>  $StaffQualifications->aliasField('qualification_institution')
            ])
            ->contain([
              
                'Institutions' => [
                    'fields' => [
                        'Institutions.id',
                        'Institutions.name',
                        'Institutions.code'
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
                        $row['userIdentityTypes'] =[];
                        foreach($userIdTypes as $ss =>$userIDType){
                            $row['userIdentityTypes'][$ss] =  $IdentityTypesss->find()->where(['id'=>$userIDType->identity_type_id])->first();
                            $row['userIdentityTypes'][$ss]['number'] = $userIDType->number;
                        }
                        //assign value in column
                        foreach($row['userIdentityTypes'] as $sss =>$useroneIDType){
                            $row[str_replace(" ","_",$useroneIDType->name)] = $useroneIDType->number;
                        }
                        //staff qulification staff_qualifications************
                        $staffQualificationss = TableRegistry::get('staff_qualifications');
                        $Qualificationss = TableRegistry::get('qualification_titles');
                        $sQu= $staffQualificationss->find()->where(['staff_id'=>$row->staff_id])->first();

                        $qulifi = $Qualificationss->find()->where(['id'=>$sQu->qualification_title_id])->first();
                        $row['qualification'] = $qulifi->name;

                        //Field of stydy****************
                        $sfieldofStydyT = TableRegistry::get('education_field_of_studies');
                        $sfieldofStydy = $sfieldofStydyT->find()->where(['id'=>$sQu->education_field_of_study_id])->first();
                        $row['field_of_study'] = $sfieldofStydy->name;

                        //class***********
                        $InstitutionClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');
                        $Classes = TableRegistry::get('Institution.InstitutionClasses');
                        $GradesClassT = TableRegistry::get('institution_class_grades');
                        $GradeT = TableRegistry::get('education_grades');
                        
                        $staff_id = $row->staff_id;
                        $obj =$this->find()
                        ->select([$Classes->aliasField('name'),$Classes->aliasField('id')])
                        ->leftJoin(
                            [$InstitutionClassSubjects->alias() => $InstitutionClassSubjects->table()],
                            [
                                $InstitutionClassSubjects->aliasField('institution_subject_id = ') . $this->aliasField('institution_subject_id')
                            ]
                        )
                        ->leftJoin(
                            [$Classes->alias() => $Classes->table()],
                            [
                                $Classes->aliasField('id = ') . $InstitutionClassSubjects->aliasField('institution_class_id')
                            ]
                        )
                        ->where([$this->aliasField('staff_id') => $staff_id])
                        ->group([$Classes->aliasField('name')]);
                       foreach($obj->toArray() as $k=>$obj1){ 
                        //Grade
                            $row['classes'] .= $obj1->InstitutionClasses['name'].",";
                            $GradesClass = $GradesClassT->find()->where(['institution_class_id' => $obj1->InstitutionClasses['id']])->first();
                            $Grade = $GradeT->find()->where(['id'=>$GradesClass->education_grade_id])->first();
                            $row['grades'] .= $Grade->name.",";     
                       }
                       $row['classes'] = rtrim($row['classes'], ',');
                       $row['grades'] = rtrim($row['grades'], ',');
                       $row['grades'] = implode(',',array_unique(explode(',', $row['grades'])));

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

        $newFields = [];

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

        foreach($userIdTypes as $userIdType){
            $newFields[] = [
                'key' => '',
                'field' => str_replace(' ', '_',$userIdType->name),
                'type' => 'string',
                'label' => __($userIdType->name)
            ];
        }

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
