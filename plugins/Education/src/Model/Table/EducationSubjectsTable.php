<?php
namespace Education\Model\Table;

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

    public function getEducationSubjectsByGrades($gradeId)
    {
        $subjectOptions = $this
                        ->find('visible')
                        ->select([
                            'education_subject_id' => $this->aliasField('id'),
                            'education_subject_name' => $this->find()->func()->concat([
                                $this->aliasField('code') => 'literal',
                                " - ",
                                $this->aliasField('name') => 'literal'
                            ])
                        ])
                        ->find('list', ['keyField' => 'education_subject_id', 'valueField' => 'education_subject_name'])
                        ->innerJoin(['EducationGradesSubjects' => 'education_grades_subjects'], [
                            'EducationGradesSubjects.education_subject_id = '.$this->aliasField('id'),
                            'EducationGradesSubjects.education_grade_id = '.$gradeId
                        ])
                        ->order([$this->aliasField('order') => 'ASC'])
                        ->toArray();

        return $subjectOptions;
    } 
}