<?php
namespace Scholarship\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class InstitutionChoicesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('scholarship_application_institution_choices');
        parent::initialize($config);

        $this->belongsTo('ScholarshipApplications', ['className' => 'Scholarship.ScholarshipApplications', 'foreignKey' => ['scholarship_id', 'applicant_id']]);
        $this->belongsTo('Applicants', ['className' => 'User.Users', 'foreignKey' => 'applicant_id']);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
        $this->belongsTo('Countries', ['className' => 'FieldOption.Countries', 'foreignKey' => 'country_id']);
        $this->belongsTo('InstitutionChoiceStatuses', ['className' => 'Scholarship.InstitutionChoiceStatuses', 'foreignKey' => 'institution_choice_status_id']);
        $this->belongsTo('EducationFieldOfStudies', ['className' => 'Education.EducationFieldOfStudies' , 'foreignKey' => 'education_field_of_study_id']);
        $this->belongsTo('QualificationLevels', ['className' => 'FieldOption.QualificationLevels',  'foreignKey' =>'level_of_study_id' ]);
    }
}
