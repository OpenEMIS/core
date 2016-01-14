<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class QualificationSpecialisationsTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->table('qualification_specialisations');
		parent::initialize($config);
		$this->hasMany('Qualifications', ['className' => 'Staff.Qualifications', 'foreignKey' => 'qualification_specialisation_id']);

		$this->belongsToMany('EducationSubjects', [
			'className' => 'Education.EducationSubjects',
			'joinTable' => 'qualification_specialisation_subjects',
			'foreignKey' => 'qualification_specialisation_id',
			'targetForeignKey' => 'education_subject_id',
			'through' => 'Education.QualificationSpecialisationSubjects',
			'dependent' => false
		]);

	}
}
