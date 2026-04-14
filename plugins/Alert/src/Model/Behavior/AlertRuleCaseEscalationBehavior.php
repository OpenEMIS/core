<?php
namespace Alert\Model\Behavior;

use ArrayObject;

use Alert\Model\Behavior\AlertRuleBehavior;

use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\EventInterface;

/* POCOR-7462 for cases alert rule */
class AlertRuleCaseEscalationBehavior extends AlertRuleBehavior
{
    protected $_defaultConfig = [
        'feature' => 'CaseEscalation',
        'name' => 'Case Escalation',
        'method' => ['Email','SMS'], // POCOR-8286
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
                    'options' =>'Cases.workflow_steps',
                    'attr' => ['required' => true], //POCOR-9509: mark workflow_steps as required
                ],
        ],
        'placeholder' => [
            '${case.case_number}'      => 'The unique identifier for the case',
            '${case.title}'            => 'The title or subject of the case',
            '${case.description}'      => 'The detailed description of the case',
            '${case.created}'          => 'The date and time when the case was created',
            '${case.status}'           => 'The current status of the case (e.g., Open, Closed, In Progress)',
            '${case.type}'             => 'The type or category of the case',
            '${case.priority}'         => 'The priority level of the case (e.g., Low, Medium, High, Urgent)',
            '${days_open}'             => 'The number of days the case has been open',
            '${threshold.value}'       => 'The threshold value set for case escalation',
            // Assignee fields
            '${assignee.openemis_no}'  => 'The OpenEMIS ID of the assignee',
            '${assignee.first_name}'   => 'The first name of the person assigned to the case',
            '${assignee.middle_name}'  => 'The middle name of the assignee',
            '${assignee.last_name}'    => 'The last name of the assignee',
            '${assignee.name}'         => 'The full name of the assignee',
            '${assignee.email}'        => 'The email address of the assignee',
            // Institution fields
            '${institution.name}'      => 'The name of the institution associated with the case',
            '${institution.code}'      => 'The institution code or identifier',
            '${institution.address}'   => 'The physical address of the institution',
            '${institution.postal_code}' => 'The postal code of the institution',
            '${institution.contact_person}' => 'The contact person at the institution',
            '${institution.telephone}' => 'The telephone number of the institution',
            '${institution.email}'     => 'The email address of the institution',
            '${institution.website}'   => 'The website URL of the institution',
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
                // POCOR-8286 start
                $validator = $model->getValidator();
                $validator->add('value', [
                    'ruleRange' => [
                        'rule' => ['range', 1, 30],
                        'message' => __('Value must be within 1 to 30')
                    ]
                ]);
                //POCOR-9509: start - validate workflow_steps is required non-empty array
                $workflowIds = $data['workflow_steps']['_ids'] ?? [];
                if (empty($workflowIds)) {
                    $data['workflow_steps'] = null;
                }
                $validator->notEmptyString('workflow_steps', __('Workflow Step cannot be empty'));
                //POCOR-9509: end
                $model->setValidator('forSave', $validator);
                // POCOR-8286 end
            }
        }
    }

    public function onCaseEscalationSetupFields(EventInterface $event, Entity $entity)
    {
        $this->onAlertRuleSetupFields($event, $entity);
    }

    public function onGetCaseThreshold(EventInterface $event, Entity $entity)
    {
        $thresholdData = json_decode($entity->threshold, true);
        return $thresholdData['value'];
    }



}
