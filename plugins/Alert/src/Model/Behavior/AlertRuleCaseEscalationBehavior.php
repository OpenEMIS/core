<?php
namespace Alert\Model\Behavior;

use ArrayObject;

use Alert\Model\Behavior\AlertRuleBehavior;

use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

/* POCOR-7462 for cases alert rule */ 
class AlertRuleCaseEscalationBehavior extends AlertRuleBehavior
{
    protected $_defaultConfig = [
        'feature' => 'CaseEscalation',
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
                    'options' =>'Cases.workflow_steps'
                ],
        ],
        'placeholder' => [
            '${threshold.value}' => 'Threshold value.',
            '${case_number}'=>'Case Number.',
            '${assignee.openemis_no}'=>'Assignee OpenEMIS ID.',
            '${assignee.first_name}'=>'Assignee First Name.',
            '${assignee.middle_name}'=>'Assignee Middle Name.',
            '${assignee.third_name}'=>'Assignee Third Name.',
            '${assignee.last_name}'=>'Assignee Last Name.',
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

    public function onCaseEscalationSetupFields(Event $event, Entity $entity)
    {
        $this->onAlertRuleSetupFields($event, $entity);
    }

    public function onGetCaseThreshold(Event $event, Entity $entity)
    {
        $thresholdData = json_decode($entity->threshold, true);
        return $thresholdData['value'];
    }


   
}
