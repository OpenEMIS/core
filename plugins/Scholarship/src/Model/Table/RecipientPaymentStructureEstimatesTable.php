<?php
namespace Scholarship\Model\Table;

use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class RecipientPaymentStructureEstimatesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('scholarship_recipient_payment_structure_estimates');
        parent::initialize($config);

		$this->belongsTo('ScholarshipRecipients', ['className' => 'Scholarship.ScholarshipRecipients', 'foreignKey' => ['recipient_id', 'scholarship_id']]);
		$this->belongsTo('DisbursementCategories', ['className' => 'Scholarship.DisbursementCategories', 'foreignKey' => 'scholarship_disbursement_category_id']);
		$this->belongsTo('RecipientPaymentStructures', ['className' => 'Scholarship.RecipientPaymentStructures', 'foreignKey' => 'scholarship_recipient_payment_structure_id']);
		$this->belongsTo('Recipients', ['className' => 'User.Users', 'foreignKey' => 'recipient_id']);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('estimated_amount', 'validateDecimal', [
                'rule' => ['decimal', null, '/^[0-9]+(\.[0-9]{1,2})?$/'],
                'message' => __('Amount cannot be more than two decimal places')
            ]);
    }

    public function getEstimatedAmount($paymentStructureId = 0)
    {
        $value = 0;
        if(!is_null($paymentStructureId)) {
            $estimatedAmount = $this->find()
                ->where([
                    $this->aliasField('scholarship_recipient_payment_structure_id') => $paymentStructureId
                ])
                ->select([
                        'total' => $this->find()->func()->sum('estimated_amount')
                    ])
                ->first();

            $value = $estimatedAmount->total;
        }
        return $value;
    }

}
