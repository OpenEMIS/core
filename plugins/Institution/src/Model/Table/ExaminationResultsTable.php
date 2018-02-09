<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\ResultSet;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;

class ExaminationResultsTable extends ControllerActionTable
{
    private $fieldPrefix = 'examination_item_';
    private $examinationItems = null;

    public function initialize(array $config)
    {
        $this->table('examination_centres_examinations_students');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
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
            'cascadeCallBacks' => true
        ]);

        $this->addBehavior('Examination.RegisteredStudents');

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.onGetFieldLabel'] = 'onGetFieldLabel';
        return $events;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($this->startsWith($field, $this->fieldPrefix)) {
            $examinationItemId = str_replace($this->fieldPrefix, "", $field);

            if (!is_null($this->examinationItems) && array_key_exists($examinationItemId, $this->examinationItems)) {
                $examinationItemEntity = $this->examinationItems[$examinationItemId];
                $label = $examinationItemEntity->code;
                $label .= '&nbsp;&nbsp;<i class="fa fa-info-circle fa-lg fa-right icon-blue" tooltip-placement="top" uib-tooltip="'.$examinationItemEntity->name.'" tooltip-append-to-body="true" tooltip-class="tooltip-blue"></i>';

                return $label;
            }
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $extra['auto_contain'] = false;

        $query
            ->select([$this->aliasField('institution_id')])
            ->autoFields(true);
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $this->field('date_of_birth', ['visible' => false]);
        $this->field('gender_id', ['visible' => false]);
        $this->field('total_mark', ['visible' => false]);

        $this->setupExaminationItemFields($query, $data, $extra);

        $this->setFieldOrder(['registration_number', 'openemis_no', 'student_id']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // Start: not applicable to unregister from Institutions > Examinations > Results
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        if (array_key_exists('unregister', $toolbarButtonsArray)) {
            unset($toolbarButtonsArray['unregister']);
        }
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        // End

        $this->field('total_mark', ['visible' => false]);
        $this->field('results', [
            'type' => 'custom_results', 'valueClass' => 'table-full-width'
        ]);

        $this->setFieldOrder(['registration_number']);
    }

    public function onGetCustomResultsElement(Event $event, $action, $entity, $attr, $options=[])
    {
        if ($action == 'view') {
            $tableHeaders = [__('Examination Item'), __('Subject'), __('Mark'), __('Weight'), __('Total Mark')];
            $tableCells = [];

            $gradingTypes = $this->getGradingTypes();
            $academicPeriodId = $entity->academic_period_id;
            $examinationId = $entity->examination_id;
            $institutionId = $entity->institution->id;
            $studentId = $entity->user->id;
            $studentExaminationResults = $this->getStudentExaminationResults($academicPeriodId, $examinationId, $institutionId, $studentId);

            foreach ($studentExaminationResults as $key => $obj) {
                $examItemObj = $obj->_matchingData['ExaminationItems'];
                $subjectObj = $obj->_matchingData['EducationSubjects'];
                $gradingOptionObj = $obj->_matchingData['ExaminationGradingOptions'];
                $gradingTypeId = $gradingOptionObj->examination_grading_type_id;
                $itemWeight = $examItemObj->weight;

                $resultType = "MARKS";
                $passMark = 0;
                if (!empty($gradingTypes) && array_key_exists($gradingTypeId, $gradingTypes)) {
                    $resultType = $gradingTypes[$gradingTypeId]->result_type;
                    $passMark = $gradingTypes[$gradingTypeId]->pass_mark;
                }

                $itemResult = '';
                $totalMark = '<i class="fa fa-minus"></i>';
                switch ($resultType) {
                    case 'MARKS':
                        $itemResult = number_format($obj->marks, 2);
                        $totalMark = number_format($obj->marks * $itemWeight, 2);
                        if ($itemResult < $passMark) {
                            $itemResult = '<span style="color:#CC5C5C;">' . $itemResult . '</span>';
                        }
                        break;
                    case 'GRADES':
                        $itemResult = $gradingOptionObj->code_name;
                        break;
                    default:
                        break;
                }

                $rowData = [];
                $rowData[] = $examItemObj->code_name;
                $rowData[] = $subjectObj->code_name;
                $rowData[] = $itemResult;
                $rowData[] = $itemWeight;
                $rowData[] = $totalMark;

                $tableCells[] = $rowData;
            }

            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;
        }

        return $event->subject()->renderElement('Institution.ExaminationResults/results', ['attr' => $attr]);
    }

    private function setupExaminationItemFields(Query $query, ResultSet $data, ArrayObject $extra)
    {
        if ($extra->offsetExists('selectedAcademicPeriod') && $extra->offsetExists('selectedExamination')) {
            $selectedAcademicPeriod = $extra['selectedAcademicPeriod'];
            $selectedExamination = $extra['selectedExamination'];

            if ($selectedExamination != '-1') {
                // Start: add each examination item as new columns
                $this->examinationItems = $this->getExaminationItems($selectedExamination);
                foreach ($this->examinationItems as $examItemKey => $examItemObj) {
                    $fieldName = $this->getFieldNameByExamItem($examItemObj);
                    $this->field($fieldName, [
                        'type' => 'string'
                    ]);
                }
                // End

                $this->setStudentExaminationResults($data);
            }
        }
    }

    private function getGradingTypes()
    {
        $gradingTypes = [];

        $ExaminationGradingTypes = TableRegistry::get('Examination.ExaminationGradingTypes');
        $gradingTypeResults = $ExaminationGradingTypes
            ->find()
            ->toArray();

        foreach ($gradingTypeResults as $gradingTypeKey => $gradingTypeObj) {
            $gradingTypes[$gradingTypeObj->id] = $gradingTypeObj;
        }

        return $gradingTypes;
    }

    private function getExaminationItems($selectedExamination)
    {
        $items = [];

        $ExaminationItems = TableRegistry::get('Examination.ExaminationItems');
        $EducationSubjects = TableRegistry::get('Education.EducationSubjects');
        $examinationItemResults = $ExaminationItems
            ->find()
            ->leftJoinWith('EducationSubjects')
            ->where([
                $ExaminationItems->aliasField('examination_id') => $selectedExamination,
                $ExaminationItems->aliasField('weight > ') => 0
            ])
            ->order([
                $EducationSubjects->aliasField('order'),
                $ExaminationItems->aliasField('code'),
                $ExaminationItems->aliasField('name')
            ])
            ->toArray();

        foreach ($examinationItemResults as $key => $obj) {
            $items[$obj->id] = $obj;
        }

        return $items;
    }

    private function getFieldNameByExamItem($examItemObj)
    {
        $fieldName = $this->fieldPrefix.$examItemObj->id;
        return $fieldName;
    }

    private function getStudentExaminationResults($academicPeriodId, $examinationId, $institutionId, $studentId)
    {
        $ExaminationItemResults = TableRegistry::get('Examination.ExaminationItemResults');
        $studentExaminationResults = $ExaminationItemResults
            ->find()
            ->select([
                $ExaminationItemResults->aliasField('id'),
                $ExaminationItemResults->aliasField('marks'),
                $ExaminationItemResults->aliasField('examination_grading_option_id'),
                $ExaminationItemResults->aliasField('student_id'),
                $ExaminationItemResults->aliasField('examination_id'),
                $ExaminationItemResults->aliasField('examination_item_id'),
                $ExaminationItemResults->aliasField('education_subject_id'),
                $ExaminationItemResults->aliasField('institution_id'),
                $ExaminationItemResults->aliasField('academic_period_id'),
                $ExaminationItemResults->Examinations->aliasField('code'),
                $ExaminationItemResults->Examinations->aliasField('name'),
                $ExaminationItemResults->Examinations->aliasField('education_grade_id'),
                $ExaminationItemResults->ExaminationItems->aliasField('id'),
                $ExaminationItemResults->ExaminationItems->aliasField('code'),
                $ExaminationItemResults->ExaminationItems->aliasField('name'),
                $ExaminationItemResults->ExaminationItems->aliasField('weight'),
                $ExaminationItemResults->EducationSubjects->aliasField('code'),
                $ExaminationItemResults->EducationSubjects->aliasField('name'),
                $ExaminationItemResults->ExaminationGradingOptions->aliasField('code'),
                $ExaminationItemResults->ExaminationGradingOptions->aliasField('name'),
                $ExaminationItemResults->ExaminationGradingOptions->aliasField('examination_grading_type_id'),
            ])
            ->innerJoinWith('Examinations')
            ->innerJoinWith('ExaminationItems')
            ->leftJoinWith('EducationSubjects')
            ->innerJoinWith('ExaminationGradingOptions')
            ->where([
                $ExaminationItemResults->aliasField('academic_period_id') => $academicPeriodId,
                $ExaminationItemResults->aliasField('examination_id') => $examinationId,
                $ExaminationItemResults->aliasField('institution_id') => $institutionId,
                $ExaminationItemResults->aliasField('student_id') => $studentId,
                $ExaminationItemResults->ExaminationItems->aliasField('weight > ') => 0
            ])
            ->toArray();

        return $studentExaminationResults;
    }

    private function setStudentExaminationResults(ResultSet $data)
    {
        $gradingTypes = $this->getGradingTypes();
        $ExaminationItemResults = TableRegistry::get('Examination.ExaminationItemResults');

        foreach ($data as $examCentreStudentKey => $examCentreStudentObj) {
            $academicPeriodId = $examCentreStudentObj['academic_period_id'];
            $examinationId = $examCentreStudentObj['examination_id'];
            $institutionId = $examCentreStudentObj['institution_id'];
            $studentId = $examCentreStudentObj['student_id'];
            $studentExaminationResults = $this->getStudentExaminationResults($academicPeriodId, $examinationId, $institutionId, $studentId);

            foreach ($studentExaminationResults as $key => $itemResultObj) {
                $examItemObj = $itemResultObj->_matchingData['ExaminationItems'];
                $gradingOptionObj = $itemResultObj->_matchingData['ExaminationGradingOptions'];
                $gradingTypeId = $gradingOptionObj->examination_grading_type_id;
                $fieldName = $this->getFieldNameByExamItem($examItemObj);

                $resultType = "MARKS";
                $passMark = 0;
                if (!empty($gradingTypes) && array_key_exists($gradingTypeId, $gradingTypes)) {
                    $resultType = $gradingTypes[$gradingTypeId]->result_type;
                    $passMark = $gradingTypes[$gradingTypeId]->pass_mark;
                }

                $itemResult = '';
                switch ($resultType) {
                    case 'MARKS':
                        $itemResult = number_format($itemResultObj->marks, 2);
                        if ($itemResult < $passMark) {
                            $itemResult = '<span style="color:#CC5C5C;">' . $itemResult . '</span>';
                        }
                        break;
                    case 'GRADES':
                        $itemResult = $gradingOptionObj->code_name;
                        break;
                    default:
                        break;
                }

                $examCentreStudentObj->{$fieldName} = $itemResult;
            }
        }
    }
}
