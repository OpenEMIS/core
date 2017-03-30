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
                'field' => 'value'
            ],
            'operand_id' => [
                'type' => 'select',
                'field' => 'operand',
                'option' => 'before_after_age'
            ],
            // 'staff_leave_type_id' => [
            //     'type' => 'select',
            //     'field' => 'staff_leave_type',
            //     'lookupModel' => 'Staff.StaffLeaveTypes'
            // ],
        ],
        'placeholder' => [
            '${threshold.value}' => 'Threshold value.',
            '${age}' => 'Age value.',
            '${openemis_no}' => 'Student OpenEMIS number.',
            '${first_name}' => 'Student first name.',
            '${middle_name}' => 'Student middle name.',
            '${third_name}' => 'Student third name.',
            '${last_name}' => 'Student last name.',
            '${preferred_name}' => 'Student preferred name.',
            '${email}' => 'Student email.',
            '${address}' => 'Student address.',
            '${postal_code}' => 'Student postal code.',
            '${date_of_birth}' => 'Student date of birth.',
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

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['AlertRule.setupFields'] = 'onAlertRuleSetupFields';
        $events['AlertRule.UpdateField.'.$this->alertRule.'.Threshold'] = 'onUpdateFieldRetirementWarningThreshold';
        $events['AlertRule.onGet.'.$this->alertRule.'.Threshold'] = 'onGetRetirementWarningThreshold';
        return $events;
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        $model = $this->_table;
        if (isset($data['submit']) && $data['submit'] == 'save') {
            if (isset($data['feature']) && !empty($data['feature']) && $data['feature'] == $this->alertRule) {
                $validator = $model->validator();
                $validator->add('value', [
                    'ruleRange' => [
                        'rule' => ['range', 49, 99]
                    ]
                ]);

                $thresholdArray = [];
                if (isset($data['value'])) {
                    $thresholdArray['value'] = $data['value'];
                }
                if (isset($data['operand'])) {
                    $thresholdArray['operand'] = $data['operand'];
                }
                // if (isset($data['license_type'])) {
                //     $thresholdArray['license_type'] = $data['license_type'];
                // }
                $data['threshold'] = json_encode($thresholdArray, JSON_UNESCAPED_UNICODE);
            }
        }
    }

    public function onAlertRuleSetupFields(Event $event, Entity $entity)
    {
        // pr($this->_table->action);
        // pr($entity->feature);
        // pr($this->alertRule);
        // pr($entity->threshold);
        // die;

        if ($entity->has('feature') && $entity->feature == $this->alertRule) {
            // pr('if');
            $thresholdArray=[];
            if (!empty($entity->threshold)) {
                $thresholdArray = json_decode($entity->threshold, true);
            }
            $model = $this->_table;

            // value field
            $model->field('value', [
                'type' => 'integer',
                'after' => 'security_roles',
                'attr' => [
                    'min' => 49,
                    'max' => 99,
                ]
            ]);
            // end

            // operand field
            $operandOptions = $model->getSelectOptions($model->aliasField('before_after_age'));
            $model->field('operand', [
                'type' => 'select',
                'select' => false,
                'after' => 'value',
                'options' => $operandOptions
            ]);
            // end

            // // license_type field
            // $LicenseTypes = TableRegistry::get('FieldOption.LicenseTypes');
            // $licenseTypesOptions = $LicenseTypes
            //     ->find('list')
            //     ->find('visible')
            //     ->find('order')
            //     ->toArray();
            // $model->field('license_type', [
            //     'type' => 'select',
            //     'select' => false,
            //     'after' => 'operand',
            //     'options' => $licenseTypesOptions
            // ]);
            // // end
        }
    }

    public function onGetRetirementWarningThreshold(Event $event, Entity $entity)
    {
        $thresholdData = json_decode($entity->threshold, true);
        return $thresholdData['value'];
    }

    public function onUpdateFieldRetirementWarningThreshold(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'hidden';
            $attr['value'] = '';
        }

        return $attr;
    }
}
