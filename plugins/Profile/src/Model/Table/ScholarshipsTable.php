<?php
namespace Profile\Model\Table;

use Cake\ORM\Query;
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
