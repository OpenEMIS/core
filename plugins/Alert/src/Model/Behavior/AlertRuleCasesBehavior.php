<?php
namespace Alert\Model\Behavior;

use ArrayObject;

use Alert\Model\Behavior\AlertRuleBehavior;

use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

/* POCOR-7462 for cases alert rule*/ 
class AlertRuleCasesBehavior extends AlertRuleBehavior
{
    protected $_defaultConfig = [
        'feature' => 'Cases',
        'name' => 'Case Escalation',
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
                'workflow_steps' => [
                    'type' => 'chosenSelect',
                    'select' => false,
                    'after' => 'security_roles',
                    'options' => 'Cases.workflow_steps'
                ],
        ],
        'placeholder' => [
            '${threshold.value}' => 'Threshold value.',
            '${license_type.name}' => 'License type.',
            '${license_number}' => 'License number.',
            '${issue_date}' => 'Issue date.',
            '${expiry_date}' => 'Expiry date.',
            '${day_difference}' => 'Days difference between today and expiry date.',
            '${issuer}' => 'Issuer.',
            '${cases.casenumber}'=>'Case Number.',
            '${assignee.openemis_no}'=>'Assignee OpenEMIS ID.',
            '${assignee.first_name}'=>'Assignee First Name.',
            '${assignee.Middle_name}'=>'Assignee Middle Name.',
            '${assignee.third_name}'=>'Assignee Third Name.',
            '${assignee.last_name}'=>'Assignee Last Name.',
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

    public function onCasesSetupFields(Event $event, Entity $entity)
    {
        $this->onAlertRuleSetupFields($event, $entity);
    }

    public function onGetCasesThreshold(Event $event, Entity $entity)
    {
        $thresholdData = json_decode($entity->threshold, true);
        return $thresholdData['value'];
    }


   
}
