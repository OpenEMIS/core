<?php
namespace Scholarship\Model\Table;

use Cake\Validation\Validator;
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

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->requirePresence('scholarship_payment_frequency_id')
            ->allowEmpty('interest_rate')
            ->add('interest_rate', 'validateDecimal', [
                'rule' => ['decimal', null, '/^[0-9]+(\.[0-9]{1,2})?$/'],
                'message' => __('Value cannot be more than two decimal places')
            ]);
    }
}
