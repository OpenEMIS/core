<?php
namespace Alert\Model\Behavior;

use ArrayObject;

use Alert\Model\Behavior\AlertRuleBehavior;

use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\EventInterface;

class AlertRuleRetirementWarningBehavior extends AlertRuleBehavior
{
    protected $_defaultConfig = [
        'feature' => 'RetirementWarning',
        'name' => 'Retirement Warning',
        'method' => ['Email','SMS'], // POCOR-8286
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
            //POCOR-9509: dot notation (user.*) to match Laravel AlertRetirementWarningCommand
            '${user.openemis_no}' => 'OpenEMIS ID.',
            '${user.first_name}' => 'First name.',
            '${user.middle_name}' => 'Middle name.',
            '${user.third_name}' => 'Third name.',
            '${user.last_name}' => 'Last name.',
            '${user.preferred_name}' => 'Preferred name.',
            '${user.email}' => 'Email.',
            '${user.address}' => 'Address.',
            '${user.postal_code}' => 'Postal code.',
            '${user.date_of_birth}' => 'Date of birth.',
            '${institution.name}' => 'Institution name.',
            '${institution.code}' => 'Institution code.',
            '${institution.address}' => 'Institution address.',
            '${institution.postal_code}' => 'Institution postal code.',
            '${institution.contact_person}' => 'Institution contact person.',
            '${institution.telephone}' => 'Institution telephone number.',
//            '${institution.fax}' => 'Institution fax number.',
            '${institution.email}' => 'Institution email.',
            '${institution.website}' => 'Institution website.',
        ]
    ];

    public function initialize(array $config): void
    {
        parent::initialize($config);
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        $model = $this->_table;
        if (isset($data['feature']) && !empty($data['feature']) && $data['feature'] == $this->alertRule) {
            if (isset($data['submit']) && $data['submit'] == 'save') {
                $validator = $model->getValidator('default');//POCOR-8341
                $validator->add('value', [
                    'ruleRange' => [
                        'rule' => ['range', 50, 75],
                        'message' => __('Retirement age must be between 50 to 75')
                    ]
                ]);
            }
        }
    }

    public function onRetirementWarningSetupFields(EventInterface $event, Entity $entity)
    {
        $this->onAlertRuleSetupFields($event, $entity);
    }

    public function onGetRetirementWarningThreshold(EventInterface $event, Entity $entity)
    {
        $thresholdData = json_decode($entity->threshold, true);
        return $thresholdData['value'];
    }
}
