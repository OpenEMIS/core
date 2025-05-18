<?php

namespace Student\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;
use Archive\Model\Table\DataManagementConnectionsTable as ArchiveConnections;
use Cake\Log\Log;

use App\Model\Table\ControllerActionTable;

class CurrentAssessmentsTable extends ControllerActionTable
{

    private $institutionId = null;
    private $studentId = null;

    public function initialize(array $config): void
    {
        $this->setTable('assessment_item_results');
        parent::initialize($config);

        $this->belongsTo('Assessments', ['className' => 'Assessment.Assessments']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('AssessmentGradingOptions', ['className' => 'Assessment.AssessmentGradingOptions']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('AssessmentPeriods', ['className' => 'Assessment.AssessmentPeriods']);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('view', false);
        $this->toggle('remove', false);
        $this->toggle('search', false);

        $this->addBehavior('Restful.RestfulAccessControl');

        $this->addBehavior('Institution.InstitutionTab');
        //$this->addBehavior('Student.StudentTab');
        $this->addBehavior('Excel',[
            'excludes' => ['id','education_subject_id','academic_period_id','assessment_id','assessment_period_id','institution_classes_id','institution_id','assessment_grading_option_id','student_id','education_grade_id','marks'],
            'pages' => ['index'],
        ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        //$contentHeader = $this->controller->viewVars['contentHeader'];
        $contentHeader = $this->controller->viewBuilder()->getVars()['contentHeader'];
        list($studentName, $module) = explode(' - ', $contentHeader);
        $module = __('Assessments');
        $contentHeader = $studentName . ' - ' . $module;
        $this->controller->set('contentHeader', $contentHeader);
        $this->controller->Navigation->substituteCrumb(__('Student Assessment'), $module);
       // $session = $this->controller->request->getSession();
        $institutionId = $this->getInstitutionID();
       /* if ($session->check('Institution.Institutions.id')) {
            $institutionId = $session->read('Institution.Institutions.id');
        }*/
        $studentId = $this->getStudentID();
        $this->institutionId = $institutionId;
        $this->studentId = $studentId;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        //set fields
        $this->fields['student_status_id']['visible'] = false;
        $this->fields['academic_period_id']['visible'] = false;
        $this->fields['institution_id']['visible'] = false;
        $this->fields['assessment_grading_option_id']['visible'] = false;

        $this->field('total_mark', ['after' => 'marks']);
        $this->field('assessment_period_name', ['label' => 'Assessment Period']);
        $this->field('education_subject_name', ['label' => 'Education Subject']);
        $this->field('assessments_name', [
            'attr' => ['label' => __('Assessment Name')]
        ]);

        $this->setFieldOrder(['assessment_period_name', 'assessments_name', 'education_subject_name', 'marks', 'total_mark']);

        //add controls with php
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $extra['elements']['controls'] = [
            'name' => 'Student.Assessments/controls',
            'data' => ['encodedQueryString' => $encodedQueryString],
            'options' => [],
            'order' => 1];

        $this->addExtraButtons($extra);
    }

    /**
     * @param Event $event
     * @param Query $query
     * @param ArrayObject $extra
     * @author for the POCOR-7536 change Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        //POCOR-7201[START]
        $session = $this->Session;
        $institutionId = $this->getInstitutionID();
        $studentId = $this->studentId;
        //POCOR-7201[END]
        $query->contain('Assessments');
        $query->contain('AssessmentPeriods');
        $query->contain('EducationSubjects');
        $AssessmentPeriods = TableRegistry::get('Assessment.AssessmentPeriods');


        $query->find('all', [
            'fields' => [
                'id' => $this->aliasField('id'),
                'education_subject_name' => 'EducationSubjects.name',
                'education_subject_id' => 'EducationSubjects.id',
                'assessment_period_id' => 'AssessmentPeriods.id',
                'assessment_period_name' => 'AssessmentPeriods.name',
                'assessment_period_term' => 'AssessmentPeriods.academic_term',
                'assessment_id' => 'Assessments.id',
                'assessment_name' => 'Assessments.name',
                'assessment_code' => 'Assessments.code',
                'marks' => $this->aliasField('marks'),
                'academic_period_id' => $this->aliasField('academic_period_id'),
                'education_grade_id' => $this->aliasField('education_grade_id'),
                'student_id' => $this->aliasField('student_id'),
                'institution_class_id' => $this->aliasField('institution_classes_id'),
                'institution_id' => $this->aliasField('institution_id'),
            ]
        ])
            ->group([ //POCOR-7536 change
                $this->aliasField('student_id'),
                $this->aliasField('education_subject_id'),
                $this->aliasField('assessment_id'),
                $this->aliasField('assessment_period_id'),
                $AssessmentPeriods->aliasField('academic_term'),
//                $this->aliasField('assessment_id')
            ]);
//        $this->log($query->sql, 'debug');
        // Academic Periods filter
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $selectedAcademicPeriod =
            !is_null($this->request->getQuery('academic_period_id'))
                ? $this->request->getQuery('academic_period_id')
                : $this->AcademicPeriods->getCurrent();
        // POCOR-8224 start
        $selectedAssessment =
            !is_null($this->request->getQuery('assessment_id'))
                ? $this->request->getQuery('assessment_id')
                : -1;
        $selectedAssessmentPeriod =
            !is_null($this->request->getQuery('assessment_period_id'))
                ? $this->request->getQuery('assessment_period_id')
                : -1;
        // POCOR-8224 end
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
//        $where[$this->aliasField('institution_id')] = $institutionId;  // POCOR-7201
        // removed for the quickfix POCOR-7536-KHINDOL
        //End
        //Assessment Period filter
        $selectedAcademicPeriod = $this->setAcademicPeriodOptions($institutionId, $studentId, $selectedAcademicPeriod);

        if (!empty($selectedAcademicPeriod)) {
            $selectedAssessment = $this->setAssessmentOptions($selectedAcademicPeriod, $selectedAssessment);
            $where[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;

        }
        if (!empty($selectedAssessment)) {
            $selectedAssessmentPeriod = $this->setAssessmentPeriodOptions($selectedAssessment, $selectedAssessmentPeriod);
            if ($selectedAssessment != null) {
                if ($selectedAssessment != -1) {
                    $where[$this->aliasField('assessment_id')] = $selectedAssessment;
                }
            }

        }
        if (!empty($selectedAssessmentPeriod)) {
            if ($selectedAssessmentPeriod != null) {
                if ($selectedAssessmentPeriod != -1) {
                    $where[$this->aliasField('assessment_period_id')] = $selectedAssessmentPeriod;
                }
            }
        }
        $where[$this->aliasField('institution_id')] = $institutionId;
        $where[$this->aliasField('student_id')] = $studentId;
        $query->find('all')->where([$where]);
//        $this->log($query->sql(), 'debug');


    }

    public function onGetAssessmentsName(Event $event, Entity $entity)
    {
        return $entity->assessment_name . ' - ' . $entity->assessment_code . ' - ' . $entity->assessment_name;
    }

    public function onGetAssessmentPeriodName(Event $event, Entity $entity)
    {
        return $entity->assessment_period_term . ' - ' . $entity->assessment_period_name;
    }

    /**
     * get last marks from a common query
     * @param Event $event
     * @param Entity $entity
     * @return float
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function onGetMarks(Event $event, Entity $entity)
    {
        $ItemResults = TableRegistry::get('Assessment.AssessmentItemResults');

        $options = ["student_id" => $entity->student_id,
            "academic_period_id" => $entity->academic_period_id,
            "education_grade_id" => $entity->education_grade_id,
            "education_subject_id" => $entity->education_subject_id,
            "assessment_period_id" => $entity->assessment_period_id,
            'assessment_id' => $entity->assessment_id];
        $exemptions = $ItemResults::getLastExemptions($options);
        // POCOR-8224 start
        if(!empty($exemptions)){
            $mark = 'EXEMPT';
        }else{
            $marks = $ItemResults::getLastMark($options);
            $mark = round($marks[0]['marks'], 2);
        }
        return $mark;
        // POCOR-8224 start
    }

    /**
     * get total marks from a common query
     * @param Event $event
     * @param Entity $entity
     * @return float
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function onGetTotalMark(Event $event, Entity $entity)
    {
        $ItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
        $options = ["student_id" => $entity->student_id,
//            "institution_id" => $entity->institution_id,
//            "institution_class_id" => $entity->institution_class_id,
            "academic_period_id" => $entity->academic_period_id,
            "education_grade_id" => $entity->education_grade_id,
            "education_subject_id" => $entity->education_subject_id,
            "assessment_period_id" => -1,
            'assessment_id' => $entity->assessment_id];
        $marks = $ItemResults::getLastMark($options);

        //POCOR-8224 start
        // Fetch exemptions from getLastExemptions
        $exemptions = $ItemResults::getLastExemptions($options);

        // Extract relevant identifiers from exemptions: education_subject_id and assessment_period_id
        $exempt_combination = array_map(function($exemption) {
            return $exemption['education_subject_id'] . '-' . $exemption['assessment_period_id'];
        }, $exemptions);

        // Filter results to exclude the ones that match both education_subject_id and assessment_period_id in exemptions
        $filtered_results = [];
        foreach ($marks as $mark) {
            // Create a key for the current mark for comparison
            $mark_combination = $mark['education_subject_id'] . '-' . $mark['assessment_period_id'];

            // If the combination is not in the exemptions, include the mark
            if (!in_array($mark_combination, $exempt_combination)) {
                $filtered_results[] = $mark['marks'];
            }
//            else{
//                $filtered_results[] = 0;
//            }
        }

        // Sum the filtered results
        $sum_results = array_sum($filtered_results);
        // Return the rounded sum of the results
        //POCOR-8224 end
        return round($sum_results, 2);
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        if (isset($buttons['view'])) {
            $institutionId = $entity->institution_class->institution_id;
            $url = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'Subjects',
                'view',
                $this->paramsEncode(['id' => $entity->institution_subject->id]),
                'institution_id' => $institutionId,
            ];

            if ($this->controller->getName() == 'Directories') {
                $url = [
                    'plugin' => 'Directory',
                    'controller' => 'Directories',
                    'action' => 'StudentSubjects',
                    'index',
                    'type' => 'student',
                    'institution_subject_id' => $entity->institution_subject->id,
                    'institution_id' => $institutionId,
                ];
            }

            $buttons['view']['url'] = $url;
        }
        return $buttons;
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        //POCOR-7474-HINDOL
        $this->setupTabElements();
    }

    private function setupTabElements()
    {
        //POCOR-7474-HINDOL
        $options['type'] = 'student';
        //$tabElements = $this->controller->getAcademicTabElements($options);
        $tabElements = $this->getAcademicTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Assessments');
    }

    private function generateButton(ArrayObject $toolbarButtons, $name, $title, $label, $url, $btnAttr = null)
    {
        if (!$btnAttr) {
            $btnAttr = $this->getButtonAttr();
        }
        $customButton = [];
        if (isset($url['_ext'])) {
            unset($customButton['url']['_ext']);
        }
        if (isset($url['pass'])) {
            unset($customButton['url']['pass']);
        }
        if (isset($url['paging'])) {
            unset($customButton['url']['paging']);
        }
        if (isset($url['filter'])) {
            unset($customButton['url']['filter']);
        }
        $customButton['type'] = 'button';
        $customButton['attr'] = $btnAttr;
        $customButton['attr']['title'] = $title;
        $customButton['label'] = $label;
        $customButton['url'] = $url;
        $name = 'archive';
        $toolbarButtons[$name] = $customButton;
    }

    /**
     * @param ArrayObject $extra
     */
    private function addExtraButtons(ArrayObject $extra)
    {
        $toolbarButtons = $extra['toolbarButtons'];

        // Start POCOR-5188
        $this->addManualButton($toolbarButtons);
        // End POCOR-5188
        $this->addArchiveButton($toolbarButtons);
    }

    /**
     * @param $toolbarButtons
     */
    private function addManualButton($toolbarButtons)
    {
        $is_manual_exist = $this->getManualUrl('Institutions', 'Assessments', 'Students - Academic');
        if (!empty($is_manual_exist)) {
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target' => '_blank'
            ];

            $customButtonName = 'help';
            $customButtonUrl = $is_manual_exist['url'];
            $customButtonLabel = '<i class="fa fa-question-circle"></i>';
            $customButtonTitle = __('Help');
            $this->generateButton($toolbarButtons, $customButtonName, $customButtonTitle, $customButtonLabel, $customButtonUrl, $btnAttr);
        }


    }

    /**
     * @param $toolbarButtons
     */
    private function addArchiveButton($toolbarButtons)
    {
        $is_archive_exists = $this->isArchiveExists();
//        if ($is_archive_exists) {
            $customButtonName = 'archive';
            $customButtonUrl = [
                'plugin' => 'Student',
                'controller' => 'Students',
                'action' => 'AssessmentsArchived'
            ];
            $customButtonLabel = '<i class="fa fa-folder"></i>';
            $customButtonTitle = __('Archive');
            $this->generateButton($toolbarButtons, $customButtonName, $customButtonTitle, $customButtonLabel, $customButtonUrl);
//        }
    }

    private function isArchiveExists()
    {
        $is_archive_exists = true;
        $institutionId = $this->institutionId;
        $studentId = $this->studentId;
        //POCOR-7526::Start

        $where = [
            ['institution_id = ' .  $institutionId,
                'student_id =' . $studentId],
        ];
        $table_name = 'assessment_item_results';
        $is_archive_exists = ArchiveConnections::getArchiveAssessments($table_name, $where);
        return $is_archive_exists;
        //POCOR-7526::End
        return $is_archive_exists;
    }

    /**
     * @param $institutionId
     * @param $studentId
     * @param $selectedAcademicPeriod
     * @return int|string|null
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private function setAcademicPeriodOptions($institutionId, $studentId, $selectedAcademicPeriod)
    {
    // Academic Periods filter
        $ItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
        $years_arr = $ItemResults->find()
            ->select('academic_period_id')
            ->distinct('academic_period_id')
            ->where(['student_id' => $this->studentId])
            ->toArray();
        $years_ids = array_column($years_arr, 'academic_period_id');
        if(sizeof($years_ids) == 0){
            $years_ids = [0];
        }
        $academicPeriodOptions = $this->AcademicPeriods->getYearList([
            'isEditable' => true,
            'conditions' => [
                $this->AcademicPeriods->aliasField('id IN') => $years_ids]
        ]
        );

        $selectedAcademicPeriod = $this->advancedSelectOptions($academicPeriodOptions, $selectedAcademicPeriod);
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        return $selectedAcademicPeriod;
    }

    /**
     * @param $selectedAcademicPeriod
     * @param $selectedAssessment
     * @return int|mixed|string|null
     */
    private function setAssessmentOptions($selectedAcademicPeriod, $selectedAssessment = -1)
    {
        $ItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
        $assessments_arr = $ItemResults->find()
            ->select('assessment_id')
            ->distinct('assessment_id')
            ->where(['student_id' => $this->studentId])
            ->toArray();
        $assessments_ids = array_column($assessments_arr, 'assessment_id');
        if(sizeof($assessments_ids) == 0){
            $assessments_ids = [0];
        }
        $Assessments = TableRegistry::get('Assessment.Assessments');
        $assessmentOptions = $Assessments
            ->find('list')
            ->where([$Assessments->aliasField('id IN') => $assessments_ids,
                $Assessments->aliasField('academic_period_id') => $selectedAcademicPeriod])
            ->toArray();
        $assessmentOptions = ['-1' => __('All Assessments')] + $assessmentOptions;
        $selectedAssessment = $this->advancedSelectOptions($assessmentOptions, $selectedAssessment);
        $this->controller->set(compact('assessmentOptions', 'selectedAssessment'));
        //Assessment[End]
        return $selectedAssessment;
    }

    /**
     * POCOR-8224 changes
     * @param int $selectedAssessment
     * @param int $selectedAssessmentPeriod
     * @return int|string|null
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private function setAssessmentPeriodOptions($selectedAssessment = -1, $selectedAssessmentPeriod = -1)
    {
        $ItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
        $assessment_periods_arr = $ItemResults->find()
            ->select('assessment_period_id')
            ->distinct('assessment_period_id')
            ->where(['student_id' => $this->studentId])
            ->toArray();
        $assessment_periods_ids = array_column($assessment_periods_arr, 'assessment_period_id');
        if(sizeof($assessment_periods_ids) == 0){
            $assessment_periods_ids = [0];
        }
        $AssessmentPeriods = TableRegistry::get('Assessment.AssessmentPeriods');
        $where = [$AssessmentPeriods->aliasField('id IN') => $assessment_periods_ids];
        if ($selectedAssessment > 0) {
            $where[$AssessmentPeriods->aliasField('assessment_id')] = $selectedAssessment;
        }
        $AssessmentPeriodsOptions = $AssessmentPeriods
            ->find('list')
            ->where($where)
            ->toArray();
        $AssessmentPeriodsOptions = ['-1' => __('All Assessment Periods')] + $AssessmentPeriodsOptions;
        $selectedAssessmentPeriod = $this->advancedSelectOptions($AssessmentPeriodsOptions, $selectedAssessmentPeriod);
        $this->controller->set(compact('AssessmentPeriodsOptions', 'selectedAssessmentPeriod'));
        return $selectedAssessmentPeriod;
    }

    //POCOR-8137 Start
    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, $query)
    {
        $institutionId = $this->getInstitutionID();
        $studentId = $this->studentId;
        $query->contain('Assessments');
        $query->contain('AssessmentPeriods');
        $query->contain('EducationSubjects');
        $AssessmentPeriods = TableRegistry::get('Assessment.AssessmentPeriods');
        $query->contain('Assessments');
        $query->contain('AssessmentPeriods');
        $query->contain('EducationSubjects');
        $AssessmentPeriods = TableRegistry::get('Assessment.AssessmentPeriods');
        $query->find('all', [
            'fields' => [
                'id' => $this->aliasField('id'),
                'academic_period_id' => $this->aliasField('academic_period_id'),
                'assessment_period_id' => 'AssessmentPeriods.id',
                'assessment_period_name' => 'AssessmentPeriods.name',
                'assessment_period_term' => 'AssessmentPeriods.academic_term',
                'assessment_id' => 'Assessments.id',
                'assessment_name' => 'Assessments.name',
                'assessment_code' => 'Assessments.code',
                'education_grade_id' => $this->aliasField('education_grade_id'),
                'education_subject_name' => 'EducationSubjects.name',
                'education_subject_id' => 'EducationSubjects.id',
                'student_id' => $this->aliasField('student_id'),
                'institution_id' => $this->aliasField('institution_id'),
                'marks' => $this->aliasField('marks'),
            ]
        ])
        ->group([
            $this->aliasField('student_id'),
            $this->aliasField('education_subject_id'),
            $this->aliasField('assessment_id'),
            $this->aliasField('assessment_period_id'),
            $AssessmentPeriods->aliasField('academic_term'),
        ]);
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $selectedAcademicPeriod =
            !is_null($this->request->getQuery('academic_period_id'))
                ? $this->request->getQuery('academic_period_id')
                : $this->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        $selectedAcademicPeriod = $this->setAcademicPeriodOptions($institutionId, $studentId, $selectedAcademicPeriod);

        if (!empty($selectedAcademicPeriod)) {
            $selectedAssessment = $this->setAssessmentOptions($selectedAcademicPeriod, $selectedAssessment);
            $where[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;

        }
        if (!empty($selectedAssessment)) {
            $selectedAssessmentPeriod = $this->setAssessmentPeriodOptions($selectedAssessment, $selectedAssessmentPeriod);
            if ($selectedAssessment != null) {
                if ($selectedAssessment != -1) {
                    $where[$this->aliasField('assessment_id')] = $selectedAssessment;
                }
            }

        }
        if (!empty($selectedAssessmentPeriod)) {
            if ($selectedAssessmentPeriod != null) {
                if ($selectedAssessmentPeriod != -1) {
                    $where[$this->aliasField('assessment_period_id')] = $selectedAssessmentPeriod;
                }
            }
        }
        $where[$this->aliasField('institution_id')] = $institutionId;
        $where[$this->aliasField('student_id')] = $studentId;
        $query->find('all')->where([$where]);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $fields[] = [
            'key' => 'CurrentAssessments.User.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => 'OpenEMIS ID',
        ];
        $fields[] = [
            'key' =>  'CurrentAssessments.student_id',
            'field' => 'student_id',
            'type' => 'string',
            'label' => 'Student Name',
        ];
        $fields[] = [
            'key' => 'CurrentAssessments.academic_period_id',
            'field' => 'academic_period_id',
            'type' => 'string',
            'label' => 'Academic Period',
        ];
        $fields[] = [
            'key' => 'CurrentAssessments.assessment_id',
            'field' => 'assessment_id',
            'type' => 'string',
            'label' => 'Assessment Name',
        ];
        $fields[] = [
            'key' => 'CurrentAssessments.assessment_period_id',
            'field' => 'assessment_period_id',
            'type' => 'string',
            'label' => 'Assessment Periods',
        ];
        $fields[] = [
            'key' => 'CurrentAssessments.education_subject_id',
            'field' => 'education_subject_id',
            'type' => 'string',
            'label' => 'Education Subjects',
        ];
        $fields[] = [
            'key' => 'CurrentAssessments.marks',
            'field' => 'marks',
            'type' => 'integer',
            'label' => 'Marks',
        ];
        $fields[] = [
            'key' => 'Assessment.AssessmentItemResults',
            'field' => 'total_mark',
            'type' => 'string',
            'label' => 'Total Marks',
        ];
    }
    
    public function onExcelGetOpenemisNo(Event $event, Entity $entity)
    {
        return $entity->user->openemis_no;
    }

    public function onExcelGetAssessmentId(Event $event, Entity $entity)
    {
        return $entity->assessment_name . ' - ' . $entity->assessment_code . ' - ' . $entity->assessment_name;
    }

    public function onExcelGetAssessmentPeriodId(Event $event, Entity $entity)
    {
        return $entity->assessment_period_term . ' - ' . $entity->assessment_period_name;
    }

    public function onExcelGetTotalMark(Event $event, Entity $entity)
    {
        $ItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
        $options = ["student_id" => $entity->student_id,
            "academic_period_id" => $entity->academic_period_id,
            "education_grade_id" => $entity->education_grade_id,
            "education_subject_id" => $entity->education_subject_id,
            "assessment_period_id" => -1,
            'assessment_id' => $entity->assessment_id];
        $marks = $ItemResults::getLastMark($options);
        $last_results = array_column($marks, 'marks');
        $sum_results = array_sum($last_results);
        return round($sum_results, 2);
    }
    //POCOR-8137 END
}
