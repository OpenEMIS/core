<?php
namespace Alert\Model\Behavior;

use ArrayObject;

use Alert\Model\Behavior\AlertRuleBehavior;

use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

class AlertRuleScholarshipDisbursementBehavior extends AlertRuleBehavior
{
    protected $_defaultConfig = [
        'feature' => 'ScholarshipDisbursement',
        'name' => 'Scholarship Disbursement',
        'method' => 'Email',
        'threshold' => [
            'value' => [
                'type' => 'integer',
                'after' => 'security_roles',
                'attr' => [
                    'min' => 1,
                    'max' => 120,
                    'required' => true
                ],
                'tooltip' => [
                    'label' => 'Value',
                    'sprintf' => [1, 120]
                ]
            ],
            'condition' => [
                'type' => 'select',
                'select' => false,
                'after' => 'value',
                'options' => 'ScholarshipDisbursement.before_after',
                'attr' => [
                    'required' => true
                ]
            ]
        ],
        'placeholder' => [
            '${threshold.value}' => 'Threshold value.',
            '${day_difference}' => 'Days difference between today and expiry date.',
            '${estimated_disbursement_date}'=> 'Estimated disbursement date.',
            '${estimated_amount}'=> 'Estimated amount.',
            '${comments}'=> 'Scholarship application comments.',
            '${recipient_payment_structure.code}'=> 'Scholarship recipient payment structure name.',
            '${recipient_payment_structure.name}'=> 'Scholarship recipient payment structure code.',
            '${disbursement_category.name}'=> 'Scholarship disbursement category name.',
            '${recipient.first_name}'=> 'Recipient first name.',
            '${recipient.middle_name}'=> 'Recipient middle name.',
            '${recipient.third_name}'=> 'Recipient third name.',
            '${recipient.last_name}'=> 'Recipient last name.',
            '${recipient.preferred_name}'=> 'Recipient preferred name.',
            '${recipient.email}'=> 'Recipient email.',
            '${recipient.address}'=> 'Recipient address.',
            '${recipient.postal_code}'=> 'Recipient postal code.',
            '${recipient.date_of_birth}'=> 'Recipient date of birth.',
            '${scholarship.code}'=> 'Scholarship code.',
            '${scholarship.name}'=> 'Scholarship name.',
            '${scholarship.description}'=> 'Scholarship description.',
            '${scholarship.application_open_date}'=> 'Scholarship application_open_date.',
            '${scholarship.application_close_date}'=> 'Scholarship application_close_date.',
            '${scholarship.maximum_award_amount}'=> 'Scholarship maximum_award_amount.',
            '${scholarship.total_amount}'=> 'Scholarship total_amount.',
            '${scholarship.duration}'=> 'Scholarship duration.',
            '${scholarship.bond}' => 'Scholarship bond.'
        ]
    ];

    public function initialize(array $config)
    {
        parent::initialize($config);
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        $model = $this->_table;
        if (isset($data['feature']) && !empty($data['feature']) && $data['feature'] == $this->alertRule) {
            if (isset($data['submit']) && $data['submit'] == 'save') {
                $validator = $model->validator();
                $validator->add('value', [
                    'ruleRange' => [
                        'rule' => ['range', 1, 120],
                        'message' => __('Value must be within 1 to 120')
                    ]
                ]);
            }
        }
    }

    public function onScholarshipDisbursementSetupFields(Event $event, Entity $entity)
    {
        $this->onAlertRuleSetupFields($event, $entity);   
    }

    public function onGetScholarshipDisbursementThreshold(Event $event, Entity $entity)
    {
        $thresholdData = json_decode($entity->threshold, true);
        return $thresholdData['value'];
    }
}
