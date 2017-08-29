<?php
namespace Assessment\Model\Table;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\ORM\Query;

use App\Model\Table\AppTable;

class AssessmentItemsTable extends AppTable
{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Assessments', ['className' => 'Assessment.Assessments']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsToMany('AssessmentPeriods', [
            'className' => 'Assessment.AssessmentPeriods',
            'joinTable' => 'assessment_items_grading_types',
            'foreignKey' => ['assessment_id', 'education_subject_id'],
            'bindingKey' => ['assessment_id', 'education_subject_id'],
            'targetForeignKey' => 'assessment_period_id',
            'through' => 'Assessment.AssessmentItemsGradingTypes',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->belongsToMany('AssessmentGradingTypes', [
            'className' => 'Assessment.AssessmentGradingTypes',
            'joinTable' => 'assessment_items_grading_types',
            'foreignKey' => ['assessment_id', 'education_subject_id'],
            'bindingKey' => ['assessment_id', 'education_subject_id'],
            'targetForeignKey' => 'assessment_grading_type_id',
            'through' => 'Assessment.AssessmentItemsGradingTypes',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Results' => ['index']
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $validator
            ->add('weight', 'ruleIsDecimal', [
                'rule' => ['decimal', null],
            ])
            ->add('weight', 'ruleWeightRange', [
                'rule'  => ['range', 0, 2],
                'last' => true
            ]);
        return $validator;
    }

    public function populateAssessmentItemsArray($gradeId)
    {
        $EducationGradesSubjects = TableRegistry::get('Education.EducationGradesSubjects');
        $gradeSubjects = $EducationGradesSubjects->find()
            ->contain('EducationSubjects')
            ->where([$EducationGradesSubjects->aliasField('education_grade_id') => $gradeId])
            ->order(['order'])
            ->toArray();

        $assessmentItems = [];
        foreach ($gradeSubjects as $key => $gradeSubject) {
            if (!empty($gradeSubject->education_subject)) {
                $assessmentItems[] = [
                    'education_subject_id' => $gradeSubject->education_subject->id,
                    'education_subject' => $gradeSubject->education_subject,
                    'weight' => '0.00'
                ];
            }
        }
        return $assessmentItems;
    }


    public function findStaffSubjects(Query $query, array $options)
    {
        if (isset($options['class_id']) && isset($options['staff_id'])) {
            $classId = $options['class_id'];
            $staffId = $options['staff_id'];
            $query->where([
                    // For subject teachers
                    'EXISTS (
                        SELECT 1
                        FROM institution_subjects InstitutionSubjects
                        INNER JOIN institution_class_subjects InstitutionClassSubjects
                            ON InstitutionClassSubjects.institution_class_id = '.$classId.'
                            AND InstitutionClassSubjects.institution_subject_id = InstitutionSubjects.id
                        INNER JOIN institution_subject_staff InstitutionSubjectStaff
                            ON InstitutionSubjectStaff.institution_subject_id = InstitutionSubjects.id
                            AND InstitutionSubjectStaff.staff_id = '.$staffId.'
                        WHERE InstitutionSubjects.education_subject_id = ' . $this->aliasField('education_subject_id') .')'
                ]);

            return $query;
        }
    }

    public function findAssessmentItemsInClass(Query $query, array $options)
    {
        $ClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');
        $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
        $assessmentId = $options['assessment_id'];
        $classId = $options['class_id'];

        $query
            ->contain('EducationSubjects')
            ->innerJoin([$ClassSubjects->alias() => $ClassSubjects->table()], [
                $ClassSubjects->aliasField('institution_class_id') => $classId
            ])
            ->innerJoin([$InstitutionSubjects->alias() => $InstitutionSubjects->table()], [
                $InstitutionSubjects->aliasField('id = ') . $ClassSubjects->aliasField('institution_subject_id'),
                $InstitutionSubjects->aliasField('education_subject_id = ') . $this->aliasField('education_subject_id'),
            ])
            ->where([$this->aliasField('assessment_id') => $assessmentId])
            ->order(['EducationSubjects.order', 'EducationSubjects.code', 'EducationSubjects.name']);

        return $query;
    }

    public function getSubjects($assessmentId)
    {
        $subjectList = $this
            ->find()
            ->innerJoinWith('EducationSubjects')
            ->where([$this->aliasField('assessment_id') => $assessmentId])
            ->select([
                'assessment_item_id' => $this->aliasField('id'),
                'education_subject_name' => 'EducationSubjects.name',
                'subject_id' => $this->aliasField('education_subject_id'),
                'subject_weight' => $this->aliasField('weight'),
            ])
            ->order(['EducationSubjects.order'])
            ->hydrate(false)
            ->toArray();
        return $subjectList;
    }

    public function getAssessmentItemSubjects($assessmentId)
    {
        $subjectList = $this
            ->find()
            ->matching('EducationSubjects')
            ->where([$this->aliasField('assessment_id') => $assessmentId])
            ->select([
                'assessment_item_id' => $this->aliasField('id'),
                'education_subject_id' => 'EducationSubjects.id',
                'education_subject_name' => $this->find()->func()->concat([
                    'EducationSubjects.code' => 'literal',
                    " - ",
                    'EducationSubjects.name' => 'literal'
                ])
            ])
            ->order(['EducationSubjects.order'])
            ->hydrate(false)
            ->toArray();
        return $subjectList;
    }

    public function afterDelete()
    {
        // delete all AssessmentItemsGradingTypes by education_subject_id and assessment_id
    }
}
