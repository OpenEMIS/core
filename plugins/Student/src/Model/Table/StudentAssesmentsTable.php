<?php
namespace Student\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;

use App\Model\Table\ControllerActionTable;

class StudentAssesmentsTable extends ControllerActionTable
{

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
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        
        $this->fields['student_status_id']['visible'] = false;
        $this->fields['academic_period_id']['visible'] = false;
        $this->fields['institution_id']['visible'] = false;
        $this->fields['assessment_grading_option_id']['visible'] = false;

       

        $this->field('total_mark', ['after' => 'marks']);
        $this->field('assessment_period_name',['label' =>'Assessment Period']);
        $this->field('education_subject_name',['label' =>'Education Subject']);
        $this->field('assessments_name',[
                 'attr' => ['label' =>__('Assessment Name')]
            ]);
        
        $this->setFieldOrder(['assessment_period_name', 'assessments_name','education_subject_name','marks','total_mark']);
        $extra['elements']['controls'] = ['name' => 'Student.Assesments/controls', 'data' => [], 'options' => [], 'order' => 1];

        // Start POCOR-5188
        $toolbarButtons = $extra['toolbarButtons'];
        $is_manual_exist = $this->getManualUrl('Institutions','Assessments','Students - Academic');       
        if(!empty($is_manual_exist)){
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target'=>'_blank'
            ];

            $toolbarButtons['help']['url'] = $is_manual_exist['url'];
            $toolbarButtons['help']['type'] = 'button';
            $toolbarButtons['help']['label'] = '<i class="fa fa-question-circle"></i>';
            $toolbarButtons['help']['attr'] = $btnAttr;
            $toolbarButtons['help']['attr']['title'] = __('Help');
        }
        // End POCOR-5188
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        //POCOR-7201[START]
        $session = $this->Session;
        $institutionId = $session->read('Institution.Institutions.id');
        //POCOR-7201[END]
        $query->contain('Assessments');
        $query->contain('AssessmentPeriods');
        $query->contain('EducationSubjects');

        $query->find('all',[
                'fields'=>[
                    'id'=>$this->aliasField('id'),
                    'education_subject_name'=>'EducationSubjects.name',
                    'education_subject_id' => 'EducationSubjects.id',
                    'assessment_period_id' => 'AssessmentPeriods.id',
                    'assessment_period_name' => 'AssessmentPeriods.name ',
                    'assessment_id' => 'Assessments.id',
                    'assessment_name' => 'Assessments.name ',
                    'assessment_code' => 'Assessments.code ',
                    'marks'=>$this->aliasField('marks'),
                    'academic_period_id'=>$this->aliasField('academic_period_id'),
                    'education_grade_id' => $this->aliasField('education_grade_id'),
                    'student_id' => $this->aliasField('student_id'),
                    'institution_class_id' => $this->aliasField('institution_classes_id'),
                    'institution_id' => $this->aliasField('institution_id'),
                    
                ]
        ]);
        
        // Academic Periods filter
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $selectedAcademicPeriod = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        $where[$this->aliasField('institution_id')] = $institutionId;  // POCOR-7201
        //End
        //Assesment Period filter
        if(!empty($selectedAcademicPeriod)){
            $academicPeriodRecord = $this->AcademicPeriods->get($selectedAcademicPeriod);
            $startDate = $academicPeriodRecord->start_date->format('Y-m-d');
            $endDate = $academicPeriodRecord->end_date->format('Y-m-d');

            $InstitutionStudents = TableRegistry::get('Assessment.AssessmentPeriods');
            $institutionQuery = $InstitutionStudents->find('list')
                ->where([
                    'start_date >=' => $startDate,
                    'end_date <=' => $endDate
                ])
                ->order([$InstitutionStudents->aliasField('created') => 'DESC'])
                ->toArray();

            $institutionOptions = $institutionQuery;
            $institutionOptions = ['-1' => __('All Assesment Periods')] + $institutionOptions;
            $selectedInstitution = !is_null($this->request->query('assessment_period_id')) ? $this->request->query('assessment_period_id') : -1;
            $this->controller->set(compact('institutionOptions', 'selectedInstitution'));
            
            if($selectedInstitution != "-1"){
                $where[$this->aliasField('assessment_period_id')] = $selectedInstitution;
            }
        }

        $query->find('all')->where([$where]);
        
         
    }

    public function onGetAssessmentsName(Event $event, Entity $entity)
    {
        return $entity->assessment_code .' - '.$entity->assessment_name;
    }
    public function onGetTotalMark(Event $event, Entity $entity)
    {
        $ItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
        $studentId = $entity->student_id;
        $academicPeriodId =  $entity->academic_period_id;
        $educationSubjectId =  $entity->education_subject_id;
        $educationGradeId =  $entity->education_grade_id;
        $institutionClassesId =  $entity->institution_class_id;
        $assessmentPeriodId =  '';
        $institutionId = $entity->institution_id;
        // $totalMark = $ItemResults->getTotalMarksForSubject($studentId, $academicPeriodId, $educationSubjectId, $educationGradeId,$institutionClassesId, $assessmentPeriodId, $institutionId );//POCOR-6479
        $totalMark = $ItemResults->getTotalMarksForAssessment($studentId, $academicPeriodId, $educationSubjectId, $educationGradeId,$institutionClassesId, $assessmentPeriodId, $institutionId );//POCOR-7201
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
        $options = ['type' => 'student'];
        $tabElements = $this->controller->getAcademicTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Assesments');
    }
}
