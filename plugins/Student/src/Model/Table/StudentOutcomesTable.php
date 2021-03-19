<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;

class StudentOutcomesTable extends ControllerActionTable
{
    private $studentId;

    public function initialize(array $config)
    {
        $this->table('institution_outcome_results');

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
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $session = $this->request->session();
        if ($this->controller->name == 'Directories') {
            $this->studentId = $session->read('Directory.Directories.id');
        } else if ($this->controller->name == 'Profiles') {
            $this->studentId = $session->read('Auth.User.id');
        } else {
            $this->studentId = $session->read('Student.Students.id');
        }

        $this->field('outcome_period_id', ['type' => 'integer']);
        $this->field('education_subject_id', ['type' => 'integer']);
        $this->field('outcome_criteria_id', ['type' => 'integer']);

        $this->setFieldOrder(['outcome_period_id', 'education_subject_id', 'outcome_criteria_id', 'outcome_grading_option_id']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // academic period filter
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->query('academic_period')) ? $this->request->query('academic_period') : $this->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $conditions[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        // end

        // outcome template filter
        $InstitutionStudents = TableRegistry::get('Institution.Students');
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

        $selectedTemplate = !is_null($this->request->query('template')) ? $this->request->query('template') : key($templateOptions);
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

        $selectedPeriod = !is_null($this->request->query('period')) ? $this->request->query('period') : 0;
        $this->controller->set(compact('periodOptions', 'selectedPeriod'));
        if (!empty($selectedPeriod)){
            $conditions[$this->aliasField('outcome_period_id')] = $selectedPeriod;
        }
        // end

        // education subject filter
        $subjectOptions = [];
        if (!empty($selectedTemplate)){
            $session = $this->request->session();
            $studentId = $session->read('Student.Students.id');
            $InstitutionSubjectStudents = TableRegistry::get('Institution.InstitutionSubjectStudents');
            $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
            $EducationSubjects = TableRegistry::get('Education.EducationSubjects');
            $subjectOptions = $EducationSubjects
                                ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                                ->innerJoin([$InstitutionSubjects->alias() => $InstitutionSubjects->table()], [
                                   $InstitutionSubjects->aliasField('education_subject_id = ') . $EducationSubjects->aliasField('id')
                                ])
                                ->innerJoin([$InstitutionSubjectStudents->alias() => $InstitutionSubjectStudents->table()], [
                                   $InstitutionSubjectStudents->aliasField('institution_subject_id = ') . $InstitutionSubjects->aliasField('id')
                                ])
                                ->where([$InstitutionSubjectStudents->aliasField('student_id') => $studentId ])
                                ->toArray(); 
             
            $subjectOptions = ['0' => __('All Subjects')] + $subjectOptions;
        }

        $selectedSubject = !is_null($this->request->query('subject')) ? $this->request->query('subject') : 0;
        $this->controller->set(compact('subjectOptions', 'selectedSubject'));
        if (!empty($selectedSubject)){
            $conditions[$this->aliasField('education_subject_id')] = $selectedSubject;
        }
        // end

        $extra['elements']['controls'] = ['name' => 'Student.Outcomes/controls', 'data' => [], 'options' => [], 'order' => 1];
        $extra['auto_contain_fields'] = [
            'OutcomePeriods' => ['code'],
            'EducationSubjects' => ['code'],
            'OutcomeCriterias' => ['code'],
            'OutcomeGradingOptions' => ['code']
        ];

		$userData = $this->Session->read();
		$studentId = $userData['Auth']['User']['id'];
		
		if(!empty($userData['System']['User']['roles']) & !empty($userData['Student']['Students']['id'])) {

		} else {
			if (!empty($studentId)) {
				$conditions[$this->aliasField('student_id')] = $studentId;
			}
		}

        $query->where($conditions);
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

    public function onGetOutcomePeriodId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('outcome_period')) {
            $value = $entity->outcome_period->code_name;
        }
        return $value;
    }

    public function onGetEducationSubjectId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('education_subject')) {
            $value = $entity->education_subject->code_name;
        }
        return $value;
    }

    public function onGetOutcomeCriteriaId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('outcome_criteria')) {
            $value = $entity->outcome_criteria->code_name;
        }
        return $value;
    }

    public function onGetOutcomeGradingOptionId(Event $event, Entity $entity)
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
        $tabElements = $this->controller->getAcademicTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Outcomes');
    }
}
