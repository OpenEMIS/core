<?php
namespace Education\Model\Table;

use App\Model\Table\AppTable;

class EducationSubjectsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->addBehavior('Education.Setup');
		$this->hasMany('InstitutionSiteClasses', ['className' => 'Institution.InstitutionSiteClasses', 'cascadeCallbacks' => true]);
		$this->belongsToMany('EducationGrades', [
			'className' => 'Education.EducationGrades',
			'joinTable' => 'education_grades_subjects',
			'foreignKey' => 'education_subject_id',
			'targetForeignKey' => 'education_grade_id',
			'through' => 'Education.EducationGradesSubjects',
			'dependent' => false;
		]);
	}
}
