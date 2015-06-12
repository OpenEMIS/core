<?php
namespace Education\Model\Table;

use App\Model\Table\AppTable;

class EducationGradesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('EducationProgrammes', ['className' => 'Education.EducationProgrammes']);
		$this->belongsToMany('EducationSubjects', [
			'className' => 'Education.EducationSubjects',
			'joinTable' => 'education_grade_subjects',
			'foreignKey' => 'education_grade_id',
			'targetForeignKey' => 'education_subject_id'
		]);
		// $this->hasMany('Sections', ['className' => 'Institution.Sections']);
	}
}
