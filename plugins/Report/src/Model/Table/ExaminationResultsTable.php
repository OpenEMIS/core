<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class ExaminationResultsTable extends AppTable  {
    public function initialize(array $config)
    {
        $this->table('examination_centre_students');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('ExaminationGradingOptions', ['className' => 'Examination.ExaminationGradingOptions']);
        $this->belongsTo('ExaminationItems', ['className' => 'Examination.ExaminationItems']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);

        $this->addBehavior('Excel', [
            'excludes' => ['id', 'total_mark'],
            'pages' => false,
            'orientation' => 'landscape'
        ]);
        $this->addBehavior('Report.ReportList');
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
        $requestData = json_decode($settings['process']['params']);
        $selectedExam = $requestData->examination_id;
        $selectedInstitution = $requestData->institution_id;

        $query
            ->contain(['Users', 'Institutions'])
            ->select(['code' => 'Institutions.code', 'openemis_no' => 'Users.openemis_no'])
            ->where([$this->aliasField('examination_id') => $selectedExam])
            ->order([$this->aliasField('institution_id')]);

        if ($selectedInstitution != -1) {
            $query->where([$this->aliasField('institution_id') => $selectedInstitution]);
        }
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'ExaminationResults.academic_period_id',
            'field' => 'academic_period_id',
            'type' => 'integer',
            'label' => 'Academic Period',
        ];

        $newFields[] = [
            'key' => 'ExaminationResults.examination_id',
            'field' => 'examination_id',
            'type' => 'integer',
            'label' => 'Examination',
        ];

        $newFields[] = [
            'key' => 'Institutions.code',
            'field' => 'code',
            'type' => 'string',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'ExaminationResults.institution_id',
            'field' => 'institution_id',
            'type' => 'integer',
            'label' => 'Institution',
        ];

        $newFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'ExaminationResults.student_id',
            'field' => 'student_id',
            'type' => 'integer',
            'label' => '',
        ];

        $requestData = json_decode($settings['process']['params']);
        $selectedExam = $requestData->examination_id;
        $selectedAcademicPeriod = $requestData->academic_period_id;

        $examSubjects = $this->ExaminationItems
            ->find()
            ->matching('EducationSubjects')
            ->where([$this->ExaminationItems->aliasField('examination_id') => $selectedExam])
            ->toArray();

        foreach ($examSubjects as $subject) {
            $subjectId = $subject->education_subject_id;
            $weight = $subject->weight;
            $subjectName = $subject->_matchingData['EducationSubjects']->name;
            $label = __($subjectName);

            $gradingType = TableRegistry::get('Examination.ExaminationGradingTypes')->get($subject->examination_grading_type_id);
            if (!empty($gradingType)) {
                $resultType = $gradingType->result_type;

                if ($resultType == 'MARKS') {
                    $label = $label.' ('.$weight.') ';
                }
            }

            $newFields[] = [
                'key' => $label,
                'field' => 'examination_item',
                'type' => 'subject_mark',
                'label' => $label,
                'examinationId' => $selectedExam,
                'subjectId' => $subjectId,
                'academicPeriodId' => $selectedAcademicPeriod,
                'resultType' => $resultType,
                'weight' => $weight
            ];
        }

        $fields[] = [
            'key' => 'total_mark',
            'field' => 'examination_item',
            'type' => 'total_mark',
            'label' => __('Total Marks')
        ];


        $fields->exchangeArray($newFields);
    }

    public function onExcelRenderSubjectMark(Event $event, Entity $entity, array $attr) {
        $studentId = $entity->student_id;
        $examinationId = $attr['examinationId'];
        $subjectId = $attr['subjectId'];
        $academicPeriodId = $attr['academicPeriodId'];
        $resultType = $attr['resultType'];
        $weight = $attr['weight'];
        $ExaminationItemResultsTable = TableRegistry::get('Examination.ExaminationItemResults');
        $results = $ExaminationItemResultsTable->getExaminationItemResults($academicPeriodId, $examinationId, $subjectId, $studentId);

        if (isset($results)) {
            $marks = $assessmentItemResults[$institutionId][$studentId][$subjectId][$assessmentPeriodId];
            switch($resultType) {
                case 'MARKS':
                    // Add logic to add weighted mark to subjectWeightedMark
                    $this->assessmentPeriodWeightedMark += ($result['marks'] * $attr['assessmentPeriodWeight']);
                    $printedResult = ' '.$result['marks'];
                    break;
                case 'GRADES':
                    $printedResult = $result['grade_code'] . ' - ' . $result['grade_name'];
                    break;
            }
        }

        return $printedResult;
    }

    public function onExcelRenderTotalMark(Event $event, Entity $entity, array $attr) {
        pr($entity);
        // $totalMark = $this->totalMark;
        // $this->totalMark = 0;
        // return ' '.$totalMark;
    }
}