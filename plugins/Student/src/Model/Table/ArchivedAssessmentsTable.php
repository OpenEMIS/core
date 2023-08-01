<?php

namespace Student\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Archive\Model\Table\DataManagementConnectionsTable as ArchiveConnections;
use App\Model\Table\ControllerActionTable;

class ArchivedAssessmentsTable extends ControllerActionTable
{
    private $institutionId = null;
    private $studentId = null;

    public function initialize(array $config)
    {
        $this->table('assessment_item_results_archived');
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
        $module = __('Assessments Archived');
        $contentHeader = $studentName . ' - ' . $module;
        $this->controller->set('contentHeader', $contentHeader);
        $this->controller->Navigation->substituteCrumb(__('Student Assessment Archived'), $module);
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
        $extra['elements']['controls'] = ['name' => 'Student.Assessments/controls', 'data' => [], 'options' => [], 'order' => 1];

        $this->addExtraButtons($extra);
        // Start POCOR-5188
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        //POCOR-7201[START]
        $institutionId = $this->institutionId;
        $studentId = $this->studentId;
        //POCOR-7201[END]
        $query->contain('Assessments');
        $query->contain('AssessmentPeriods');
        $query->contain('EducationSubjects');

        $query->find('all', [
            'fields' => [
                'id' => $this->aliasField('id'),
                'education_subject_name' => 'EducationSubjects.name',
                'education_subject_id' => 'EducationSubjects.id',
                'assessment_period_id' => 'AssessmentPeriods.id',
                'assessment_period_name' => 'AssessmentPeriods.name ',
                'assessment_id' => 'Assessments.id',
                'assessment_name' => 'Assessments.name ',
                'assessment_code' => 'Assessments.code ',
                'marks' => $this->aliasField('marks'),
                'academic_period_id' => $this->aliasField('academic_period_id'),
                'education_grade_id' => $this->aliasField('education_grade_id'),
                'student_id' => $this->aliasField('student_id'),
                'institution_class_id' => $this->aliasField('institution_classes_id'),
                'institution_id' => $this->aliasField('institution_id'),

            ]
        ]);
        $selectedAcademicPeriod = !is_null(
            $this->request->query('academic_period_id'))
            ?
            $this->request->query('academic_period_id')
            :
            $this->AcademicPeriods->getCurrent();
        $selectedAssessment = !is_null(
            $this->request->query('assessment_id'))
            ?
            $this->request->query('assessment_id')
            :
            null;
        $selectedAssessmentPeriod = !is_null(
            $this->request->query('assessment_period_id'))
            ?
            $this->request->query('assessment_period_id')
            :
            null;
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

    public function onGetAssessmentsName(Event $event, Entity $entity)
    {
        return $entity->assessment_code . ' - ' . $entity->assessment_name;
    }

    public function onGetTotalMark(Event $event, Entity $entity)
    {
        $ItemResults = TableRegistry::get('Institution.AssessmentItemResultsArchived');
        $studentId = $entity->student_id;
        $academicPeriodId = $entity->academic_period_id;
        $educationSubjectId = $entity->education_subject_id;
        $educationGradeId = $entity->education_grade_id;
        $institutionClassesId = $entity->institution_class_id;
        $assessmentPeriodId = '';
        $institutionId = $entity->institution_id;
        $totalMark = $ItemResults->getTotalMarksForAssessmentArchived($studentId, $academicPeriodId, $educationSubjectId, $educationGradeId, $institutionClassesId, $assessmentPeriodId, $institutionId);//POCOR-7201
        return round($totalMark->calculated_total, 2);
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
        $this->addBackButton($toolbarButtons);
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
    private function addBackButton($toolbarButtons)
    {
        $is_archive_exists = true;
        if ($is_archive_exists) {
            $customButtonName = 'back';
            $customButtonUrl = [
                'plugin' => 'Student',
                'controller' => 'Students',
                'action' => 'Assessments'
            ];
            $customButtonLabel = '<i class="fa kd-back"></i>';
            $customButtonTitle = __('Back');
            $this->generateButton($toolbarButtons, $customButtonName, $customButtonTitle, $customButtonLabel, $customButtonUrl);
        }
    }

    /**
     * @param $institutionId
     * @param $studentId
     */
    private function setAcademicPeriodOptions($selectedAcademicPeriod)
    {
// Academic Periods filter

        $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
//
        $institutionId = $this->institutionId;
        $studentId = $this->studentId;

        $academicPeriodStudentAttendanceArray = ArchiveConnections::getArchiveYears('assessment_item_results',
            ['institution_id' => $institutionId,
                'student_id' => $studentId]);
        if (!$selectedAcademicPeriod) {
            $selectedYear = end($academicPeriodStudentAttendanceArray);
        } else {
            $selectedYear = $selectedAcademicPeriod;
        }
        if (sizeof($academicPeriodStudentAttendanceArray) == 0) {
            $academicPeriodStudentAttendanceArray = [0];
            $selectedYear = null;
        }
//        $this->log('uniqu_array', 'debug');
//        $this->log($academicPeriodStudentAttendanceArray, 'debug');
        $conditions = [
            'current !=' => 1,
            'id IN' => $academicPeriodStudentAttendanceArray
        ];
        if (sizeof($academicPeriodStudentAttendanceArray) > 0) {
            $academicPeriodOptions = $AcademicPeriod->getYearList(['conditions' => $conditions]);
            if (empty($this->request->query['academic_period'])) {
                $this->request->query['academic_period'] = $selectedYear;
            }
        }
        $selectedPeriod = $this->request->query['academic_period'];

        $this->advancedSelectOptions($academicPeriodOptions, $selectedPeriod);
        $this->controller->set(compact('academicPeriodOptions', 'selectedPeriod'));
        return $selectedPeriod;

    }

    /**
     * @param Query $selectedAcademicPeriod
     * @return array
     */
    private function setAssessmentOptions($selectedAcademicPeriod, $selectedAssessment = -1)
    {
        $Assessments = TableRegistry::get('Assessment.Assessments');
        $this->AcademicPeriodId = $selectedAcademicPeriod;
        $whereArchive = ['academic_period_id' => $selectedAcademicPeriod,
            'institution_id' => $this->institutionId,
            'student_id' => $this->studentId];
        $archived_assessment_array =
            ArchiveConnections::getArchiveAssessments('assessment_item_results',
                $whereArchive);
        $assessment_array = [0];
        if (sizeof($archived_assessment_array) > 0) {
            $assessment_array = $archived_assessment_array;
        }
//        $this->log('assessments_array', 'debug');
//        $this->log($assessment_array, 'debug');
        $where = [$Assessments->aliasField('academic_period_id') => $selectedAcademicPeriod,
            $Assessments->aliasField('id IN') => $assessment_array];

        $assessmentOptions = $Assessments
            ->find('list')
            ->where($where)
            ->toArray();
        $assessmentOptions = ['-1' => __('All Assessments')] + $assessmentOptions;
        $selectedAssessment = $this->advancedSelectOptions($assessmentOptions, $selectedAssessment);
        $this->controller->set(compact('assessmentOptions', 'selectedAssessment'));
        return $selectedAssessment;

    }

    private function setAssessmentPeriodOptions($selectedAssessment = -1, $selectedAssessmentPeriod = -1)
    {
        $AssessmentPeriods = TableRegistry::get('Assessment.AssessmentPeriods');
//        $where = [];
        $whereArchive = [
            'academic_period_id' => $this->AcademicPeriodId,
            'institution_id' => $this->institutionId,
            'student_id' => $this->studentId];
        if ($selectedAssessment > 0) {
            $whereArchive['assessment_id'] = $selectedAssessment;
        }
        $archived_assessment_period_array =
            ArchiveConnections::getArchiveAssessmentPeriods('assessment_item_results',
                $whereArchive);
        $assessment_period_array = [0];
        if (sizeof($archived_assessment_period_array) > 0) {
            $assessment_period_array = $archived_assessment_period_array;
        }
        $this->log('assessment_period_array', 'debug');
        $this->log($whereArchive, 'debug');
        $this->log($assessment_period_array, 'debug');
        $where = [$AssessmentPeriods->aliasField('id IN') => $assessment_period_array];

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
