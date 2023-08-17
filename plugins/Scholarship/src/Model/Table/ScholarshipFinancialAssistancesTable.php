<?php
namespace Scholarship\Model\Table;

use App\Model\Table\ControllerActionTable;

class ScholarshipFinancialAssistancesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
    	$this->setTable('scholarship_financial_assistances');
        parent::initialize($config);
        $this->addBehavior('FieldOption.FieldOption');
    }
}
