<?php

namespace Alert\Model\Behavior;

use ArrayObject;

use Alert\Model\Behavior\AlertRuleBehavior;

use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

/* POCOR-7462 for cases alert rule */

class AlertRuleStudentAdmissionBehavior extends AlertRuleBehavior
{
    protected $_defaultConfig = [
        'feature' => 'StudentAdmission',
        'name' => 'Student Admission',
        'method' => 'Email',
        'threshold' => [
            'workflow_steps' => [
                'type' => 'chosenSelect',
                'select' => false,
                'after' => 'security_roles',
                'options' => 'StudentAdmission.workflow_steps'
            ],
        ],
        'placeholder' => [
            '${school_name}' => 'School Name.',
            '${student_name}' => 'Student Name.', //POCOR-9103
            '${academic_year}' => 'Academic Year.',
            '${grade_name}' => 'Grade Level/Program Name.',
            // '${assignee.middle_name}'=>'Assignee Middle Name.',
            // '${assignee.third_name}'=>'Assignee Third Name.',
            // '${assignee.last_name}'=>'Assignee Last Name.',
        ]

    ];

    public function initialize(array $config): void
    {
        parent::initialize($config);
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        $model = $this->_table;
        if (isset($data['feature']) && !empty($data['feature']) && $data['feature'] == $this->alertRule) {

            if (isset($data['submit']) && $data['submit'] == 'save') {
                $validator = $model->getValidator();
                $validator->add('value', [
                    'ruleRange' => [
                        'rule' => ['range', 1, 30],
                        'message' => __('Value must be within 1 to 30')
                    ]
                ]);
            }
        }
    }

    public function onStudentAdmissionSetupFields(Event $event, Entity $entity)
    {
        $this->onAlertRuleSetupFields($event, $entity);
    }

    public function onGetStudentAdmissionThreshold(Event $event, Entity $entity)
    {
        $thresholdData = json_decode($entity->threshold, true);
        return $thresholdData['value'];
    }
}
