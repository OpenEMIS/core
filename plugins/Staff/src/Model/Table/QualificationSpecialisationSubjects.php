<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;

class QualificationSpecialisationSubjectsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('QualificationSpecialisations', ['className' => 'FieldOption.QualificationSpecialisations']);
		$this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
	}
}