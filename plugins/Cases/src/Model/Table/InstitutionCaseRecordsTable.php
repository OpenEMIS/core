<?php
namespace Cases\Model\Table;

use App\Model\Table\AppTable;

class InstitutionCaseRecordsTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('InstitutionCases', ['className' => 'Cases.InstitutionCases']);

        $this->addBehavior('CompositeKey');
    }
}
