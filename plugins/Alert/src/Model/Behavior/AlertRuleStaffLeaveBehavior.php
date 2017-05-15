<?php
namespace Alert\Model\Behavior;

use ArrayObject;

use Alert\Model\Behavior\AlertRuleBehavior;

use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

class AlertRuleStaffLeaveBehavior extends AlertRuleBehavior
{
    protected $_defaultConfig = [
        'feature' => 'StaffLeave',
            'name' => 'Staff Leave',
            'method' => 'Email',
            'threshold' => [
                'value' => [
                    'type' => 'integer',
                    'after' => 'security_roles',
                    'attr' => [
                        'min' => 1,
                        'max' => 30,
                        'required' => true
                    ],
                    'tooltip' => [
                        'label' => 'Value',
                        'sprintf' => [1, 30]
                    ]
                ],
                'condition' => [
                    'type' => 'select',
                    'select' => false,
                    'after' => 'value',
                    'options' => 'StaffLeave.before_after'
                ],
                'staff_leave_type' => [
                    'type' => 'select',
                    'select' => false,
                    'after' => 'condition',
                    'lookupModel' => 'Staff.StaffLeaveTypes'
                ]
            ],
            'placeholder' => [
                '${threshold.value}' => 'Threshold value.',
                '${staff_leave_type.name}' => 'License type.',
                '${date_from}' => 'Leave start date.',
                '${date_to}' => 'Leave end date.',
                '${day_difference}' => 'Days difference between today and leave end date.',
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
                        'rule' => ['range', 1, 30],
                        'message' => __('Value must be within 1 to 30')
                    ]
                ]);
            }
        }
    }

    public function onStaffLeaveSetupFields(Event $event, Entity $entity)
    {
        $this->onAlertRuleSetupFields($event, $entity);
    }

    public function onGetStaffLeaveThreshold(Event $event, Entity $entity)
    {
        $thresholdData = json_decode($entity->threshold, true);
        return $thresholdData['value'];
    }
}
