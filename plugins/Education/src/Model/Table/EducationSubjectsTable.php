<?php
namespace Education\Model\Table;

use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class EducationSubjectsTable extends ControllerActionTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->addBehavior('Education.Setup');
		$this->hasMany('InstitutionSubjects',			['className' => 'Institution.InstitutionSubjects', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('InstitutionSubjectStudents',	['className' => 'Institution.InstitutionSubjectStudents', 'dependent' => true, 'dependent' => true]);
		$this->belongsToMany('EducationGrades', [
			'className' => 'Education.EducationGrades',
			'joinTable' => 'education_grades_subjects',
			'foreignKey' => 'education_subject_id',
			'targetForeignKey' => 'education_grade_id',
			'through' => 'Education.EducationGradesSubjects',
			'dependent' => true
		]);
        $this->setDeleteStrategy('restrict');
	}

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->add('code', 'ruleUniqueCode', [
                'rule' => ['checkUniqueCode', null]
            ]);
        return $validator;
    }

    public function getEducationSubjectsByGrades($gradeId)
    {
        if ($gradeId) {
            $subjectOptions = $this
                        ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                        ->find('visible')
                        ->innerJoin(['EducationGradesSubjects' => 'education_grades_subjects'], [
                            'EducationGradesSubjects.education_subject_id = '.$this->aliasField('id'),
                            'EducationGradesSubjects.education_grade_id' => $gradeId
                        ])
                        ->order([$this->aliasField('order') => 'ASC'])
                        ->toArray();
        
            return $subjectOptions;
        }
    }
}