<?php
namespace Examination\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use Cake\Utility\Security;
use Cake\Event\Event;

use App\Model\Table\AppTable;

class ExaminationItemsTable extends AppTable {

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('ExaminationGradingTypes', ['className' => 'Examination.ExaminationGradingTypes']);
        $this->belongsToMany('ExaminationCentresExaminations', [
            'className' => 'Examination.ExaminationCentresExaminations',
            'joinTable' => 'examination_centres_examinations_subjects',
            'foreignKey' => 'examination_item_id',
            'targetForeignKey' => ['examination_centre_id', 'examination_id'],
            'through' => 'Examination.ExaminationCentresExaminationsSubjects',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->belongsToMany('Students', [
            'className' => 'User.Users',
            'joinTable' => 'examination_centres_examinations_subjects_students',
            'foreignKey' => 'examination_item_id',
            'targetForeignKey' => 'student_id',
            'through' => 'Examination.ExaminationCentresExaminationsSubjectsStudents',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->belongsToMany('StudentResults', [
            'className' => 'User.Users',
            'joinTable' => 'examination_item_results',
            'foreignKey' => 'examination_item_id',
            'targetForeignKey' => 'student_id',
            'through' => 'Examination.ExaminationItemResults',
            'dependent' => true,
            'cascadeCallbacks' => true,
            'conditions' => ['OR' => [
                'ExaminationItemResults.marks IS NOT NULL',
                'ExaminationItemResults.examination_grading_option_id IS NOT NULL'
            ]]
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $validator
            ->add('code', 'ruleUniqueCodeWithinForm', [
                'rule' => ['checkUniqueCodeWithinForm', $this->Examinations],
            ])
            ->notEmpty('name')
            ->add('weight', 'ruleIsDecimal', [
                'rule' => ['decimal', null],
            ])
            ->add('weight', 'ruleWeightRange', [
                'rule'  => ['range', 0, 2],
                'last' => true
            ])
            ->notEmpty('education_subject_id')
            ->notEmpty('examination_grading_type_id')
            ->add('examination_date', 'ruleCompareDateReverse', [
                'rule' => ['compareDateReverse', 'registration_end_date', false]
            ])
            ->add('start_time', 'ruleCompareTime', [
                'rule' => ['compareTime', 'end_time', true],
                'provider' => 'table',
            ])
            ->allowEmpty('start_time')
            ->allowEmpty('end_time');
        return $validator;
    }

    public function getExaminationItemSubjects($examinationId)
    {
        $subjectList = $this
            ->find()
            ->contain('EducationSubjects')
            ->select([
                'item_id' => $this->aliasField('id'),
                'item_name' => $this->aliasField('name'),
                'education_subject_id' => $this->aliasField('education_subject_id'),
                'education_subject_name' => $this->EducationSubjects->aliasField('name')
            ])
            ->where([$this->aliasField('examination_id') => $examinationId])
            ->toArray();

        return $subjectList;
    }
}
