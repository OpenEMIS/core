<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\I18n\Date;
use App\Model\Table\ControllerActionTable;

class WithdrawRequestsTable extends ControllerActionTable
{
    const NEW_REQUEST = 0;

    public function initialize(array $config)
    {
        $this->table('institution_student_withdraw');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('StudentWithdrawReasons', ['className' => 'Student.StudentWithdrawReasons', 'foreignKey' => 'student_withdraw_reason_id']);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->addBehavior('Workflow.Workflow', ['model' => 'Institution.StudentWithdraw']);
        $this->toggle('edit', false);
        $this->toggle('view', false);
        $this->toggle('remove', false);
        $this->toggle('index', false);
    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $data)
    {
        if (!$entity->invalid()) {
            $studentId = $this->Session->read('Student.Students.id');
            $action = $this->url('add');
            $action['action'] = 'StudentUser';
            $action[0] = 'view';
            $action[1] = $this->paramsEncode(['id' => $studentId]);
            $action['id'] = $this->Session->read($this->registryAlias().'.id');

            $event->stopPropagation();
            $this->Session->delete($this->registryAlias().'.id');
            return $this->controller->redirect($action);
        }
    }

    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $requestData)
    {
        $WorkflowModelsTable = TableRegistry::get('Workflow.WorkflowModels');
        $StudentTransfersTable = TableRegistry::get('Institution.InstitutionStudentTransfers');
        $pendingTransferStatuses = $StudentTransfersTable->getStudentTransferWorkflowStatuses('PENDING');

        $conditions = [
            'student_id' => $entity->student_id,
            'status_id IN ' => $pendingTransferStatuses,
            'previous_education_grade_id' => $entity->education_grade_id,
            'previous_institution_id' => $entity->institution_id,
            'previous_academic_period_id' => $entity->academic_period_id
        ];

        $count = $StudentTransfersTable->find()
            ->where($conditions)
            ->count();

        if ($count > 0) {
            $process = function ($model, $entity) {
                $this->Alert->error('StudentWithdraw.hasTransferApplication');
            };
            return $process;
        }
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($this->Session->check($this->registryAlias().'.id')) {
            $this->field('student_id', ['type' => 'readonly', 'attr' => ['value' => $this->Users->get($entity->student_id)->name_with_id]]);
            $this->field('institution_id', ['type' => 'readonly', 'attr' => ['value' => $this->Institutions->get($entity->institution_id)->code_name]]);
            $this->field('academic_period_id', ['type' => 'hidden', 'attr' => ['value' => $entity->academic_period_id]]);
            $this->field('education_grade_id', ['type' => 'readonly', 'attr' => ['value' => $this->EducationGrades->get($entity->education_grade_id)->programme_grade_name]]);
            $this->field('effective_date');
            $this->field('student_withdraw_reason_id', ['type' => 'select']);
            $this->field('comment');

            $this->setFieldOrder([
                'student_id','institution_id', 'academic_period_id', 'education_grade_id',
                'effective_date',
                'student_withdraw_reason_id', 'comment',
            ]);
        } else {
            $Students = TableRegistry::get('Institution.Students');
            $action = $this->url('index');
            $action['action'] = $Students->alias();
            $event->stopPropagation();
            return $this->controller->redirect($action);
        }

        $toolbarButtons = $extra['toolbarButtons'];
        $studentId = $this->Session->read('Student.Students.id');
        $Students = TableRegistry::get('Institution.StudentUser');
        $toolbarButtons['back']['url']['action'] = $Students->alias();
        $toolbarButtons['back']['url'][0] = 'view';
        $toolbarButtons['back']['url'][1] = $this->paramsEncode(['id' => $studentId]);
    }

    public function addOnInitialize(Event $event, Entity $entity)
    {
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $id = $this->Session->read($this->registryAlias().'.id');
        $Students = TableRegistry::get('Institution.Students');
        $student = $Students->get($id);
        $entity->student_id = $student->student_id;
        $entity->academic_period_id = $student->academic_period_id;
        $entity->education_grade_id = $student->education_grade_id;
        $entity->institution_id = $student->institution_id;

        $this->request->data[$this->alias()]['student_id'] = $entity->student_id;
        $this->request->data[$this->alias()]['academic_period_id'] = $entity->academic_period_id;
        $this->request->data[$this->alias()]['education_grade_id'] = $entity->education_grade_id;
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $validator->add('effective_date', 'ruleDateAfterEnrollment', [
                    'rule' => ['dateAfterEnrollment'],
                    'provider' => 'table'
                    ]);
        return $validator;
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
        return $events;
    }

    public function onUpdateFieldEffectiveDate(Event $event, array $attr, $action, $request)
    {
        $id = $this->Session->read($this->registryAlias().'.id');
        $studentData = TableRegistry::get('Institution.Students')->get($id);
        $enrolledDate = $studentData['start_date']->format('d-m-Y');
        $attr['date_options'] = ['startDate' => $enrolledDate];

        return $attr;
    }
}
