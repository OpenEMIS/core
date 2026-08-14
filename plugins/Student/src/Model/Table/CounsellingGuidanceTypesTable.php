<?php
namespace Student\Model\Table;

use App\Model\Table\AppTable;

class CounsellingGuidanceTypesTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->belongsTo('Counsellings', ['className' => 'Student.Counsellings']);
        $this->belongsTo('GuidanceTypes', ['className' => 'Student.GuidanceTypes']);
    }
}
