<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class NotRegisteredStudentsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('institution_students');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('PreviousInstitutionStudents', ['className' => 'Institution.Students', 'foreignKey' => 'previous_institution_student_id']);

        $this->addBehavior('Excel', [
            'excludes' => ['id', 'total_mark'],
            'pages' => false,
            'orientation' => 'landscape'
        ]);
        $this->addBehavior('Report.ReportList');
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
        $selectedPeriod = $requestData->academic_period_id;
        $selectedExam = $requestData->examination_id;
        $selectedInstitution = $requestData->institution_id;

        $ExamCentreStudents = TableRegistry::get('Examination.ExaminationCentresExaminationsStudents');
        $Examinations = TableRegistry::get('Examination.Examinations');
        $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        $Class = TableRegistry::get('Institution.InstitutionClasses');

        $selectedGrade = $Examinations->get($selectedExam)->education_grade_id;
        $currentStatus = $this->StudentStatuses->getIdByCode('CURRENT');

        $query
            ->contain(['Users.Genders', 'Users.MainNationalities', 'Users.BirthplaceAreas', 'Users.AddressAreas', 'Users.SpecialNeeds.SpecialNeedsTypes', 'Institutions'])
            ->leftJoin([$ExamCentreStudents->alias() => $ExamCentreStudents->table()], [
                $ExamCentreStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
                $ExamCentreStudents->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
                $ExamCentreStudents->aliasField('examination_id = ') . $selectedExam
            ])
            ->leftJoin([$ClassStudents->alias() => $ClassStudents->table()], [
                $ClassStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
                $ClassStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                $ClassStudents->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
                $ClassStudents->aliasField('student_status_id = ') . $currentStatus
            ])
            ->leftJoin([$Class->alias() => $Class->table()], [
                $Class->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id'),
            ])
            ->select(['openemis_no' => 'Users.openemis_no', 'first_name' => 'Users.first_name', 'middle_name' => 'Users.middle_name','last_name' => 'Users.last_name', 'gender_name' => 'Genders.name', 'nationality_name' => 'MainNationalities.name', 'dob' => 'Users.date_of_birth', 'birthplace_area' => 'BirthplaceAreas.name', 'address_area' => 'AddressAreas.name', 'class_name' => 'InstitutionClasses.name'])
            ->where([
                $this->aliasField('academic_period_id') => $selectedPeriod,
                $this->aliasField('education_grade_id') => $selectedGrade,
                $this->aliasField('student_status_id') => $currentStatus,
                $ExamCentreStudents->aliasField('id') . ' IS NULL'
            ])
            ->order([$this->aliasField('institution_id'), $ClassStudents->aliasField('institution_class_id')]);

        if (!empty($selectedInstitution)) {
            $query->where([$this->aliasField('institution_id') => $selectedInstitution]);
        }
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'NotRegisteredStudents.academic_period_id',
            'field' => 'academic_period_id',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'NotRegisteredStudents.institution_id',
            'field' => 'institution_id',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'NotRegisteredStudents.education_grade_id',
            'field' => 'education_grade_id',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'InstitutionClasses.name',
            'field' => 'class_name',
            'type' => 'integer',
            'label' => __('Class'),
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
            'key' => 'Users.nationality_id',
            'field' => 'nationality_name',
            'type' => 'string',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'special_needs',
            'field' => 'special_needs',
            'type' => 'string',
            'label' => __('Special Needs'),
        ];

        $newFields[] = [
            'key' => 'Users.birthplace_area_id',
            'field' => 'birthplace_area',
            'type' => 'string',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Users.address_area_id',
            'field' => 'address_area',
            'type' => 'string',
            'label' => '',
        ];

        $fields->exchangeArray($newFields);
    }

    public function onExcelGetInstitutionId(Event $event, Entity $entity)
    {
        if ($entity->institution_id) {
            return $entity->institution->code_name;
        } else {
            return '';
        }
    }

    public function onExcelGetSpecialNeeds(Event $event, Entity $entity)
    {
        if ($entity->has('user') && $entity->user->has('special_needs') && !empty($entity->user->special_needs)) {
            $specialNeeds = $entity->user->special_needs;
            $allSpecialNeeds = [];

            foreach($specialNeeds as $key => $need) {
                $allSpecialNeeds[] = $need->special_needs_type->name;
            }

            return implode(', ', $allSpecialNeeds);
        } else {
            return '';
        }
    }
}