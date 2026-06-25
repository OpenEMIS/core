<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;

class StudentOutcomesTable extends ControllerActionTable
{
    private $studentId;

    public function initialize(array $config): void
    {
        $this->setTable('institution_outcome_results');

        parent::initialize($config);
       
        $this->belongsTo('OutcomeGradingOptions', ['className' => 'Outcome.OutcomeGradingOptions']);
        $this->belongsTo('Students', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('OutcomeTemplates', [
            'className' => 'Outcome.OutcomeTemplates',
            'foreignKey' => ['outcome_template_id', 'academic_period_id'],
            'bindingKey' => ['id', 'academic_period_id']
        ]);
        $this->belongsTo('OutcomePeriods', [
            'className' => 'Outcome.OutcomePeriods',
            'foreignKey' => ['outcome_period_id', 'academic_period_id'],
            'bindingKey' => ['id', 'academic_period_id']
        ]);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('OutcomeCriterias', [
            'className' => 'Outcome.OutcomeCriterias',
            'foreignKey' => ['outcome_criteria_id', 'academic_period_id', 'outcome_template_id', 'education_grade_id', 'education_subject_id'],
            'bindingKey' => ['id', 'academic_period_id', 'outcome_template_id', 'education_grade_id', 'education_subject_id']
        ]);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

        $this->toggle('view', false);
        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        $this->toggle('search', false);
        //$this->addBehavior('User.UserTab');
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['Outcomes' =>['id', 'institution_id']
            ]
        ]);
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $queryString = $this->getQueryString();
        $studentId = $queryString['student_id'];
        $session = $this->request->getSession();
        if ($this->controller->getName() == 'Directories') {
            $this->studentId = $session->read('Directory.Directories.id');
        } else if ($this->controller->getName() == 'Profiles') {
            $this->studentId = $session->read('Auth.User.id');
        } else {
            $this->studentId = $studentId;
        }

        $this->field('outcome_period_id', ['type' => 'integer']);
        $this->field('education_subject_id', ['type' => 'integer']);
        $this->field('outcome_criteria_id', ['type' => 'integer']);

        $this->setFieldOrder(['outcome_period_id', 'education_subject_id', 'outcome_criteria_id', 'outcome_grading_option_id']);

        // Start POCOR-5188
        if($this->request->getParam('controller') == 'Institutions'){
            $is_manual_exist = $this->getManualUrl('Institutions','Programmes','Students - Academic');       
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];

