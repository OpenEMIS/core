<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Controller\Component;
use Cake\Utility\Inflector;
use Cake\I18n\Date;
use App\Model\Table\ControllerActionTable;

// This file serves as an abstract class for StudentTransferIn and StudentTransferOut
class InstitutionStudentTransfersTable extends ControllerActionTable
{
    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    // Initiated By
    const INCOMING = 1;
    const OUTGOING = 2;

    public function initialize(array $config)
    {
        $this->table('institution_student_transfers');
        parent::initialize($config);

        // Mandatory data
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);
        // New institution data
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades', 'foreignKey' => 'education_grade_id']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' => 'institution_class_id']);
        // Previous institution data
        $this->belongsTo('PreviousInstitutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'previous_institution_id']);
        $this->belongsTo('PreviousEducationGrades', ['className' => 'Education.EducationGrades', 'foreignKey' => 'previous_education_grade_id']);
        $this->belongsTo('StudentTransferReasons', ['className' => 'Student.StudentTransferReasons', 'foreignKey' => 'student_transfer_reason_id']);

        $this->addBehavior('Workflow.Workflow');
        $this->addBehavior('Institution.InstitutionWorkflowAccessControl');
        $this->addBehavior('OpenEmis.Section');
        $this->addBehavior('User.AdvancedNameSearch');
    }

    private $workflowEvents = [
        [
            'value' => 'Workflow.onTransferStudent',
            'text' => 'Transfer of Student',
            'description' => 'Performing this action will transfer the student to the receiving institution.',
            'method' => 'onTransferStudent',
            'unique' => true
        ],
        [
            'value' => 'Workflow.onCancel',
            'text' => 'Cancellation of Student Transfer',
            'description' => 'Performing this action will return the student to the sending institution.',
            'method' => 'onCancel',
            'unique' => true
        ]
    ];

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Workflow.getEvents'] = 'getWorkflowEvents';
        $events['Workflow.checkIfCanAddButtons'] = 'checkIfCanAddButtons';
        $events['Workflow.onSetCustomAssigneeParams'] = 'onSetCustomAssigneeParams';
        $events['UpdateAssignee.onSetCustomAssigneeParams'] = 'onSetCustomAssigneeParams';
        $events['Workflow.setAutoAssignAssigneeFlag'] = 'setAutoAssignAssigneeFlag';
        $events['ControllerAction.Model.getSearchableFields'] = 'getSearchableFields';
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';

        foreach($this->workflowEvents as $event) {
            $events[$event['value']] = $event['method'];
        }
        return $events;
    }

    public function getWorkflowEvents(Event $event, ArrayObject $eventsObject)
    {
        foreach ($this->workflowEvents as $key => $attr) {
            $attr['text'] = __($attr['text']);
            $attr['description'] = __($attr['description']);
            $eventsObject[] = $attr;
        }
    }

    public function onTransferStudent(Event $event, $id, Entity $workflowTransitionEntity)
    {
        $entity = $this->get($id);
        $Students = TableRegistry::get('Institution.Students');
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $statuses = $StudentStatuses->findCodeList();

        $previousStudentRecord = $Students->find()
            ->where([
                $Students->aliasField('institution_id') => $entity->previous_institution_id,
                $Students->aliasField('student_id') => $entity->student_id,
                $Students->aliasField('academic_period_id') => $entity->academic_period_id,
                $Students->aliasField('education_grade_id') => $entity->previous_education_grade_id,
                $Students->aliasField('student_status_id') => $statuses['CURRENT']
            ])
            ->first();

        if (!empty($previousStudentRecord)) {
            // add new student record in the new institution
            $newStudent = [
                'student_status_id' => $statuses['CURRENT'],
                'student_id' => $entity->student_id,
                'education_grade_id' => $entity->education_grade_id,
                'academic_period_id' => $entity->academic_period_id,
                'start_date' => $entity->start_date,
                'end_date' => $entity->end_date,
                'institution_id' => $entity->institution_id,
                'previous_institution_student_id' => $previousStudentRecord->id
            ];
            if (!empty($entity->institution_class_id)) {
                $newStudent['class'] = $entity->institution_class_id;
            }
            $newStudentEntity = $Students->newEntity($newStudent);

            if ($Students->save($newStudentEntity)) {
                // end previous student record
                if ($entity->requested_date < $entity->start_date) {
                    $newEndDate = $entity->requested_date;
                } else {
                    $newEndDate = (new Date($entity->start_date))->modify('-1 day');
                }
                $previousStudentRecord->end_date = $newEndDate;
                $previousStudentRecord->student_status_id = $statuses['TRANSFERRED'];
                $Students->save($previousStudentRecord);
            }
        }
    }

    public function onCancel(Event $event, $id, Entity $workflowTransitionEntity)
    {
        $entity = $this->get($id);
        $Students = TableRegistry::get('Institution.Students');
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $statuses = $StudentStatuses->findCodeList();

        $newStudentRecord = $Students->find()
            ->where([
                $Students->aliasField('institution_id') => $entity->institution_id,
                $Students->aliasField('student_id') => $entity->student_id,
                $Students->aliasField('academic_period_id') => $entity->academic_period_id,
                $Students->aliasField('education_grade_id') => $entity->education_grade_id,
                $Students->aliasField('student_status_id') => $statuses['CURRENT']
            ])
            ->first();

        $prevStudentId = null;
        if (!empty($newStudentRecord)) {
            // delete student record in the new institution
            $prevStudentId = $newStudentRecord->previous_institution_student_id;
            $Students->delete($newStudentRecord);
        }

        if (!is_null($prevStudentId)) {
            $previousStudentRecord = $Students->get($prevStudentId);
        } else {
            $previousStudentRecord = $Students->find()
                ->where([
                    $Students->aliasField('institution_id') => $entity->previous_institution_id,
                    $Students->aliasField('student_id') => $entity->student_id,
                    $Students->aliasField('academic_period_id') => $entity->academic_period_id,
                    $Students->aliasField('education_grade_id') => $entity->previous_education_grade_id,
                    $Students->aliasField('student_status_id') => $statuses['TRANSFERRED']
                ])
                ->first();
        }

        if ($previousStudentRecord) {
            // update previous student record back to enrolled status
            $academicPeriod = $this->AcademicPeriods->get($entity->academic_period_id);
            if (!empty($academicPeriod)) {
                $previousStudentRecord->end_date = $academicPeriod->end_date;
            }
            $previousStudentRecord->student_status_id = $statuses['CURRENT'];
            $Students->save($previousStudentRecord);
        }
    }

    // to determine if workflow buttons should be shown in view page
    public function checkIfCanAddButtons(Event $event, Entity $entity)
    {
        $canAddButtons = false;
        $institutionOwner = $this->getWorkflowStepsParamValue($entity->status_id, 'institution_owner');
        $currentInstitutionId = isset($this->request->params['institutionId']) ? $this->paramsDecode($this->request->params['institutionId'])['id'] : $this->request->session()->read('Institution.Institutions.id');

        if ($institutionOwner == self::INCOMING && $currentInstitutionId == $entity->institution_id) {
            $canAddButtons = $this->Institutions->isActive($entity->institution_id);
        } else if ($institutionOwner == self::OUTGOING && $currentInstitutionId == $entity->previous_institution_id) {
            $canAddButtons = $this->Institutions->isActive($entity->previous_institution_id);
        }
        return $canAddButtons;
    }

    // to get the correct list of assignees in modal and in UpdateAssigneeShell
    public function onSetCustomAssigneeParams(Event $event, Entity $entity, $params)
    {
        $institutionOwner = $this->getWorkflowStepsParamValue($entity->status_id, 'institution_owner');

        if ($institutionOwner == self::INCOMING) {
            $params['institution_id'] = $entity->institution_id;
        } else if ($institutionOwner == self::OUTGOING) {
            $params['institution_id'] = $entity->previous_institution_id;
        }
        return $params;
    }

    // to determine if assignee list or 'Auto Assign' should be shown
    public function setAutoAssignAssigneeFlag(Event $event, Entity $action)
    {
        $currentInstitutionOwner = $this->getWorkflowStepsParamValue($action->workflow_step_id, 'institution_owner');
        $nextInstitutionOwner = $this->getWorkflowStepsParamValue($action->next_workflow_step_id, 'institution_owner');
        return $currentInstitutionOwner != $nextInstitutionOwner ? 1 : 0;
    }

    public function getSearchableFields(Event $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'student_id';
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona)
    {
        $session = $this->request->session();
        $institutionId = isset($this->request->params['institutionId']) ? $this->paramsDecode($this->request->params['institutionId'])['id'] : $session->read('Institution.Institutions.id');
        $studentsUrl = ['plugin' => 'Institution', 'controller' => 'Institutions', 'institutionId' => $this->paramsEncode(['id' => $institutionId]), 'action' => 'Students'];
        $previousTitle = Inflector::humanize(Inflector::underscore($this->alias()));

        $Navigation->substituteCrumb($previousTitle, 'Students', $studentsUrl);
        $Navigation->addCrumb($previousTitle);
    }

    public function addSections()
    {
        $this->field('previous_information_header', ['type' => 'section', 'title' => __('Transfer From')]);
        $this->field('new_information_header', ['type' => 'section', 'title' => __('Transfer To')]);
        $this->field('transfer_reasons_header', ['type' => 'section', 'title' => __('Other Information')]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('all_visible', ['type' => 'hidden']);
    }

    // for index
    public function onGetStatusId(Event $event, Entity $entity)
    {
        $institutionOwner = $this->getWorkflowStepsParamValue($entity->status_id, 'institution_owner');
        $currentInstitutionId = isset($this->request->params['institutionId']) ? $this->paramsDecode($this->request->params['institutionId'])['id'] : $this->request->session()->read('Institution.Institutions.id');

        $belongsToCurrentInstitution = ($institutionOwner == self::INCOMING && $currentInstitutionId == $entity->institution_id) || ($institutionOwner == self::OUTGOING && $currentInstitutionId == $entity->previous_institution_id);

        if ($belongsToCurrentInstitution) {
            return '<span class="status highlight">' . $entity->status->name . '</span>';
        } else {
            return '<span class="status past">' . $entity->status->name . '</span>';
        }
    }

    // for view
    public function onGetWorkflowStatus(Event $event, Entity $entity)
    {
        $institutionOwner = $this->getWorkflowStepsParamValue($entity->status_id, 'institution_owner');
        $currentInstitutionId = isset($this->request->params['institutionId']) ? $this->paramsDecode($this->request->params['institutionId'])['id'] : $this->request->session()->read('Institution.Institutions.id');

        $belongsToCurrentInstitution = ($institutionOwner == self::INCOMING && $currentInstitutionId == $entity->institution_id) || ($institutionOwner == self::OUTGOING && $currentInstitutionId == $entity->previous_institution_id);

        if ($belongsToCurrentInstitution) {
            return '<span class="status highlight">' . $entity->workflow_status . '</span>';
        } else {
            return '<span class="status past">' . $entity->workflow_status . '</span>';
        }
    }

    public function onGetPreviousInstitutionId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('previous_institution')) {
            $value = $entity->previous_institution->code_name;
        }
        return $value;
    }

    public function onGetInstitutionId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('institution')) {
            $value = $entity->institution->code_name;
        }
        return $value;
    }

    public function onGetPreviousEducationGradeId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('previous_education_grade')) {
            $value = $entity->previous_education_grade->programme_grade_name;
        }
        return $value;
    }

    public function onGetEducationGradeId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('education_grade')) {
            $value = $entity->education_grade->programme_grade_name;
        }
        return $value;
    }

    public function onGetStudentId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('user')) {
            $value = $entity->user->name_with_id;
        }
        return $value;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'institution_id') {
            return __('New Institution');
        } else if ($field == 'previous_institution_id') {
            return __('Current Institution');
        } else if ($field == 'previous_education_grade_id') {
            return __('Education Grade');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if (!$entity->isNew() && $entity->dirty('status_id')) {
            if (!$entity->all_visible) {
                $currentInstitutionOwner = $this->getWorkflowStepsParamValue($entity->status_id, 'institution_owner');
                $previousInstitutionOwner = $this->getWorkflowStepsParamValue($entity->getOriginal('status_id'), 'institution_owner');

                if ($currentInstitutionOwner != $previousInstitutionOwner) {
                    $this->updateAll(['all_visible' => 1], ['id' => $entity->id]);
                }
            }
        }
    }

    public function findInstitutionStudentTransferIn(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $incomingInstitution = self::INCOMING;
        $pending = array_key_exists('pending_records', $options) ? $options['pending_records'] : false;

        $query
            ->matching('Statuses.WorkflowStepsParams', function ($q) {
                return $q->where(['WorkflowStepsParams.name' => 'institution_owner']);
            })
            ->where([
                $this->aliasField('institution_id') => $institutionId,
                'OR' => [
                    'WorkflowStepsParams.value' => self::INCOMING, // institution_owner for the step can always see the record
                    $this->aliasField('all_visible') => 1
                ]
            ]);

        if ($pending) {
            $query->where(['Statuses.category <> ' => self::DONE]);
        }
        return $query;
    }

    public function findInstitutionStudentTransferOut(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $outgoingInstitution = self::OUTGOING;
        $pending = array_key_exists('pending_records', $options) ? $options['pending_records'] : false;

        $query
            ->matching('Statuses.WorkflowStepsParams', function ($q) {
                return $q->where(['WorkflowStepsParams.name' => 'institution_owner']);
            })
            ->where([
                $this->aliasField('previous_institution_id') => $institutionId,
                'OR' => [
                    'WorkflowStepsParams.value' => self::OUTGOING, // institution_owner for the step can always see the record
                    $this->aliasField('all_visible') => 1
                ]
            ]);

        if ($pending) {
            $query->where(['Statuses.category <> ' => self::DONE]);
        }
        return $query;
    }
}
