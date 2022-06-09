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

class GuardiansTable extends AppTable {

    public function initialize(array $config) {

        $this->table('student_guardians');
        parent::initialize($config);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Excel', [
            'pages' => false
        ]);
    }

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets) {

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
            ->select(['security_users.first_name', 'security_users.last_name'])
            ->leftJoin(['Guardians' => 'student_guardians'], [
                'security_users.id = ' . 'Guardians.student_id'
            ])
            ->where(['Guardians.student_id' => $entity->student_id])
            ->first();

            if (!empty($getStudent)) {
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
            ->select(['security_users.first_name', 'security_users.last_name'])
            ->leftJoin(['Guardians' => 'student_guardians'], [
                'security_users.id = ' . 'Guardians.guardian_id',
            ])
            ->leftJoin(['GuardiansRelation' => 'guardian_relations'], [
                'Guardians.guardian_relation_id = ' . 'GuardiansRelation.id',
            ])
            ->where(['Guardians.student_id' => $entity->student_id, 'GuardiansRelation.name' => 'Father'])
            ->first();

            if (!empty($getGuardian)) {
                $name = $getGuardian->first_name . ' ' . $getGuardian->last_name;
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
            ->where(['Guardians.student_id' => $entity->student_id, 'GuardiansRelation.name' => 'Father'])
            ->first();

            if (!empty($getGuardian)) {
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
            ->where(['Guardians.student_id' => $entity->student_id, 'GuardiansRelation.name' => 'Father'])
            ->first();

            if (!empty($getGuardian)) {
                $fatherAddress = $getGuardian->address;
            }
        }

        return $fatherAddress;
    }

    public function onExcelGetGuardianMotherName(Event $event, Entity $entity) {

        $guardianData = TableRegistry::get('security_users');
        $motherName = '';

        if (!is_null($entity->student_id)) {

            $motherDetails = $guardianData->find()
            ->select(['security_users.first_name', 'security_users.last_name'])
            ->leftJoin(['Guardians' => 'student_guardians'], [
                'security_users.id = ' . 'Guardians.guardian_id',
            ])
            ->leftJoin(['GuardiansRelation' => 'guardian_relations'], [
                'Guardians.guardian_relation_id = ' . 'GuardiansRelation.id',
            ])
            ->where(['Guardians.student_id' => $entity->student_id, 'GuardiansRelation.name' => 'Mother'])
            ->first();

            if (!empty($motherDetails)) {
                $motherName = $motherDetails->first_name . ' ' . $motherDetails->last_name;
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
            ->where(['Guardians.student_id' => $entity->student_id, 'GuardiansRelation.name' => 'Mother'])
            ->first();

            if (!empty($motherDetails)) {
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
            ->where(['Guardians.student_id' => $entity->student_id, 'GuardiansRelation.name' => 'Mother'])
            ->first();

            if (!empty($motherDetails)) {
                $motherAddress = $motherDetails->address;
            }
        }

        return $motherAddress;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {

        $requestData = json_decode($settings['process']['params']);
        $institutionId = $requestData->institution_id;
        $institutionTypeId = $requestData->institution_type_id;
        $institutionsTable = TableRegistry::get('institutions');
        $areaId = $requestData->area_education_id;
        $conditions = [];
        if (!empty($institutionId) && $institutionId > 0) {
            $conditions['Institutions.id'] = $institutionId;
        }
        if (!empty($institutionTypeId) && $institutionTypeId > 0) {
            $conditions['Institutions.institution_type_id'] = $institutionTypeId;
        }
        if (!empty($areaId) && $areaId != -1) {
            $conditions['Institutions.area_id'] = $areaId;
        }

        $query
        ->select([
            'student_id' => 'Users.id',
            'student_first_name' => 'Users.first_name',
            'student_last_name' => 'Users.last_name',
            'openemis_no' => 'Users.openemis_no',
            'date_of_birth' => 'Users.date_of_birth',
            'institution_name' => 'Institutions.name',
            'education_grade_name' => 'EducationGrades.name',
            'institution_class_name' => 'InstitutionClasses.name',
            'institution_code' => 'Institutions.code',
            'gender_name' => 'Genders.name',
            'guardian_relation_name' => 'GuardianRelations.name',
            'guardian_first_name' => 'Guardian.first_name',
            'guardian_last_name' => 'Guardian.last_name',
            'address' => 'Guardian.address',
            'email' => 'Guardian.email',
            'area_code' => 'Areas.code',
            'area_name' => 'Areas.name',
            'contact_no' => 'UserContacts.value',
            'atoll' => 'AreaAdministrativeLevels.name',//POCOR-6728
            'education_code' => 'AreaAdministratives.code'//POCOR-6728
        ])
        ->leftJoin(['Users' => 'security_users'], [
            'Users.id = ' . 'Guardians.student_id'
        ])
        ->leftJoin(['Guardian' => 'security_users'], [
            'Guardian.id = ' . 'Guardians.guardian_id',
        ])
        ->leftJoin(['GuardianRelations' => 'guardian_relations'], [
            'GuardianRelations.id = ' . 'Guardians.guardian_relation_id',
        ])
        ->leftJoin(['Genders' => 'genders'], [
                    'Users.gender_id = ' . 'Genders.id'
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
        ->leftJoin(['Areas' => 'areas'], [
            'Institutions.area_id = ' . 'Areas.id'
        ])
        ->leftJoin(['AreaAdministratives' => 'area_administratives'], [
            'Institutions.area_administrative_id = ' . 'AreaAdministratives.id'
        ])
        ->leftJoin(['AreaAdministrativeLevels' => 'area_administrative_levels'], [
            'AreaAdministratives.area_administrative_level_id = ' . 'AreaAdministrativeLevels.id'
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
            'Guardians.guardian_id = ' . 'UserContacts.security_user_id'
        ])
        ->group('Users.first_name')
        ->where(['StudentStatuses.code' => 'CURRENT',
            'InstitutionClassStudents.student_status_id = ' . 'StudentStatuses.id',
            'Areas.area_level_id !=' . 1,
             $conditions
        ]);/**POCOR-6728 starts*/
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                
                $areas1 = TableRegistry::get('areas');
                $areasData = $areas1
                            ->find()
                            ->where([$areas1->alias('code')=>$row->area_code])
                            ->first();
                $row['region_code'] = '';            
                $row['region_name'] = '';
                if(!empty($areasData)){
                    $areas = TableRegistry::get('areas');
                    $areaLevels = TableRegistry::get('area_levels');
                    $institutions = TableRegistry::get('institutions');
                    $val = $areas
                                ->find()
                                ->select([
                                    $areas1->aliasField('code'),
                                    $areas1->aliasField('name'),
                                    ])
                                ->leftJoin(
                                    [$areaLevels->alias() => $areaLevels->table()],
                                    [
                                        $areas->aliasField('area_level_id  = ') . $areaLevels->aliasField('id')
                                    ]
                                )
                                ->leftJoin(
                                    [$institutions->alias() => $institutions->table()],
                                    [
                                        $areas->aliasField('id  = ') . $institutions->aliasField('area_id')
                                    ]
                                )    
                                ->where([
                                    $areaLevels->aliasField('level !=') => 1,
                                    $areas->aliasField('id') => $areasData->parent_id
                                ])->first();
                    
                    if (!empty($val->name) && !empty($val->code)) {
                        $row['region_code'] = $val->code;
                        $row['region_name'] = $val->name;
                    }
                }            
                
                return $row;
            });
        });
        /**POCOR-6728 end*/
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields) {
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
            'key' => 'Areas.code',
            'field' => 'area_code',
            'type' => 'string',
            'label' => __('Area Code')
        ];
        /**POCOR-6728 starts - uncommented area column*/
        $AreaLevelTbl = TableRegistry::get('area_levels');
        $AreaLevelArr = $AreaLevelTbl->find()->select(['id','name'])->order(['id'=>'DESC'])->limit(2)->hydrate(false)->toArray();
        
        $extraFields[] = [
            'key' => '',
            'field' => 'region_name',
            'type' => 'string',
            'label' => __($AreaLevelArr[1]['name'])
        ];

        $extraFields[] = [
            'key' => '',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __($AreaLevelArr[0]['name'])
        ];
        /**POCOR-6728 ends*/

        $extraFields[] = [ // POCOR-6728
            'key' => 'education_code',
            'field' => 'education_code',
            'type' => 'string',
            'label' => __('Education Code')
        ];

        $extraFields[] = [
            'key' => 'Users.first_name',
            'field' => 'student_first_name',
            'type' => 'string',
            'label' => __('Student First Name')
        ];

        $extraFields[] = [
            'key' => 'Users.last_name',
            'field' => 'student_last_name',
            'type' => 'string',
            'label' => __('Student Last Name')
        ];

        $extraFields[] = [
            'key' => 'Users.date_of_birth',
            'field' => 'date_of_birth',
            'type' => 'string',
            'label' => __('Date Of Birth')
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
            'key' => 'Genders.name',
            'field' => 'gender_name',
            'type' => 'string',
            'label' => __('Gender')
        ];

        $extraFields[] = [
            'key' => 'Guardian.first_name',
            'field' => 'guardian_first_name',
            'type' => 'string',
            'label' => __('Guardians First Name')
        ];

        $extraFields[] = [
            'key' => 'Guardian.last_name',
            'field' => 'guardian_last_name',
            'type' => 'string',
            'label' => __('Guardians Last Name')
        ];

        $extraFields[] = [
            'key' => 'GuardianRelations.name',
            'field' => 'guardian_relation_name',
            'type' => 'string',
            'label' => __('Guardian Relationship')
        ];

        $extraFields[] = [
            'key' => 'Guardian.address',
            'field' => 'address',
            'type' => 'string',
            'label' => __('Guardians Address')
        ];

        $extraFields[] = [
            'key' => 'Guardian.email',
            'field' => 'email',
            'type' => 'string',
            'label' => __('Guardians Email')
        ];

        $extraFields[] = [
            'key' => 'contact_no',
            'field' => 'contact_no',
            'type' => 'integer',
            'label' => __('Guardians Primary Phone Contact')
        ];
        $newFields = $extraFields;

        $fields->exchangeArray($newFields);
    }

}
