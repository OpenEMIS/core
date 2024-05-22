<?php

namespace Cases\Model\Table;
//POCOR-7613
use App\Model\Table\AppTable;

class InstitutionCaseCommentsTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('InstitutionCases', ['className' => 'Cases.InstitutionCases']);
    }
}
