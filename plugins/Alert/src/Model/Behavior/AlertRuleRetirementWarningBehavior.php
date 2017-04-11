<?php
namespace Alert\Model\Behavior;

use ArrayObject;

use Alert\Model\Behavior\AlertRuleBehavior;

use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

class AlertRuleRetirementWarningBehavior extends AlertRuleBehavior
{
    protected $_defaultConfig = [
        'feature' => 'RetirementWarning',
        'name' => 'Retirement Warning',
        'method' => 'Email',
        'threshold' => [
            'value' => [
                'type' => 'integer',
                'after' => 'security_roles',
                'attr' => [
                    'min' => 50,
                    'max' => 75,
                    'required' => true
                ],
                'tooltip' => [
                    'label' => 'Value',
                    'sprintf' => [50, 75]
                ]
            ],
            'condition' => [
                'type' => 'select',
                'select' => false,
                'after' => 'value',
                'options' => 'RetirementWarning.before_after'
            ]
        ],
        'placeholder' => [
            '${threshold.value}' => 'Threshold value.',
            '${age}' => 'Age value.',
            '${openemis_no}' => 'OpenEMIS ID.',
            '${first_name}' => 'First name.',
            '${middle_name}' => 'Middle name.',
            '${third_name}' => 'Third name.',
            '${last_name}' => 'Last name.',
            '${preferred_name}' => 'Preferred name.',
            '${email}' => 'Email.',
            '${address}' => 'Address.',
            '${postal_code}' => 'Postal code.',
            '${date_of_birth}' => 'Date of birth.',
            '${institution.name}' => 'Institution name.',
            '${institution.code}' => 'Institution code.',
            '${institution.address}' => 'Institution address.',
            '${institution.postal_code}' => 'Institution postal code.',
            '${institution.contact_person}' => 'Institution contact person.',
            '${institution.telephone}' => 'Institution telephone number.',
            '${institution.fax}' => 'Institution fax number.',
            '${institution.email}' => 'Institution email.',
            '${institution.website}' => 'Institution website.',
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
                        'rule' => ['range', 50, 75],
                        'message' => __('Retirement age must be between 50 to 75')
                    ]
                ]);
            }
        }
    }

    public function onRetirementWarningSetupFields(Event $event, Entity $entity)
    {
        $this->onAlertRuleSetupFields($event, $entity);
    }

    public function onGetRetirementWarningThreshold(Event $event, Entity $entity)
    {
        $thresholdData = json_decode($entity->threshold, true);
        return $thresholdData['value'];
    }
}
