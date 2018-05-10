<?php
namespace Scholarship\Model\Table;

use App\Model\Table\AppTable;

class LoansTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('scholarship_loans');
        parent::initialize($config);

        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
        $this->belongsTo('PaymentFrequencies', ['className' => 'Scholarship.PaymentFrequencies', 'foreignKey' => 'scholarship_payment_frequency_id']);
    }
}
