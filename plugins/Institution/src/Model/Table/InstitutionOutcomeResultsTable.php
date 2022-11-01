<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class InstitutionOutcomeResultsTable extends AppTable
{
    public function initialize(array $config)
    {
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

        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentOutcomes' => ['index', 'add']
        ]);

        $this->addBehavior('CompositeKey');
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);
        
        $validator
            ->add('outcome_criteria_id', 'custom', [
                'rule' => function ($value, $context) {

            $allowSubjectList = $this->getAllowedSubjectList();
            $outcomeCriterias = TableRegistry::get('Outcome.OutcomeCriterias');
            $outcomeCriteriasList = $outcomeCriterias
                ->find()
                ->where([$outcomeCriterias->aliasField('id') => $value])
                ->first();
                    return in_array($outcomeCriteriasList->education_subject_id, $allowSubjectList);
                },
                'message' => __('You do not have permission for this subject'),
                'on' => function ($context) {  
                    if (array_key_exists('action_type', $context['data']) && $context['data']['action_type'] == 'imported') {
                        return true;
                    }
                    return false;
                }
            ])
            ;
        return $validator;
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        // do not save new record if result is empty
        $gradingOption = $entity->outcome_grading_option_id;
        if ($entity->isNew() && empty($gradingOption)) {
            return false;
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        // delete record if user removes result
        $gradingOption = $entity->outcome_grading_option_id;
        if (empty($gradingOption)) {
            $this->delete($entity);
        }
    }

    public function findStudentResults(Query $query, array $options)
    {
        $studentId = $options['student_id'];
        $outcomeTemplateId = $options['outcome_template_id'];
        $outcomePeriodId = $options['outcome_period_id'];
        $educationGradeId = $options['education_grade_id'];
        $educationSubjectId = $options['education_subject_id'];
        $institutionId = $options['institution_id'];
        $academicPeriodId = $options['academic_period_id'];

        return $query
            ->where([
                $this->aliasField('student_id') => $studentId,
                $this->aliasField('outcome_template_id') => $outcomeTemplateId,
                $this->aliasField('outcome_period_id') => $outcomePeriodId,
                $this->aliasField('education_grade_id') => $educationGradeId,
                $this->aliasField('education_subject_id') => $educationSubjectId,
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('academic_period_id') => $academicPeriodId
            ]);
    }

    private function getAllowedSubjectList()
    {
        $ImportOutcomeResults = TableRegistry::get('Institution.ImportOutcomeResults');
        $userId = $ImportOutcomeResults->Auth->user('id');
        $AccessControl = $ImportOutcomeResults->AccessControl;
        $classId = $ImportOutcomeResults->request->query('class');
        $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
        return $InstitutionSubjects
            ->find('list', [
                'keyField' => 'education_subject_id',
                'valueField' => 'education_subject_id'
            ])
            ->find('byAccess', ['userId' => $userId, 'accessControl' => $AccessControl, 'controller' => $ImportOutcomeResults->controller])
            ->matching('ClassSubjects', function ($q) use ($classId) {
                return $q->where(['ClassSubjects.institution_class_id' => $classId]);
            })
            ->toArray();
    }
}
