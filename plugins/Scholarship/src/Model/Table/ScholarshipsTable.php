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
        $this->belongsTo('FundingSources', ['className' => 'Scholarship.FundingSources', 'foreignKey' => 'scholarship_funding_source_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->hasMany('ScholarshipAttachmentTypes', ['className' => 'Scholarship.ScholarshipAttachmentTypes', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ScholarshipApplications', ['className' => 'Scholarship.ScholarshipApplications', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionChoices', ['className' => 'Scholarship.InstitutionChoices', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ApplicationAttachments', ['className' => 'Scholarship.ApplicationAttachments', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->belongsToMany('EducationFieldOfStudies', [
                    'className' => 'Education.EducationFieldOfStudies',
                    'joinTable' => 'scholarships_field_of_studies',
                    'foreignKey' => 'scholarship_id', 
                    'targetForeignKey' => 'education_field_of_study_id',
                    'through' => 'Scholarship.ScholarshipsFieldOfStudies',
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

    public function getAvailableScholarships($options = [])
    {
        $list = [];
        $applicantId = array_key_exists('applicant_id', $options) ? $options['applicant_id'] : '';
        $financialTypeId = array_key_exists('financial_type_id', $options) ? $options['financial_type_id'] : '';

        if ($applicantId && $financialTypeId) {
            $list = $this->find('list')
                ->notMatching('ScholarshipApplications', function ($q) use ($applicantId) {
                        return $q->where([
                            'ScholarshipApplications.applicant_id' => $applicantId
                        ]);
                     })
                ->where([$this->aliasField('financial_assistance_type_id') => $financialTypeId])
                ->toArray();
        } 
        return $list;
    }
}
