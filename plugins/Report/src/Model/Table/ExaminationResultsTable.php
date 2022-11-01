<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class ExaminationResultsTable extends AppTable
{
    private $examinationResults = [];
    private $itemWeightedMark = 0;
    private $totalMarks = 0;
    private $totalWeightedMark = 0;

    public function initialize(array $config)
    {
        $this->table('examination_centres_examinations_students');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('ExaminationCentresExaminations', [
            'className' => 'Examination.ExaminationCentresExaminations',
            'foreignKey' => ['examination_centre_id', 'examination_id']
        ]);
        $this->belongsToMany('ExaminationCentresExaminationsSubjects', [
            'className' => 'Examination.ExaminationCentresExaminationsSubjects',
            'joinTable' => 'examination_centres_examinations_subjects_students',
            'foreignKey' => ['examination_centre_id', 'examination_id', 'student_id'],
            'targetForeignKey' => ['examination_centre_id', 'examination_item_id'],
            'through' => 'Examination.ExaminationCentresExaminationsSubjectsStudents',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('ExaminationCentreRoomsExaminationsStudents', [
            'className' => 'Examination.ExaminationCentreRoomsExaminationsStudents',
            'foreignKey' => ['examination_centre_id', 'examination_id', 'student_id'],
            'bindingKey' => ['examination_centre_id', 'examination_id', 'student_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

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
        $selectedExam = $requestData->examination_id;
        $selectedInstitution = $requestData->institution_id;

        $query
            ->contain(['Users', 'Institutions'])
            ->select(['openemis_no' => 'Users.openemis_no'])
            ->where([$this->aliasField('examination_id') => $selectedExam])
            ->group($this->aliasField('student_id'))
            ->order($this->aliasField('institution_id'));

        if (!empty($selectedInstitution)) {
            $institutionId = ($selectedInstitution != '-1') ? $selectedInstitution : 0;
            $query->where([$this->aliasField('institution_id') => $institutionId]);
        }
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'ExaminationResults.academic_period_id',
            'field' => 'academic_period_id',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'ExaminationResults.examination_id',
            'field' => 'examination_id',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'ExaminationResults.examination_centre_id',
            'field' => 'examination_centre_id',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'ExaminationResults.institution_id',
            'field' => 'institution_id',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'ExaminationResults.registration_number',
            'field' => 'registration_number',
            'type' => 'string',
            'label' => '',
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

        $ExaminationItems = TableRegistry::get('Examination.ExaminationItems');
        $examItems = $ExaminationItems
            ->find()
            ->contain('ExaminationGradingTypes')
            ->where([
                $ExaminationItems->aliasField('examination_id') => $selectedExam,
                $ExaminationItems->aliasField('weight > ') => 0
            ])
            ->order([$ExaminationItems->aliasField('code')])
            ->toArray();

        foreach ($examItems as $item) {
            $examItemId = $item->id;
            $examItemCodeName = $item->code_name;
            $weight = $item->weight;

            $label = __($examItemCodeName);
            $weightLabel = __('Weighted Marks');
            $resultType = $item->examination_grading_type->result_type;
            if ($resultType == 'MARKS') {
                $label = $label.' ('.$weight.') ';
                $weightLabel = $weightLabel.' ('.$weight.')';
            }

            $newFields[] = [
                'key' => 'item_mark',
                'field' => 'item_mark',
                'type' => 'item_mark',
                'label' => $label,
                'examinationId' => $selectedExam,
                'examItemId' => $examItemId,
                'academicPeriodId' => $selectedAcademicPeriod,
                'resultType' => $resultType,
                'weight' => $weight
            ];

            $newFields[] = [
                'key' => 'item_weighted_mark',
                'field' => 'item_weighted_mark',
                'type' => 'integer',
                'label' => $weightLabel
            ];
        }

        $newFields[] = [
            'key' => 'total_mark',
            'field' => 'total_mark',
            'type' => 'integer',
            'label' => __('Total Marks')
        ];

        $newFields[] = [
            'key' => 'total_weighted_mark',
            'field' => 'total_weighted_mark',
            'type' => 'integer',
            'label' => __('Total Weighted Marks')
        ];

        $fields->exchangeArray($newFields);
    }

    public function onExcelGetExaminationId(Event $event, Entity $entity)
    {
        if ($entity->has('examination')) {
            return $entity->examination->code_name;
        } else {
            return '';
        }
    }

    public function onExcelGetExaminationCentreId(Event $event, Entity $entity)
    {
        if ($entity->has('examination_centre')) {
            return $entity->examination_centre->code_name;
        } else {
            return '';
        }
    }

    public function onExcelGetInstitutionId(Event $event, Entity $entity)
    {
        if ($entity->has('institution')) {
            return $entity->institution->code_name;
        } else {
            return __('Private Candidate');
        }
    }

    public function onExcelRenderItemMark(Event $event, Entity $entity, array $attr)
    {
        $studentId = $entity->student_id;
        $examinationId = $attr['examinationId'];
        $examItemId = $attr['examItemId'];
        $academicPeriodId = $attr['academicPeriodId'];
        $resultType = $attr['resultType'];
        $weight = $attr['weight'];

        $ExaminationItemResultsTable = TableRegistry::get('Examination.ExaminationItemResults');
        $results = $this->examinationResults;
        if (!(isset($results[$studentId]))) {
            $results = $ExaminationItemResultsTable->getExaminationItemResults($academicPeriodId, $examinationId, $studentId);
            $this->examinationResults = $results;
        }

        $printedResult = '';
        if (isset($results[$studentId][$examItemId])) {
            $marks = $results[$studentId][$examItemId];
            switch($resultType) {
                case 'MARKS':
                    $weightedMark = $marks['marks']*$weight;
                    $this->totalMarks += $marks['marks'];
                    $this->itemWeightedMark = $weightedMark;
                    $this->totalWeightedMark += $weightedMark;
                    $printedResult = $marks['marks'];
                    break;
                case 'GRADES':
                    $printedResult = $marks['grade_code'] . ' - ' . $marks['grade_name'];
                    break;
            }
        }

        return $printedResult;
    }

    public function onExcelGetItemWeightedMark(Event $event, Entity $entity)
    {
        $printedResult = $this->itemWeightedMark;
        $this->itemWeightedMark = 0;
        return ' '.$printedResult;
    }

    public function onExcelGetTotalWeightedMark(Event $event, Entity $entity)
    {
        $printedResult = $this->totalWeightedMark;
        $this->totalWeightedMark = 0;
        return $printedResult;
    }

    public function onExcelGetTotalMark(Event $event, Entity $entity)
    {
        $printedResult = $this->totalMarks;
        $this->totalMarks = 0;
        return $printedResult;
    }
}