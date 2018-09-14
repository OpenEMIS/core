<?php
namespace Alert\Model\Behavior;

use ArrayObject;

use Alert\Model\Behavior\AlertRuleBehavior;

use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

class AlertRuleScholarshipApplicationBehavior extends AlertRuleBehavior
{
    protected $_defaultConfig = [
        'feature' => 'ScholarshipApplication',
        'name' => 'Scholarship Application',
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
                'options' => 'ScholarshipApplication.before_after',
                'attr' => [
                    'required' => true
                ]
            ],
            'category' => [
                'type' => 'select',
                'select' => false,
                'after' => 'condition',
                'options' => 'ScholarshipApplication.workflow_category',
                'attr' => [
                    'required' => true
                ]
            ],
        ],
        'placeholder' => [
            '${threshold.value}' => 'Threshold value.',
            '${day_difference}' => 'Days difference between today and expiry date.',
            '${requested_amount}' => 'Scholarship application requested amount',
            '${comments}' => 'Scholarship application comments.',
            '${applicant.first_name}' => 'Applicant first name.',
            '${applicant.middle_name}' => 'Applicant middle name.',
            '${applicant.third_name}' => 'Applicant third name.',
            '${applicant.last_name}' => 'Applicant last name.',
            '${applicant.preferred_name}' => 'Applicant preferred name.',
            '${applicant.email}' => 'Applicant email.',
            '${applicant.address}' => 'Applicant address.',
            '${applicant.postal_code}' => 'Applicant postal code.',
            '${applicant.date_of_birth}' => 'Applicant date of birth.',
            '${scholarship.code}' => 'Scholarship code.',
            '${scholarship.name}' => 'Scholarship name.',
            '${scholarship.description}' => 'Scholarship description.',
            '${scholarship.application_open_date}' => 'Scholarship application open date',
            '${scholarship.application_close_date}' => 'Scholarship application close date',
            '${scholarship.maximum_award_amount}' => 'Scholarship annual award amount',
            '${scholarship.total_amount}' => 'Scholarship total award amount',
            '${scholarship.duration}' => 'Scholarship duration (years)',
            '${scholarship.bond}' => 'Scholarship bond (years)',
            '${assignee.first_name}' => 'Assignee first name.',
            '${assignee.middle_name}' => 'Assignee middle name.',
            '${assignee.third_name}' => 'Assignee third name.',
            '${assignee.last_name}' => 'Assignee last name.',
            '${assignee.preferred_name}' => 'Assignee preferred name.'
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

    public function onScholarshipApplicationSetupFields(Event $event, Entity $entity)
    {
        $this->onAlertRuleSetupFields($event, $entity);   
    }

    public function onGetScholarshipApplicationThreshold(Event $event, Entity $entity)
    {
        $thresholdData = json_decode($entity->threshold, true);
        return $thresholdData['value'];
    }
}
