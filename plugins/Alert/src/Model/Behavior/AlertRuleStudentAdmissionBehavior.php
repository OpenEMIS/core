<?php

namespace Alert\Model\Behavior;

use ArrayObject;

use Alert\Model\Behavior\AlertRuleBehavior;

use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\EventInterface;

/* POCOR-7462 for cases alert rule */

class AlertRuleStudentAdmissionBehavior extends AlertRuleBehavior
{
    protected $_defaultConfig = [
        'feature' => 'StudentAdmission',
        'name' => 'Student Admission',
        'method' => ['Email','SMS'], // POCOR-8286
        'threshold' => [
            'workflow_steps' => [
                'type' => 'chosenSelect',
                'select' => false,
                'after' => 'security_roles',
                'options' => 'StudentAdmission.workflow_steps',
                'attr' => ['required' => true], //POCOR-9509: mark workflow_steps as required
            ],
        ],
        'placeholder' => [
            '${admission_status}' => 'Admission Status',
            '${academic_period.name}' => 'Academic Period Name',
            '${start_date}' => 'Student Study Start Date',
            '${end_date}' => 'Student Study End Date',
            '${student.openemis_no}' => 'Student OpenEMIS ID',
            '${student.name}' => 'Student Name',
            '${student.first_name}' => 'Student First Name',
            '${student.middle_name}' => 'Student Middle Name',
            '${student.third_name}' => 'Student Third Name',
            '${student.last_name}' => 'Student Last Name',
            '${student.preferred_name}' => 'Student Preferred Name',
            '${student.email}' => 'Student Email',
            '${student.address}' => 'Student Address',
            '${student.postal_code}' => 'Student Postal Code',
            '${student.date_of_birth}' => 'Student Date of Birth',
            '${institution.name}' => 'Institution (School) Name',
            '${institution.code}' => 'Institution (School) Code',
            '${institution.address}' => 'Institution Address',
            '${institution.postal_code}' => 'Institution Postal Code',
            '${institution.contact_person}' => 'Institution Contact Person',
            '${institution.telephone}' => 'Institution Telephone Number',
//            '${institution.fax}' => 'Institution Fax Number',
            '${institution.email}' => 'Institution Email',
            '${institution.website}' => 'Institution Website',

//            '${school_name}' => 'School Name.',
//            '${student_name}' => 'Student Name.', //POCOR-9103
//            '${academic_year}' => 'Academic Year.',
            '${grade.name}' => 'Education Grade Name.',
            '${guardian.name}' => 'Guardian Name.',
            '${guardian.relation}' => 'Guardian Relation.',
            '${guardian.contact}' => 'Guardian Contact.',
            // '${assignee.middle_name}'=>'Assignee Middle Name.',
            // '${assignee.third_name}'=>'Assignee Third Name.',
            // '${assignee.last_name}'=>'Assignee Last Name.',
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
                //POCOR-9509: start - validate workflow_steps is required non-empty array
                $workflowIds = $data['workflow_steps']['_ids'] ?? [];
                if (empty($workflowIds)) {
                    $data['workflow_steps'] = null;
                }
                $validator = $model->getValidator();
                $validator->notEmptyString('workflow_steps', __('Workflow Step cannot be empty'));
                //POCOR-9509: end
                $model->setValidator('forSave', $validator); // POCOR-8286
            }
        }
    }

    public function onStudentAdmissionSetupFields(EventInterface $event, Entity $entity)
    {
        $this->onAlertRuleSetupFields($event, $entity);
    }

    public function onGetStudentAdmissionThreshold(EventInterface $event, Entity $entity)
    {
        $thresholdData = json_decode($entity->threshold, true);
        return $thresholdData['value'];
    }
}
