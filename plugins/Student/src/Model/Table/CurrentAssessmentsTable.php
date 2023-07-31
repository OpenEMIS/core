<?php

namespace Student\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;

use App\Model\Table\ControllerActionTable;

class CurrentAssessmentsTable extends ControllerActionTable
{

    private $institutionId = null;
    private $studentId = null;

    public function initialize(array $config)
    {
        $this->table('assessment_item_results');
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
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $contentHeader = $this->controller->viewVars['contentHeader'];
        list($studentName, $module) = explode(' - ', $contentHeader);
        $module = __('Assessments');
        $contentHeader = $studentName . ' - ' . $module;
        $this->controller->set('contentHeader', $contentHeader);
        $this->controller->Navigation->substituteCrumb(__('Student Assessment'), $module);
        $session = $this->controller->request->session();
        if ($session->check('Institution.Institutions.id')) {
            $institutionId = $session->read('Institution.Institutions.id');
        }
        $studentId = $this->Session->read('Student.Students.id');
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

        //add controls with ctp

        $extra['elements']['controls'] = [
            'name' => 'Student.Assessments/controls',
            'data' => [],
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
        $institutionId = $session->read('Institution.Institutions.id');
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
            !is_null($this->request->query('academic_period_id'))
                ? $this->request->query('academic_period_id')
                : $this->AcademicPeriods->getCurrent();
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
        $marks = $ItemResults::getLastMark($options);
        return round($marks[0]['marks'], 2);
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
        $last_results = array_column($marks, 'marks');
        $sum_results = array_sum($last_results);
        return round($sum_results, 2);
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        if (array_key_exists('view', $buttons)) {
            $institutionId = $entity->institution_class->institution_id;
            $url = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'Subjects',
                'view',
                $this->paramsEncode(['id' => $entity->institution_subject->id]),
                'institution_id' => $institutionId,
            ];

            if ($this->controller->name == 'Directories') {
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
        $tabElements = $this->controller->getAcademicTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Assessments');
    }

    private function generateButton(ArrayObject $toolbarButtons, $name, $title, $label, $url, $btnAttr = null)
    {
        if (!$btnAttr) {
            $btnAttr = $this->getButtonAttr();
        }
        $customButton = [];
        if (array_key_exists('_ext', $url)) {
            unset($customButton['url']['_ext']);
        }
        if (array_key_exists('pass', $url)) {
            unset($customButton['url']['pass']);
        }
        if (array_key_exists('paging', $url)) {
            unset($customButton['url']['paging']);
        }
        if (array_key_exists('filter', $url)) {
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
        if ($is_archive_exists) {
            $customButtonName = 'archive';
            $customButtonUrl = [
                'plugin' => 'Student',
                'controller' => 'Students',
                'action' => 'AssessmentsArchived'
            ];
            $customButtonLabel = '<i class="fa fa-folder"></i>';
            $customButtonTitle = __('Archive');
            $this->generateButton($toolbarButtons, $customButtonName, $customButtonTitle, $customButtonLabel, $customButtonUrl);
        }
    }

    private function isArchiveExists()
    {
        $is_archive_exists = true;
        $institutionId = $this->institutionId;
        $studentId = $this->studentId;
        //POCOR-7526::Start
        $connection = ConnectionManager::get('default');
        $getArchiveData = $connection->query("SHOW TABLES LIKE 'assessment_item_results_archived' ");
        $archiveDataArr = $getArchiveData->fetch();
        if (!empty($archiveDataArr)) {
            $AssessmentItemResultsArchived = TableRegistry::get('Institution.AssessmentItemResultsArchived');
            $count = $AssessmentItemResultsArchived->find()
                //            ->distinct([$AssessmentItemResultsArchived->aliasField('student_id')])// POCOR-7339-HINDOL
                ->select([$AssessmentItemResultsArchived->aliasField('student_id')])// POCOR-7339-HINDOL
                ->where([
                    $AssessmentItemResultsArchived->aliasField('institution_id') => $institutionId,
                    $AssessmentItemResultsArchived->aliasField('student_id') => $studentId,
                ])->first();
            if ($count) {
                $is_archive_exists = true;
            }
            if (!$count) {
                $is_archive_exists = false;
            }
        }
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
        $ItemResults = TableRegistry::get('assessment_item_results');
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
     * @param Query $selectedAcademicPeriod
     * @return array
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private function setAssessmentOptions($selectedAcademicPeriod, $selectedAssessment = -1)
    {
        $ItemResults = TableRegistry::get('assessment_item_results');
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
     * @param int $selectedAssessment
     * @param int $selectedAssessmentPeriod
     * @return int|string|null
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private function setAssessmentPeriodOptions($selectedAssessment = -1, $selectedAssessmentPeriod = -1)
    {
        $ItemResults = TableRegistry::get('assessment_item_results');
        $assessment_periods_arr = $ItemResults->find()
            ->select('assessment_id')
            ->distinct('assessment_id')
            ->where(['student_id' => $this->studentId])
            ->toArray();
        $assessment_periods_ids = array_column($assessment_periods_arr, 'assessment_id');
        if(sizeof($assessment_periods_ids) == 0){
            $assessment_periods_ids = [0];
        }
        $AssessmentPeriods = TableRegistry::get('Assessment.AssessmentPeriods');
        $where = [$AssessmentPeriods->aliasField('id IN') => $assessment_periods_ids];
        if ($selectedAssessment != '-1') {
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

}
