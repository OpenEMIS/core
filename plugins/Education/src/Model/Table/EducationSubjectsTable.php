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
}
