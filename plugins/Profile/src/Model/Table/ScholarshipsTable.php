<?php
namespace Profile\Model\Table;

use Cake\ORM\Query;
use App\Model\Table\AppTable;

class ScholarshipsTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('FinancialAssistanceTypes', ['className' => 'Scholarship.FinancialAssistanceTypes', 'foreignKey' => 'scholarship_financial_assistance_type_id']);
        $this->belongsTo('FundingSources', ['className' => 'Scholarship.FundingSources', 'foreignKey' => 'scholarship_funding_source_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->hasMany('AttachmentTypes', ['className' => 'Scholarship.AttachmentTypes', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('Applications', ['className' => 'Scholarship.Applications', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ApplicationInstitutionChoices', ['className' => 'Scholarship.ApplicationInstitutionChoices', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->belongsToMany('FieldOfStudies', [
            'className' => 'Education.EducationFieldOfStudies',
            'joinTable' => 'scholarships_field_of_studies',
            'foreignKey' => 'scholarship_id',
            'targetForeignKey' => 'education_field_of_study_id',
            'through' => 'Scholarship.ScholarshipsFieldOfStudies',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
    }

    public function findIndex(Query $query, array $options)
    {
        $querystring = $options['querystring'];

        if (array_key_exists('applicant_id', $querystring) && !empty($querystring['applicant_id'])) {
            $applicantId = $querystring['applicant_id'];
            $query->notMatching('ScholarshipApplications', function($q) use ($applicantId) {
                return $q->where(['ScholarshipApplications.applicant_id' => $applicantId]);
            });
        }
        return $query;
    }
}
