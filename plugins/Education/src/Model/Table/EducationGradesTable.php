<?php
namespace Education\Model\Table;

use App\Model\Table\AppTable;

class EducationGradesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('EducationProgrammes', ['className' => 'Education.EducationProgrammes']);

		// todo:mlee need to put in this association when it is created
		// $this->hasMany('EducationGradeSubject', ['className' => 'Education.EducationGradeSubject']);

		$this->belongsToMany('EducationSubjects', [
			'className' => 'Education.EducationSubjects',
			'joinTable' => 'education_grades_subjects',
			'foreignKey' => 'education_grade_id',
			'targetForeignKey' => 'education_subject_id'
		]);
		// $this->hasMany('Sections', ['className' => 'Institution.Sections']);
	}
}
