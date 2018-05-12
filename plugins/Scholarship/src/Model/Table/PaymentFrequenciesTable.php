<?php
namespace Scholarship\Model\Table;

use App\Model\Table\ControllerActionTable;

class PaymentFrequenciesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('scholarship_payment_frequencies');
        parent::initialize($config);

        $this->hasMany('Loans', ['className' => 'Scholarship.Loans', 'foreignKey' => 'scholarship_payment_frequency_id', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('FieldOption.FieldOption');
        $this->setDeleteStrategy('restrict');
    }
}
