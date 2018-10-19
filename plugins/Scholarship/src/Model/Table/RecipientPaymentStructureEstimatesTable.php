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

    public function getModelAlertData($threshold)
    {
        $thresholdArray = json_decode($threshold, true);
        $conditionKey = $thresholdArray['condition'];
        $thresholdDay = $thresholdArray['value'];

        // 1 - Days before disbursement date
        // 2 - Days after disbursement date
        $sqlConditions = [
            1 => ('DATEDIFF(' . $this->aliasField('estimated_disbursement_date') . ', NOW())' . ' BETWEEN 0 AND ' . $thresholdDay),
            2 => ('DATEDIFF(NOW(), ' . $this->aliasField('estimated_disbursement_date') . ')' . ' BETWEEN 0 AND ' . $thresholdDay)
        ];

        $record = [];
        if (array_key_exists($conditionKey, $sqlConditions)) {
            $record = $this
                ->find()
                ->select([
                    $this->aliasField('estimated_disbursement_date'),
                    $this->aliasField('estimated_amount'),
                    $this->aliasField('comments')
                ])
                ->contain([
                    'DisbursementCategories' => [
                        'fields' => [
                            'DisbursementCategories.name'
                        ]
                    ],
                    'RecipientPaymentStructures' => [
                        'fields' => [
                            'RecipientPaymentStructures.code',
                            'RecipientPaymentStructures.name'
                        ]
                    ],
                    'Recipients' => [
                        'fields' => [
                            'Recipients.first_name',
                            'Recipients.middle_name',
                            'Recipients.third_name',
                            'Recipients.last_name',
                            'Recipients.preferred_name',
                            'Recipients.email',
                            'Recipients.address',
                            'Recipients.postal_code',
                            'Recipients.date_of_birth'
                        ]
                    ],
                    'Scholarships' => [
                        'fields' => [
                            'Scholarships.code',
                            'Scholarships.name',
                            'Scholarships.description',
                            'Scholarships.application_close_date',
                            'Scholarships.application_open_date',
                            'Scholarships.maximum_award_amount',
                            'Scholarships.total_amount',
                            'Scholarships.duration',
                            'Scholarships.bond'
                        ]
                    ]
                ])
                ->where([$sqlConditions[$conditionKey]])
                ->hydrate(false)
                ->toArray();
        }

        return $record;
    }
}
