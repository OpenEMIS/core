<?php
namespace Profile\Model\Table;

use Cake\ORM\Query;
use Scholarship\Model\Table\ScholarshipsTable as BaseTable;

//class ScholarshipsDirectoryTable extends AppTable
class ScholarshipsDirectoryTable extends BaseTable
{
    public function findIndex(Query $query, array $options)
    {
        $querystring = $options['querystring'];

        if (isset($querystring['applicant_id']) && !empty($querystring['applicant_id'])) {
            $applicantId = $querystring['applicant_id'];

            $query->notMatching('Applications', function($q) use ($applicantId) {
                return $q->where(['Applications.applicant_id' => $applicantId]);
            });
        }
        $query->where(['ScholarshipsDirectory.application_close_date >= Date(NOW())']);
        $query->where(['ScholarshipsDirectory.application_open_date <= Date(NOW())']);
        return $query;
    }

    public function findView(Query $query, array $options)
    {
        return $query->contain([
            'FinancialAssistanceTypes',
            'FundingSources',
            'AcademicPeriods',
            'FieldOfStudies',
            'Loans.PaymentFrequencies'
        ]);
    }

}
