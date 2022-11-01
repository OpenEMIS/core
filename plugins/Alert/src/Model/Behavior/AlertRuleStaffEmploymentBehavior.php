<?php
namespace Alert\Model\Behavior;

use ArrayObject;

use Alert\Model\Behavior\AlertRuleBehavior;

use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

class AlertRuleStaffEmploymentBehavior extends AlertRuleBehavior
{
    protected $_defaultConfig = [
        'feature' => 'StaffEmployment',
            'name' => 'Staff Employment',
            'method' => 'Email',
            'threshold' => [
                'value' => [
                    'type' => 'integer',
                    'after' => 'security_roles',
                    'attr' => [
                        'min' => 1,
                        'max' => 365,
                        'required' => true
                    ],
                    'tooltip' => [
                        'label' => 'Value',
                        'sprintf' => [1, 365]
                    ]
                ],
                'condition' => [
                    'type' => 'select',
                    'select' => false,
                    'after' => 'value',
                    'options' => 'StaffEmployment.before_after'
                ],
                'employment_type' => [
                    'type' => 'select',
                    'select' => false,
                    'after' => 'condition',
                    'lookupModel' => 'FieldOption.EmploymentStatusTypes'
                ]
            ],
            'placeholder' => [
                '${threshold.value}' => 'Threshold value.',
                '${employment_type.name}' => 'Employment type.',
                '${employment_date}' => 'Staff employment date.',
                '${employment_period}' => 'Staff employment period.',
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
                        'rule' => ['range', 1, 365],
                        'message' => __('Staff employment must be between 1 to 365')
                    ]
                ]);
            }
        }
    }

    public function onStaffEmploymentSetupFields(Event $event, Entity $entity)
    {
        $this->onAlertRuleSetupFields($event, $entity);
    }

    public function onGetStaffEmploymentThreshold(Event $event, Entity $entity)
    {
        $thresholdData = json_decode($entity->threshold, true);
        return $thresholdData['value'];
    }
}
