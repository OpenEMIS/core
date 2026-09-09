<?php
namespace Institution\Model\Table;

use ArrayObject;
use DatePeriod;
use DateInterval;
use Cake\Event\EventInterface;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Datasource\ResultSetInterface;
use Cake\Collection\Collection;
use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Log\Log;
use Workflow\Model\Table\WorkflowStepsTable as WorkflowSteps;
use App\Model\Table\ControllerActionTable;

class InstitutionStudentUnmarkedAttendancesTable extends ControllerActionTable
{
    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    private $institutionId = null;
    private $staffId = null;

    // POCOR-7626: Rule Events offered on Workflow > Rules for the Student Unmarked
    // Attendances feature. Only the institution-level Principal role applies here - Home
    // Room/Secondary Teacher assignment (as offered for Student Attendances) needs a
    // student_id, which this table (mapped to institution_staff_leave_archived) has
    // no equivalent of.
    private $workflowRuleEvents = [
        [
            'value' => 'Workflow.onAssignToHomeRoomTeacher',
            'text' => 'Assign to Home Room Teacher',
            'description' => 'Triggering this rule will assign the case to the respective Home Room Teacher',
            'method' => 'onAssignToHomeRoomTeacher',
            'roleCode' => 'HOMEROOM_TEACHER'
        ],
        [
            'value' => 'Workflow.onAssignToSecondaryTeacher',
            'text' => 'Assign to Secondary Teacher',
            'description' => 'Triggering this rule will assign the case to the respective Secondary Teacher',
            'method' => 'onAssignToSecondaryTeacher',
            'roleCode' => 'HOMEROOM_TEACHER'
        ],
        [
            'value' => 'Workflow.onAssignToPrincipal',
            'text' => 'Assign to Principal',
            'description' => 'Triggering this rule will assign the case to Principal',
            'method' => 'onAssignToPrincipal',
            'roleCode' => 'PRINCIPAL'
        ]
    ];

    public function initialize(array $config): void
    {
        $this->setTable('institution_staff_leave_archived');
        parent::initialize($config);

        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('StaffLeaveTypes', ['className' => 'Staff.StaffLeaveTypes']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);

        // POCOR-4047 to get staff profile data
        $this->addBehavior('Institution.StaffProfile');
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();

        // POCOR-7626: without this, Workflow > Rules > Rule Events > Add Event shows
        // "No options" for the Student Unmarked Attendances feature - WorkflowRulesTable::
        // getEvents() dispatches 'Workflow.getRuleEvents' on this table looking for a listener.
        $events['Workflow.getRuleEvents'] = 'getWorkflowRuleEvents';
        foreach ($this->workflowRuleEvents as $event) {
            $events[$event['value']] = $event['method'];
        }
        return $events;
    }

    public function getWorkflowRuleEvents(EventInterface $event, ArrayObject $eventsObject)
    {
        foreach ($this->workflowRuleEvents as $key => $attr) {
            $attr['text'] = __($attr['text']);
            $attr['description'] = __($attr['description']);
            $eventsObject[] = $attr;
        }
    }

    public function onAssignToPrincipal(EventInterface $event, Entity $caseEntity, Entity $linkedRecordEntity, ArrayObject $extra)
    {
        $InstitutionPositions = TableRegistry::getTableLocator()->get('Institution.InstitutionPositions');
        $Cases = TableRegistry::getTableLocator()->get('Cases.InstitutionCases');

        $institutionPrincipal = $InstitutionPositions->find()
            ->select([
                'principal_id' => 'InstitutionStaff.staff_id'
            ])
            ->matching('InstitutionStaff')
            ->matching('StaffPositionTitles')
            ->where([
                'InstitutionStaff.institution_id' => $linkedRecordEntity->institution_id,
                'StaffPositionTitles.name ' => 'Principal'
            ])
            ->first();

        if (!empty($institutionPrincipal)) {
            $staffId = $institutionPrincipal->principal_id;

            if (!empty($staffId)) {
                $caseEntity->assignee_id = $staffId;
                $extra['assigneeFound'] = true;
                $Cases->save($caseEntity);
            }
        }
    }

}
