<?php
namespace Scholarship\Model\Table;

use Cake\ORM\Query;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class ScholarshipsTable extends AppTable
{
    public function initialize(array $config)
    {
      
        parent::initialize($config);

        $this->belongsTo('FinancialAssistanceTypes', ['className' => 'Scholarship.FinancialAssistanceTypes']);
        $this->belongsTo('FundingSources', ['className' => 'Scholarship.FundingSources']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->hasMany('ScholarshipAttachmentTypes', ['className' => 'Scholarship.ScholarshipAttachmentTypes', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ScholarshipApplications', ['className' => 'Scholarship.ScholarshipApplications', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionChoices', ['className' => 'Scholarship.InstitutionChoices', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->belongsToMany('EducationFieldOfStudies', [
                    'className' => 'Education.EducationFieldOfStudies',
                    'joinTable' => 'scholarships_education_field_of_studies',
                    'foreignKey' => 'scholarship_id', 
                    'targetForeignKey' => 'education_field_of_study_id',
                    'through' => 'Scholarship.ScholarshipsEducationFieldOfStudies',
                    'dependent' => true,
                    'cascadeCallbacks' => true
                ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator;
    }

    public function findView(Query $query, array $options)
    {
        $query->contain(['EducationFieldOfStudies']);
        return $query;
    }

    public function findEdit(Query $query, array $options)
    {
        $query->contain(['EducationFieldOfStudies']);
        return $query;
    }
}
