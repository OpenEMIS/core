<?php
namespace Scholarship\Model\Table;

use App\Model\Table\AppTable;

class ScholarshipsEducationFieldOfStudiesTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
	
		$this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
		$this->belongsTo('EducationFieldOfStudies', ['className' => 'Education.EducationFieldOfStudies' , 'foreignKey' => 'education_field_of_study_id']);

		$this->addBehavior('CompositeKey');
    }

}
