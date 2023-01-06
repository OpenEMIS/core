<?php

namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;

class StudentGuardiansTable extends AppTable {

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

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
        $requestData = json_decode($settings['process']['params']);
        $institutionId = $requestData->institution_id;

        $query
                ->select([
                    'student_first_name' => 'Users.first_name',
                    'student_last_name' => 'Users.last_name',
                    'date_of_birth' => 'Users.date_of_birth',
                    'education_grade_name' => 'EducationGrades.name',
                    'institution_class_name' => 'InstitutionClasses.name',
                    'guardian_first_name' => 'Guardians.first_name',
                    'guardian_last_name' => 'Guardians.last_name',
                    'address' => 'Guardians.address',
                    'email' => 'Guardians.email',
                    'gender_name' => 'Genders.name',
                    'contact_no' => 'UserContacts.value',
                    'institution_name' => 'Institutions.name',
                    'institution_code' => 'Institutions.code'
                ])
                ->leftJoin(['Users' => 'security_users'], [
                    'Users.id = ' . $this->aliasfield('student_id')
                ])
                ->leftJoin(['Guardians' => 'security_users'], [
                    'Guardians.id = ' . $this->aliasfield('guardian_id')
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
                    'Guardians.id = ' . 'UserContacts.security_user_id'
                ])
                ->leftJoin(['Institutions' => 'institutions'], [
                    'Institutions.id = ' . 'InstitutionStudents.institution_id'
                ])
                ->where(['StudentStatuses.code' => 'CURRENT',
                    'InstitutionClassStudents.student_status_id = ' . 'StudentStatuses.id']);

        if (!empty($institutionId)) {
            $query->where(['Institutions.id' => $institutionId]);
        }
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
            'key' => 'Guardians.first_name',
            'field' => 'guardian_first_name',
            'type' => 'string',
            'label' => __('Guardians First Name')
        ];

        $extraFields[] = [
            'key' => 'Guardians.last_name',
            'field' => 'guardian_last_name',
            'type' => 'string',
            'label' => __('Guardians Last Name')
        ];

        $extraFields[] = [
            'key' => 'Guardians.address',
            'field' => 'address',
            'type' => 'string',
            'label' => __('Guardians Address')
        ];

        $extraFields[] = [
            'key' => 'Guardians.email',
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
