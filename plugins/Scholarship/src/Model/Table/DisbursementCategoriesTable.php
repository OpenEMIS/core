<?php
namespace Scholarship\Model\Table;

use App\Model\Table\ControllerActionTable;

class DisbursementCategoriesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
    	$this->table('scholarship_disbursement_categories');
        parent::initialize($config);

        $this->hasMany('RecipientDisbursements', ['className' => 'Scholarship.RecipientDisbursements', 'foreignKey' => 'scholarship_disbursement_category_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('RecipientPaymentStructureEstimates', ['className' => 'Scholarship.RecipientPaymentStructureEstimates', 'foreignKey' => 'scholarship_disbursement_category_id', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('FieldOption.FieldOption');
        $this->setDeleteStrategy('restrict');
    }
}
