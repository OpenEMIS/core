<?php
namespace Examination\Model\Table;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\ORM\Query;

use App\Model\Table\AppTable;

class ExaminationItemsTable extends AppTable {

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Examination', ['className' => 'Examination.Examinations']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);

    }

    // public function validationDefault(Validator $validator)
    // {
    //     $validator = parent::validationDefault($validator);

    //     $validator
    //         ->add('weight', 'ruleIsDecimal', [
    //             'rule' => ['decimal', null],
    //         ])
    //         ->add('weight', 'ruleWeightRange', [
    //             'rule'  => ['range', 0, 2],
    //             'last' => true
    //         ]);
    //     return $validator;
    // }

    public function populateExaminationItemsArray($gradeId)
    {
        $EducationGradesSubjects = TableRegistry::get('Education.EducationGradesSubjects');
        $gradeSubjects = $EducationGradesSubjects->find()
            ->contain('EducationSubjects')
            ->where([$EducationGradesSubjects->aliasField('education_grade_id') => $gradeId])
            ->order(['order'])
            ->toArray();

        $examinationItems = [];
        foreach ($gradeSubjects as $key => $gradeSubject) {
            if (!empty($gradeSubject->education_subject)) {
                $examinationItems[] = [
                    'education_subject_id' => $gradeSubject->education_subject->id,
                    'education_subject_code' => $gradeSubject->education_subject->code,
                    'education_subject_name' => $gradeSubject->education_subject->name,
                    'weight' => '0.00',
                ];
            }
        }

        return $examinationItems;
    }
}