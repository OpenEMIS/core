<?php
namespace Education\Model\Table;

use App\Model\Table\ControllerActionTable;

class EducationSubjectsTable extends ControllerActionTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->addBehavior('Education.Setup');
		$this->hasMany('InstitutionSubjects', ['className' => 'Institution.InstitutionSubjects', 'cascadeCallbacks' => true]);
		$this->hasMany('InstitutionSubjectStudents', ['className' => 'Institution.InstitutionSubjectStudents', 'dependent' => true]);
		$this->belongsToMany('EducationGrades', [
			'className' => 'Education.EducationGrades',
			'joinTable' => 'education_grades_subjects',
			'foreignKey' => 'education_subject_id',
			'targetForeignKey' => 'education_grade_id',
			'through' => 'Education.EducationGradesSubjects',
			'dependent' => false
		]);
        $this->behaviors()->get('ControllerAction')->config('actions.remove', 'restrict');
	}
}
