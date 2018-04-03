<?php
namespace Scholarship\Model\Table;

use App\Model\Table\ControllerActionTable;

class FundingSourcesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->hasMany('Scholarships', ['className' => 'Scholarship.Scholarships']);
        $this->addBehavior('FieldOption.FieldOption');
    }
}
