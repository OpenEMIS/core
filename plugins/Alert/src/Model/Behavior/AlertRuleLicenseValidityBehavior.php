<?php
namespace Alert\Model\Behavior;

use ArrayObject;

use Alert\Model\Behavior\AlertRuleBehavior;

use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

class AlertRuleLicenseValidityBehavior extends AlertRuleBehavior
{
	protected $_defaultConfig = [
		'feature' => 'LicenseValidity',
        'name' => 'License Validity',
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
            'license_type_id' => [
                'type' => 'select',
                'field' => 'license_type',
                'lookupModel' => 'FieldOption.LicenseTypes'
            ],
        ],
        'placeholder' => [
            '${threshold.value}' => 'Threshold value.',
            '${license_type.name}' => 'License type.',
            '${license_number}' => 'License number.',
            '${issue_date}' => 'Issue date.',
            '${expiry_date}' => 'Expiry date.',
            '${issuer}' => 'Issuer.',
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

	public function implementedEvents()
	{
		$events = parent::implementedEvents();
		$events['AlertRule.setupFields'] = 'onAlertRuleSetupFields';
		$events['AlertRule.UpdateField.'.$this->alertRule.'.Threshold'] = 'onUpdateFieldLicenseValidityThreshold';
		$events['AlertRule.onGet.'.$this->alertRule.'.Threshold'] = 'onGetLicenseValidityThreshold';
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
						'rule' => ['range', 1, 30]
					]
				]);

				$thresholdArray = [];
				if (isset($data['value'])) {
					$thresholdArray['value'] = $data['value'];
				}
				if (isset($data['operand'])) {
					$thresholdArray['operand'] = $data['operand'];
				}
				if (isset($data['license_type'])) {
					$thresholdArray['license_type'] = $data['license_type'];
				}
				$data['threshold'] = json_encode($thresholdArray, JSON_UNESCAPED_UNICODE);
	    	}
	    }
    }

	public function onAlertRuleSetupFields(Event $event, Entity $entity)
	{
		if ($entity->has('feature') && $entity->feature == $this->alertRule) {
			$model = $this->_table;

			// value field
			$model->field('value', [
				'type' => 'integer',
				'after' => 'security_roles',
				'attr' => [
					'min' => 1,
					'max' => 30,
				]
			]);
			// end

			// operand field
			$operandOptions = $model->getSelectOptions($model->aliasField('before_after'));
	        $model->field('operand', [
	        	'type' => 'select',
	        	'select' => false,
	        	'after' => 'value',
	        	'options' => $operandOptions
	        ]);
	        // end

	        // license_type field
			$LicenseTypes = TableRegistry::get('FieldOption.LicenseTypes');
			$licenseTypesOptions = $LicenseTypes
				->find('list')
				->find('visible')
				->find('order')
				->toArray();
	        $model->field('license_type', [
	        	'type' => 'select',
	        	'select' => false,
	        	'after' => 'operand',
				'options' => $licenseTypesOptions
	        ]);
	        // end
		}
	}

	public function onGetLicenseValidityThreshold(Event $event, Entity $entity)
	{
        $thresholdData = json_decode($entity->threshold, true);
        return $thresholdData['value'];
	}

	public function onUpdateFieldLicenseValidityThreshold(Event $event, array $attr, $action, Request $request)
	{
		if ($action == 'add' || $action == 'edit') {
			$attr['type'] = 'hidden';
			$attr['value'] = '';
		}

		return $attr;
	}
}
