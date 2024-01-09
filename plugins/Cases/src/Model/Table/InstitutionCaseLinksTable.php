<?php

namespace Cases\Model\Table;
use App\Model\Table\AppTable;

class InstitutionCaseLinksTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->belongsTo('InstitutionCases', ['className' => 'Cases.InstitutionCases']);
    }
}
