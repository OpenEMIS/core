<?php
namespace Profile\Model\Table;

use Cake\ORM\Query;
use Scholarship\Model\Table\ScholarshipsTable as BaseTable;

class ScholarshipsTable extends BaseTable
{
    public function findIndex(Query $query, array $options)
    {
        $querystring = $options['querystring'];

        if (array_key_exists('applicant_id', $querystring) && !empty($querystring['applicant_id'])) {
            $applicantId = $querystring['applicant_id'];
            $query->notMatching('Applications', function($q) use ($applicantId) {
                return $q->where(['Applications.applicant_id' => $applicantId]);
            });
        }
        return $query;
    }
}
