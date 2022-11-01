<?php
namespace Education\Model\Table;

use App\Model\Table\AppTable;

class EducationSubjectsFieldOfStudiesTable extends AppTable
{
	public function initialize(array $config)
	{
		parent::initialize($config);
		$this->belongsTo('EducationFieldOfStudies', ['className' => 'Education.EducationFieldOfStudies']);
		$this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);

		$this->addBehavior('CompositeKey');
	}
}
