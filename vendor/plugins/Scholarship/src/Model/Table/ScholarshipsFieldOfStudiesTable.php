<?php
namespace Scholarship\Model\Table;

use App\Model\Table\AppTable;

class ScholarshipsFieldOfStudiesTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
        $this->belongsTo('EducationFieldOfStudies', ['className' => 'Education.EducationFieldOfStudies']);
    }
}
