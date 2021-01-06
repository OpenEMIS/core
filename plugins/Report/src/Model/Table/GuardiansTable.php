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
        $institutionsTable = TableRegistry::get('institutions');

        $conditions = [];
        if (!empty($institutionId)) {
            $conditions['Institutions.id'] = $institutionId;
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
            'area_name' => 'Areas.name'
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
        ->group('Users.first_name')
        ->where(['StudentStatuses.code' => 'CURRENT',
            'InstitutionClassStudents.student_status_id = ' . 'StudentStatuses.id',
            'Areas.area_level_id !=' . 1,
             $conditions
        ]);
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

        $extraFields[] = [
            'key' => 'Areas.name',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __('Area Name')
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
            'key' => 'UserContacts.value',
            'field' => 'contact_no',
            'type' => 'string',
            'label' => __('Guardians Primary Phone Contact')
        ];
        $newFields = $extraFields;

        $fields->exchangeArray($newFields);
    }

}
