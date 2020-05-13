<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Database\Expression\IdentifierExpression;

class GuardiansTable extends AppTable  {

   public function initialize(array $config) {
        
		$this->table('student_guardians');
		parent::initialize($config);
        $this->addBehavior('Report.ReportList');
		$this->addBehavior('Excel', [
            'pages' => false
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
    
    
    public function onExcelGetStudentNameByGuardian(Event $event, Entity $entity) {
        $securityUsers = TableRegistry::get('security_users');

        if (!is_null($entity->student_id)) {
            $getStudent = $securityUsers->find()
                ->select(['security_users.first_name','security_users.last_name'])
                ->leftJoin(['Guardians' => 'student_guardians'], [
                    'security_users.id = ' . 'Guardians.student_id'
                ])
                ->where(['Guardians.student_id'=>$entity->student_id])
                ->first();

            if(!empty($getStudent)){
                return $getStudent->first_name . ' ' . $getStudent->last_name;
            }

        }

        return '';
    }

    public function onExcelGetGuardianFatherName(Event $event, Entity $entity) {
   
        $guardianData = TableRegistry::get('security_users');
        $name = '';

            if (!is_null($entity->student_id)) {
                $getGuardian = $guardianData->find()
                            ->select(['security_users.first_name','security_users.last_name'])
                            ->leftJoin(['Guardians' => 'student_guardians'], [
                                'security_users.id = ' . 'Guardians.guardian_id',
                                
                            ])
                            ->leftJoin(['GuardiansRelation' => 'guardian_relations'], [
                                'Guardians.guardian_relation_id = ' . 'GuardiansRelation.id',
                            ])
                            ->where(['Guardians.student_id'=>$entity->student_id,'GuardiansRelation.name'=>'Father'])
                            ->first();

                    if(!empty($getGuardian)){
                        $name = $getGuardian->first_name.' '.$getGuardian->last_name;
                    }

            }

        return $name;
    }


    public function onExcelGetFatherEmail(Event $event, Entity $entity) {
   
        $guardianData = TableRegistry::get('security_users');
        $fatherEmail = '';

            if (!is_null($entity->student_id)) {
                $getGuardian = $guardianData->find()
                            ->select(['security_users.email'])
                            ->leftJoin(['Guardians' => 'student_guardians'], [
                                'security_users.id = ' . 'Guardians.guardian_id',
                                
                            ])
                            ->leftJoin(['GuardiansRelation' => 'guardian_relations'], [
                                'Guardians.guardian_relation_id = ' . 'GuardiansRelation.id',
                            ])
                            ->where(['Guardians.student_id'=>$entity->student_id, 'GuardiansRelation.name'=>'Father'])
                            ->first();

                    if(!empty($getGuardian)){
                        $fatherEmail = $getGuardian->email;
                    }

            }

        return $fatherEmail;
    }


    public function onExcelGetFatherAddress(Event $event, Entity $entity) {
   
        $guardianData = TableRegistry::get('security_users');
        $fatherAddress = '';

            if (!is_null($entity->student_id)) {
                $getGuardian = $guardianData->find()
                            ->select(['security_users.address'])
                            ->leftJoin(['Guardians' => 'student_guardians'], [
                                'security_users.id = ' . 'Guardians.guardian_id',
                                
                            ])
                            ->leftJoin(['GuardiansRelation' => 'guardian_relations'], [
                                'Guardians.guardian_relation_id = ' . 'GuardiansRelation.id',
                            ])
                            ->where(['Guardians.student_id'=>$entity->student_id, 'GuardiansRelation.name'=>'Father'])
                            ->first();

                    if(!empty($getGuardian)){
                        $fatherAddress = $getGuardian->address;
                    }

            }

        return $fatherAddress;
    }



     public function onExcelGetGuardianMotherName(Event $event, Entity $entity){
   
        $guardianData = TableRegistry::get('security_users');
        $motherName = '';
        
            if (!is_null($entity->student_id)) {
                
                $motherDetails = $guardianData->find()
                            ->select(['security_users.first_name','security_users.last_name'])
                            ->leftJoin(['Guardians' => 'student_guardians'], [
                                'security_users.id = ' . 'Guardians.guardian_id',
                                
                            ])
                            ->leftJoin(['GuardiansRelation' => 'guardian_relations'], [
                                'Guardians.guardian_relation_id = ' . 'GuardiansRelation.id',
                            ])
                            ->where(['Guardians.student_id'=>$entity->student_id, 'GuardiansRelation.name'=>'Mother'])
                            ->first();

                    if(!empty($motherDetails)){
                        $motherName = $motherDetails->first_name.' '.$motherDetails->last_name;
                    }
            }

        return $motherName;
        
        }

        public function onExcelGetMotherEmail(Event $event, Entity $entity) {
   
            $guardianData = TableRegistry::get('security_users');
            $motherEmail = '';
    
                if (!is_null($entity->student_id)) {
                    $motherDetails = $guardianData->find()
                                ->select(['security_users.email'])
                                ->leftJoin(['Guardians' => 'student_guardians'], [
                                    'security_users.id = ' . 'Guardians.guardian_id',
                                    
                                ])
                                ->leftJoin(['GuardiansRelation' => 'guardian_relations'], [
                                    'Guardians.guardian_relation_id = ' . 'GuardiansRelation.id',
                                ])
                                ->where(['Guardians.student_id'=>$entity->student_id, 'GuardiansRelation.name'=>'Mother'])
                                ->first();
    
                        if(!empty($motherDetails)){
                            $motherEmail = $motherDetails->email;
                        }
    
                }

            return $motherEmail;
        }
    
    
        public function onExcelGetMotherAddress(Event $event, Entity $entity) {
       
            $guardianData = TableRegistry::get('security_users');
            $motherAddress = '';
    
                if (!is_null($entity->student_id)) {
                    $motherDetails = $guardianData->find()
                                ->select(['security_users.address'])
                                ->leftJoin(['Guardians' => 'student_guardians'], [
                                    'security_users.id = ' . 'Guardians.guardian_id',
                                    
                                ])
                                ->leftJoin(['GuardiansRelation' => 'guardian_relations'], [
                                    'Guardians.guardian_relation_id = ' . 'GuardiansRelation.id',
                                ])
                                ->where(['Guardians.student_id'=>$entity->student_id, 'GuardiansRelation.name'=>'Mother'])
                                ->first();
    
                        if(!empty($motherDetails)){
                            $motherAddress = $motherDetails->address;
                        }
    
                }

            return $motherAddress;
        }
    

	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
      
        $query
            ->select([   
                'student_id' =>'Users.id',             
                'student_first_name' => 'Users.first_name',
                'student_last_name' => 'Users.last_name',
                'openemis_no' => 'Users.openemis_no',
                'institution_name' => 'Institutions.name',
                'education_grade_name' => 'EducationGrades.name',
                'institution_class_name' => 'InstitutionClasses.name',
                'institution_code' => 'Institutions.code'
             ])
             

           ->leftJoin(['Users' => 'security_users'], [
                            'Users.id = ' . 'Guardians.student_id'
                        ])
            
            ->leftJoin(['Guardian' => 'security_users'], [
                            'Guardian.id = ' . 'Guardians.guardian_id',
                            
                        ])
           
            ->leftJoin(['InstitutionStudents' => 'institution_students'], [
                            'Users.id = ' . 'InstitutionStudents.student_id'
                        ])

            ->leftJoin(['EducationGrades' => 'education_grades'], [
                            'InstitutionStudents.education_grade_id = ' . 'EducationGrades.id'
                        ])

            ->leftJoin(['Institutions' => 'institutions'], [
                'InstitutionStudents.institution_id = ' . 'Institutions.id'
                        ])

           ->leftJoin(['InstitutionClassStudents' => 'institution_class_students'], [
                            'InstitutionClassStudents.student_id = ' . 'Users.id'
                        ])

            ->leftJoin(['InstitutionClasses' => 'institution_classes'], [
                            'InstitutionClasses.id = ' . 'InstitutionClassStudents.institution_class_id'
                        ])

            ->leftJoin(['StudentStatuses' => 'student_statuses'], [
                            'InstitutionStudents.student_status_id = ' . 'StudentStatuses.id'
                        ])
            ->leftJoin(['UserContacts' => 'user_contacts'], [
                            'Guardian.id = ' . 'UserContacts.security_user_id'
                        ])
            ->group('Guardians.student_id')
          
            ->where(['StudentStatuses.code' => 'CURRENT',
                            'InstitutionClassStudents.student_status_id = ' . 'StudentStatuses.id',
                            'Users.id = ' . 'Guardians.student_id'
                    ])
           ;
	}

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $cloneFields = $fields->getArrayCopy();

        $extraFields[] = [
            'key' => 'Institutions.code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];

        $extraFields[] = [
            'key' => 'Institutions.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];

        $extraFields[] = [
            'key' => 'EducationGrades.name',
            'field' => 'education_grade_name',
            'type' => 'string',
            'label' => __('Education Grade')
        ];

        $extraFields[] = [
            'key' => 'InstitutionClasses.name',
            'field' => 'institution_class_name',
            'type' => 'string',
            'label' => __('Class')
        ];

        $extraFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];

        
        $extraFields[] = [
            'key' => 'student_name_by_guardian',
            'field' => 'student_name_by_guardian',
            'type' => 'string',
            'label' => __('Student Name')
        ];

        
        $extraFields[] = [
            'key' => 'guardian_father_name',
            'field' => 'guardian_father_name',
            'type' => 'string',
            'label' => __('Father Name')
        ];

        $extraFields[] = [
            'key' => 'father_email',
            'field' => 'father_email',
            'type' => 'string',
            'label' => __('Father Email')
        ];

        $extraFields[] = [
            'key' => 'father_address',
            'field' => 'father_address',
            'type' => 'string',
            'label' => __('Father Address')
        ];

        $extraFields[] = [
            'key' => 'guardian_mother_name',
            'field' => 'guardian_mother_name',
            'type' => 'string',
            'label' => __('Mother Name')
        ];

        $extraFields[] = [
            'key' => 'mother_email',
            'field' => 'mother_email',
            'type' => 'string',
            'label' => __('Mother Email')
        ];

        $extraFields[] = [
            'key' => 'mother_address',
            'field' => 'mother_address',
            'type' => 'string',
            'label' => __('Mother Address')
        ];

        $newFields = $extraFields;
        
        $fields->exchangeArray($newFields);
    }

}
