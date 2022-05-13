<?php

namespace Student\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use Cake\Core\Configure;

class StudentAssessmentsTable extends ControllerActionTable
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
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Results' => ['index', 'add'],
            'OpenEMIS_Classroom' => ['add', 'edit', 'delete']
        ]);
        if (!in_array('Risks', (array)Configure::read('School.excludedPlugins'))) {
            $this->addBehavior('Risk.Risks');
        }
        $this->addBehavior('Import.ImportLink');
    }

    public function getTemplateOptions($period, $templateQuerystring)
    {
        $templateOptions = $this->Assessments
                                ->find('list')
                                ->where([
                                    $this->Assessments->aliasField('academic_period_id') => $period
                                ])
                                ->order([$this->Assessments->aliasField('created') => 'DESC'])
                                ->toArray();
        if (empty($templateOptions) && $this->action == 'index') { //show no template option on index page only.
            $templateOptions['empty'] = $this->getMessage('Assessments.noTemplates');
        }
        if ($templateQuerystring) {
            $selectedTemplate = $templateQuerystring;
        } else {
            $selectedTemplate = key($templateOptions);
        }
        return compact('templateOptions', 'selectedTemplate');
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $session = $this->request->session();
        list($periodOptions, $selectedPeriod) = array_values($this->Assessments->getAcademicPeriodOptions($this->request->query('period')));
        $extra['selectedPeriod'] = $selectedPeriod;
        list($templateOptions, $selectedTemplate) = array_values($this->getTemplateOptions($selectedPeriod, $this->request->query('template')));
        $extra['selectedTemplate'] = $selectedTemplate;
        $extra['elements']['control'] = [
            'name' => 'Assessment.controls',
            'data' => [
                'periodOptions'=> $periodOptions,
                'selectedPeriod'=> $selectedPeriod,
                'templateOptions'=> $templateOptions,
                'selectedTemplate' => $selectedTemplate
            ],
            'order' => 3
        ];
        $this->field('academic_period_id', ['type' => 'integer', 'order' => 0]);
        $this->field('institution_id', ['type' => 'integer', 'after' => 'academic_period_id']);
        $this->field('academic_period_id', ['type' => 'integer']);
        $this->field('education_grade_id', ['type' => 'integer']);
        $this->field('education_subject_id', ['type' => 'integer']);
        $this->field('assessment_period_id', ['type' => 'integer']);
        $this->field('total_mark', ['attr' => ['visible' => true,'value'=> 'sd']]);
        $this->field('assessment_grading_option_id', ['visible' => false]);
        $this->field('institution_id', ['visible' => false]);
        $this->setFieldOrder(['academic_period_id','education_grade_id', 'education_subject_id', 'assessment_period_id', 'marks','total_mark']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->where([$this->aliasField('assessment_id') => $extra['selectedTemplate']]); //show assessment period based on the selected assessment.
        if ($extra['selectedTemplate'] != 'empty') {
            $extra['toolbarButtons']['editAcademicTerm'] = [
                'url' => [
                    'plugin' => 'Assessment',
                    'controller' => 'Assessments',
                    'action' => 'AssessmentPeriods',
                    '0' => 'editAcademicTerm',
                    'template' => $extra['selectedTemplate'],
                    'period' => $extra['selectedPeriod']
                ],
                'type' => 'button',
                'label' => '<i class="kd-edit"></i>',
                'attr' => [
                    'class' => 'btn btn-xs btn-default',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'title' => __('Edit Academic Term')
                ]
            ];
        }
        $userIddd = $_SESSION['Auth']['User']['id'];
        $query->where(['student_id'=> $userIddd]);
        
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

    public function onGetTotalMark(Event $event, Entity $entity)
    {
        return $this->getTotalMark($entity->student_id,$entity->academic_period_id,$entity->education_subject->id,$entity->education_grade_id );
    }
    
    public function onGetEducationSubjectId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('education_subject')) {
            $value = $entity->education_subject->code_name;
        }
        return $value;
    }
    
    private function setupTabElements()
    {
        $options['type'] = 'student';
        $tabElements = $this->controller->getAcademicTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Assessments');
    }
  
    public function getTotalMark($studentId, $academicPeriodId, $educationSubjectId, $educationGradeId)
    {  
        $query = $this->find();
        $totalMarks = $query
            ->matching('Assessments')
            ->matching('AssessmentPeriods')
            ->matching('AssessmentGradingOptions.AssessmentGradingTypes')
            ->order([
                $this->aliasField('created') => 'DESC'
            ])
            ->where([
                $this->aliasField('student_id') => $studentId,
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('education_subject_id') => $educationSubjectId,
                $this->aliasField('education_grade_id') => $educationGradeId,
            ])
            ->group([
                $this->aliasField('assessment_period_id')
            ])->toArray(); 
            $sumMarks = [];
            foreach ($totalMarks as $result) {
                $assessmentItemResults = TableRegistry::get('assessment_item_results');
                $assessmentItemResultsData = $assessmentItemResults->find()
                        ->select([
                            $assessmentItemResults->aliasField('marks')
                        ])
                        ->order([
                            $assessmentItemResults->aliasField('modified') => 'DESC',
                            $assessmentItemResults->aliasField('created') => 'DESC'
                            
                        ])
                        ->where([
                            $assessmentItemResults->aliasField('student_id') => $result['student_id'],
                            $assessmentItemResults->aliasField('academic_period_id') => $result['academic_period_id'],
                            $assessmentItemResults->aliasField('education_grade_id') => $result['education_grade_id'],
                            $assessmentItemResults->aliasField('assessment_period_id') => $result['assessment_period_id'],
                            $assessmentItemResults->aliasField('education_subject_id') => $result['education_subject_id'],
                        ])
                        ->first();
                    
                    $sumMarks[] = $assessmentItemResultsData->marks*$result->_matchingData['AssessmentPeriods']->weight; 
            }
            $sumMarks = array_sum($sumMarks);
            
            return $sumMarks;
    }
}