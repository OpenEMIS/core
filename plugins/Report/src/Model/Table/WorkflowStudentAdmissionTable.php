<?php

namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;

class WorkflowStudentAdmissionTable extends AppTable
{

    public function initialize(array $config): void
    {
        $this->setTable("institution_student_admission");
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);

        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.WorkflowReport');
        $this->addBehavior('Excel', [
            'pages' => false,
            'autoFields' => false
        ]);
    }

    //POCOR-7619
    public function onExcelGetOpenemisNo(EventInterface $event, Entity $entity)
    {
        $openemisNo = '';
        if (!empty($entity['user'])) {
            $openemisNo = $entity['user']['openemis_no'];
        }

        return $openemisNo;
    }

    //POCOR-9367 start
    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, $query)
    {
        $InstitutionStudentProgrammes = TableRegistry::getTableLocator()->get('Student.InstitutionStudentProgrammes');

        $query->contain(['EducationGrades.EducationProgrammes']);

        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) use ($InstitutionStudentProgrammes) {
            return $results->map(function ($row) use ($InstitutionStudentProgrammes) {
                $conditions = [];

                $educationProgrammeId = null;
                if ($row->has('education_grade') && ($row->education_grade->education_programme_id ?? null)) {
                    $educationProgrammeId = (int)$row->education_grade->education_programme_id;
                }
                if ($educationProgrammeId) {
                    $conditions['student_id'] = $row->student_id;
                    $conditions['institution_id'] = $row->institution_id ?? null;
                    $conditions['education_programme_id'] = $educationProgrammeId;
                }
                $InstitutionStudentProgrammesResult = $InstitutionStudentProgrammes->find()
                    ->select(['registration_number', 'id'])
                    ->where($conditions)
                    ->orderDesc('id')
                    ->first();
                $row['registration_number'] = $InstitutionStudentProgrammesResult->registration_number ?? '';
                return $row;
            });
        });
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
    {
        $fields[] = [
            'key' => 'registration_number',
            'field' => 'registration_number',
            'type' => 'string',
            'label' => __('Registration Number')
        ];
        return $fields;
    }
    //POCOR-9367 end
}
