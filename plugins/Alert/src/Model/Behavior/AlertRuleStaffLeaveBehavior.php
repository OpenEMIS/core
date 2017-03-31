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
                    ]
                ],
                'operand' => [
                    'type' => 'select',
                    'select' => false,
                    'after' => 'value',
                    'options' => 'before_after_leave'
                ],
                'staff_leave_type' => [
                    'type' => 'select',
                    'select' => false,
                    'after' => 'operand',
                    'lookupModel' => 'Staff.StaffLeaveTypes'
                ]
            ],
            'placeholder' => [
                '${threshold.value}' => 'Threshold value.',
                '${staff_leave_type.name}' => 'License type.',
                '${date_from}' => 'Leave start date.',
                '${date_to}' => 'Leave end date.',
                '${user.openemis_no}' => 'Student OpenEMIS number.',
                '${user.first_name}' => 'Student first name.',
                '${user.middle_name}' => 'Student middle name.',
                '${user.third_name}' => 'Student third name.',
                '${user.last_name}' => 'Student last name.',
                '${user.preferred_name}' => 'Student preferred name.',
                '${user.email}' => 'Student email.',
                '${user.address}' => 'Student address.',
                '${user.postal_code}' => 'Student postal code.',
                '${user.date_of_birth}' => 'Student date of birth.',
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
