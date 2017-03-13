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
        $this->belongsToMany('ExaminationCentres', [
            'className' => 'Examination.ExaminationCentres',
            'joinTable' => 'examination_centre_subjects',
            'foreignKey' => 'examination_item_id',
            'targetForeignKey' => 'examination_centre_id',
            'through' => 'Examination.ExaminationCentreSubjects',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->belongsToMany('Students', [
            'className' => 'User.Users',
            'joinTable' => 'examination_centre_students',
            'foreignKey' => 'examination_item_id',
            'targetForeignKey' => 'student_id',
            'through' => 'Examination.ExaminationCentreStudents',
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

        $this->addBehavior('Restful.RestfulAccessControl', [
            'ExamResults' => ['index']
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
            ->allowEmpty('education_subject_id')
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
            ->find('list', [
                    'keyField' => 'subject_id',
                    'valueField' => 'subject_name'
            ])
            ->matching('EducationSubjects')
            ->select([
                'subject_name' => 'EducationSubjects.name',
                'subject_id' => $this->aliasField('education_subject_id')
            ])
            ->where([$this->aliasField('examination_id') => $examinationId])
            ->toArray();
        return $subjectList;
    }
}