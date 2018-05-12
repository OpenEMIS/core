<?php
namespace Scholarship\Model\Table;

use App\Model\Table\ControllerActionTable;

class FundingSourcesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('scholarship_funding_sources');
        parent::initialize($config);

        $this->hasMany('Scholarships', ['className' => 'Scholarship.Scholarships', 'foreignKey' => 'scholarship_funding_source_id', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('FieldOption.FieldOption');
        $this->setDeleteStrategy('restrict');
    }
}
