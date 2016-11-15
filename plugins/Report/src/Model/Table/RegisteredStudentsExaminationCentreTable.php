<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class RegisteredStudentsExaminationCentreTable extends AppTable  {
    public function initialize(array $config)
    {
        $this->table('examination_centre_students');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);

        $this->addBehavior('Excel', [
            'excludes' => ['id', 'total_mark'],
            'pages' => false
            ]);
        $this->addBehavior('Report.ReportList');
    }

    public function onExcelBeforeGenerate(Event $event, $settings)
    {
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
        $requestData = json_decode($settings['process']['params']);
        // pr($requestData);
        $selectedExam = $requestData->examination_id;
        $selectedExamCentre = $requestData->examination_centre_id;

        // $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        // $enrolledStatus = $StudentStatuses->getIdByCode('CURRENT');
                    // ->leftJoin(['InstitutionClassStudents' => 'institution_class_student'],[
            //     'InstitutionClassStudents.student_id' => $this->aliasField('student_id'),
            //     'InstitutionClassStudents.institution_id' => $this->aliasField('institution_id'),
            //     'InstitutionClassStudents.education_grade_id' => $this->aliasField('education_grade_id'),
            //     'InstitutionClassStudents.student_status_id' => $enrolledStatus
            // ])

        $query
            ->contain(['Users.Genders', 'Users.BirthplaceAreas', 'Users.AddressAreas', 'Users.SpecialNeeds.SpecialNeedTypes', 'Institutions'])
            ->select(['code' => 'Institutions.code', 'openemis_no' => 'Users.openemis_no', 'first_name' => 'Users.first_name', 'middle_name' => 'Users.middle_name','last_name' => 'Users.last_name', 'gender_name' => 'Genders.name', 'dob' => 'Users.date_of_birth', 'birthplace_area' => 'BirthplaceAreas.name', 'address_area' => 'AddressAreas.name'])
            ->where([$this->aliasField('examination_id') => $selectedExam])
            ->group([$this->aliasField('student_id')])
            ->order([$this->aliasField('institution_id'), $this->aliasField('examination_centre_id')]);

        if ($selectedExamCentre != -1) {
            $query->where([$this->aliasField('examination_centre_id') => $selectedExamCentre]);
        }
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'Institutions.code',
            'field' => 'code',
            'type' => 'string',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'RegisteredStudentsExaminationCentre.institution_id',
            'field' => 'institution_id',
            'type' => 'integer',
            'label' => 'Institution',
        ];

        $newFields[] = [
            'key' => 'RegisteredStudentsExaminationCentre.education_grade_id',
            'field' => 'education_grade_id',
            'type' => 'integer',
            'label' => 'Education Grade',
        ];

        $newFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Users.first_name',
            'field' => 'first_name',
            'type' => 'string',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Users.middle_name',
            'field' => 'middle_name',
            'type' => 'string',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Users.last_name',
            'field' => 'last_name',
            'type' => 'string',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Users.gender_id',
            'field' => 'gender_name',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Users.date_of_birth',
            'field' => 'dob',
            'type' => 'date',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'special_needs',
            'field' => 'special_needs',
            'type' => 'string',
            'label' => 'Special Needs',
        ];

        $newFields[] = [
            'key' => 'Users.birthplace_area_id',
            'field' => 'birthplace_area',
            'type' => 'string',
            'label' => 'Area of Birth',
        ];

        $newFields[] = [
            'key' => 'Users.address_area_id',
            'field' => 'address_area',
            'type' => 'string',
            'label' => 'District',
        ];

        $newFields[] = [
            'key' => 'RegisteredStudentsExaminationCentre.academic_period_id',
            'field' => 'academic_period_id',
            'type' => 'integer',
            'label' => 'Academic Period',
        ];

        $newFields[] = [
            'key' => 'RegisteredStudentsExaminationCentre.examination_id',
            'field' => 'examination_id',
            'type' => 'integer',
            'label' => 'Examination',
        ];

        $newFields[] = [
            'key' => 'student_type',
            'field' => 'student_type',
            'type' => 'string',
            'label' => __('Student Type')
        ];

        $newFields[] = [
            'key' => 'RegisteredStudentsExaminationCentre.registration_number',
            'field' => 'registration_number',
            'type' => 'string',
            'label' => 'Registration Number',
        ];

        $newFields[] = [
            'key' => 'RegisteredStudentsExaminationCentre.examination_centre_id',
            'field' => 'examination_centre_id',
            'type' => 'integer',
            'label' => 'Examination Centre',
        ];

        $fields->exchangeArray($newFields);
    }

    public function onExcelGetStudentType(Event $event, Entity $entity) {
        $normal = 'Normal Candidate';
        $private = 'Private Candidate';
        pr($entity);
        if ($entity->has('institution') && !empty($entity->institution)) {
            return $normal;
        } else {
            return $private;
        }
    }

    public function onExcelGetSpecialNeeds(Event $event, Entity $entity) {
        if ($entity->has('user') && $entity->user->has('special_needs') && !empty($entity->user->special_needs)) {
            $specialNeeds = $entity->user->special_needs;
            $allSpecialNeeds = [];

            foreach($specialNeeds as $key => $need) {
                $allSpecialNeeds[] = $need->special_need_type->name;
            }

            return implode(', ', $allSpecialNeeds);
        } else {
            return '';
        }
    }
}