                $helpBtn['url'] = $is_manual_exist['url'];
                $helpBtn['type'] = 'button';
                $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
                $helpBtn['attr'] = $btnAttr;
                $helpBtn['attr']['title'] = __('Help');
                $extra['toolbarButtons']['help'] = $helpBtn;
            }
        }elseif($this->request->getParam('controller') == 'Directories'){ 
            $is_manual_exist = $this->getManualUrl('Directory','Outcomes','Students - Academic');       
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];

                $helpBtn['url'] = $is_manual_exist['url'];
                $helpBtn['type'] = 'button';
                $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
                $helpBtn['attr'] = $btnAttr;
                $helpBtn['attr']['title'] = __('Help');
                $extra['toolbarButtons']['help'] = $helpBtn;
            }

        }
        // End POCOR-5188

    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $queryString = $this->getQueryString();
        $studentId = $this->paramsEncode('student_id');
        $this->studentId = $studentId;
        // academic period filter
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->getQuery('academic_period')) ? $this->request->getQuery('academic_period') : $this->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $conditions[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        // end

        // outcome template filter
        $InstitutionStudents = TableRegistry::getTableLocator()->get('Institution.Students');
        $studentGrades = $InstitutionStudents->find()
            ->where([
                $InstitutionStudents->aliasField('student_id') => $this->studentId,
                $InstitutionStudents->aliasField('academic_period_id') => $selectedAcademicPeriod
            ])
            ->extract('education_grade_id')
            ->toArray();

        $templateOptions = [];
        if (!empty($studentGrades)) {
            $templateOptions = $this->OutcomeTemplates
                ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                ->where([
                    $this->OutcomeTemplates->aliasField('academic_period_id') => $selectedAcademicPeriod,
                    $this->OutcomeTemplates->aliasField('education_grade_id IN ') => $studentGrades
                ])
                ->toArray();
        }

        $selectedTemplate = !is_null($this->request->getQuery('template')) ? $this->request->getQuery('template') : key($templateOptions);
        $this->controller->set(compact('templateOptions', 'selectedTemplate'));
        if (!empty($selectedTemplate)){
            $conditions[$this->aliasField('outcome_template_id')] = $selectedTemplate;
        }
        // end

        // outcome period filter
        $periodOptions = [];
        if (!empty($selectedTemplate)){
            $periodOptions = $this->OutcomePeriods
                ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                ->where([$this->OutcomePeriods->aliasField('outcome_template_id') => $selectedTemplate])
                ->toArray();
            $periodOptions = ['0' => __('All Periods')] + $periodOptions;
        }

        $selectedPeriod = !is_null($this->request->getQuery('period')) ? $this->request->getQuery('period') : 0;
        $this->controller->set(compact('periodOptions', 'selectedPeriod'));
        if (!empty($selectedPeriod)){
            $conditions[$this->aliasField('outcome_period_id')] = $selectedPeriod;
        }
        // end

        // education subject filter
        $subjectOptions = [];
        if (!empty($selectedTemplate)){
            $session = $this->request->getSession();
            //POCOR-6215 starts
            $authUser = $session->read('Auth.User');
            if($authUser['is_student'] == 1 && $authUser['is_guardian'] == 1){
                $studentId = $session->read('Profile.StudentUser.primaryKey.id');
            }else if($authUser['is_student'] == 1 && $authUser['is_guardian'] != 1){
                $studentId = $session->read('Auth.User.id');
            }else{
                $studentId = $studentId;
            }//POCOR-6215 ends
            $InstitutionSubjectStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectStudents');
            $InstitutionSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjects');
            $EducationSubjects = TableRegistry::getTableLocator()->get('Education.EducationSubjects');
            $subjectOptions = $EducationSubjects
                                ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                                ->innerJoin([$InstitutionSubjects->getAlias() => $InstitutionSubjects->getTable()], [
                                   $InstitutionSubjects->aliasField('education_subject_id = ') . $EducationSubjects->aliasField('id')
                                ])
                                ->innerJoin([$InstitutionSubjectStudents->getAlias() => $InstitutionSubjectStudents->getTable()], [
                                   $InstitutionSubjectStudents->aliasField('institution_subject_id = ') . $InstitutionSubjects->aliasField('id')
                                ])
                                ->where([$InstitutionSubjectStudents->aliasField('student_id') => $studentId, $InstitutionSubjects->aliasField('academic_period_id') => $selectedAcademicPeriod ])//6004 add academic_period_id condition
                                ->toArray(); 
             
            $subjectOptions = ['0' => __('All Subjects')] + $subjectOptions;
        }

        $selectedSubject = !is_null($this->request->getQuery('subject')) ? $this->request->getQuery('subject') : 0;
        $this->controller->set(compact('subjectOptions', 'selectedSubject'));
        if (!empty($selectedSubject)){
            $conditions[$this->aliasField('education_subject_id')] = $selectedSubject;
        }
        // end
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $extra['elements']['controls'] = ['name' => 'Student.Outcomes/controls', 'data' => ['encodedQueryString' => $encodedQueryString], 'options' => [], 'order' => 1];
        $extra['auto_contain_fields'] = [
            'OutcomePeriods' => ['code'],
            'EducationSubjects' => ['code'],
            'OutcomeCriterias' => ['code'],
            'OutcomeGradingOptions' => ['code']
        ];

		$userData = $this->Session->read();
        $session = $this->request->getSession();//POCOR-6267
        if ($userData['Auth']['User']['is_guardian'] == 1) {
            /*POCOR-6267 starts*/
            if ($this->controller->getName() == 'GuardianNavs') {
                $studentId = $session->read('Student.Students.id');
            }else {
                /**
                 * Need to add current login id as param when no data found in existing variable
                 * @author Anand Malvi <anand.malvi@mail.valuecoders.com>
				 * @ticket POCOR-6548
                 */
                //# START: [POCOR-6548] Check if user data not found then add current login user data
                $sId = $userData['Student']['ExaminationResults']['student_id'];
                if ($sId == null || empty($sId) || $sId == '') {
                    $studentId = $userData['Student']['ExaminationResults']['student_id'];
                } else {
                $studentId = $this->ControllerAction->paramsDecode($sId)['id'];
                }
                //# END: [POCOR-6548] Check if user data not found then add current login user data
            }
        } else {
            $studentId = $userData['Auth']['User']['id'];
        }
		
        /*POCOR-6267 starts*/
        if ($this->controller->getName() == 'GuardianNavs') {
            $conditions[$this->aliasField('student_id')] = $studentId;
        }else {
            if(!empty($userData['System']['User']['roles']) & !empty($userData['Student']['Students']['id'])) {

            } else {
                if (!empty($studentId)) {
                    $conditions[$this->aliasField('student_id')] = $studentId;
                }
            }
        }
		
        $query->where($conditions);


        // Start POCOR-5188
        $toolbarButtons = $extra['toolbarButtons'];
        $is_manual_exist = $this->getManualUrl('Institutions','Outcomes','Students - Academic');       
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

    public function afterAction(EventInterface $event, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

    public function onGetOutcomePeriodId(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('outcome_period')) {
            $value = $entity->outcome_period->code_name;
        }
        return $value;
    }

    public function onGetEducationSubjectId(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('education_subject')) {
            $value = $entity->education_subject->code_name;
        }
        return $value;
    }

    public function onGetOutcomeCriteriaId(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('outcome_criteria')) {
            $value = $entity->outcome_criteria->code_name;
        }
        return $value;
    }

    public function onGetOutcomeGradingOptionId(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('outcome_grading_option')) {
            $value = $entity->outcome_grading_option->code_name;
        }
        return $value;
    }

    private function setupTabElements()
    {
        $options['type'] = 'student';
        //$tabElements = $this->controller->getAcademicTabElements($options);
        $tabElements = $this->getAcademicTabElements($options);
        if($this->controller->getName() == 'GuardianNavs'|| $this->controller->getName() == 'Directories') {
			$tabElements = $this->controller->getAcademicTabElements($options);
		}
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Outcomes');
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'outcome_period_id') {
            return __('Outcome Period');
        } elseif ($field == 'education_subject_id') {
            return __('Education Subject');
        } elseif ($field == 'outcome_criteria_id') {
            return __('Outcome Criteria');
        } elseif ($field == 'outcome_grading_option_id') {
            return __('	Outcome Grading Option');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
