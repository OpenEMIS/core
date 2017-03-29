<?php
namespace Alert\Model\Behavior;

use ArrayObject;

use Alert\Model\Behavior\AlertRuleBehavior;

use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

class AlertRuleEmploymentPeriodBehavior extends AlertRuleBehavior
{
	protected $_defaultConfig = [
		'feature' => 'EmploymentPeriod',
            'name' => 'Employment Period',
            'method' => 'Email',
            'threshold' => [
                'value' => [
                    'type' => 'integer',
                    'field' => 'value'
                ],
                'operand_id' => [
                    'type' => 'select',
                    'field' => 'operand',
                    'option' => 'before_after'
                ],
                'staff_type_id' => [
                    'type' => 'select',
                    'field' => 'staff_type',
                    'lookupModel' => 'Staff.StaffTypes'
                ],
            ],
            'placeholder' => [
                '${threshold.value}' => 'Threshold value.',
                '${staff_type.name}' => 'Staff employment type.',
                '${start_date}' => 'Staff start date.',
                '${end_date}' => 'Staff end date.',
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
}
