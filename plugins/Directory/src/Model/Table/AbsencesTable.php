<?php
namespace Directory\Model\Table;

use ArrayObject;
use Cake\Validation\Validator;
use Cake\Event\EventInterface;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;

use App\Model\Table\ControllerActionTable;

class AbsencesTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->setTable('institution_student_absences');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        // $this->belongsTo('StudentAbsenceReasons', ['className' => 'Institution.StudentAbsenceReasons']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AbsenceTypes', ['className' => 'Institution.AbsenceTypes', 'foreignKey' => 'absence_type_id']);
        $this->belongsTo('InstitutionStudentAbsenceDays', ['className' => 'Institution.InstitutionStudentAbsenceDays', 'foreignKey' => 'institution_student_absence_day_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades', 'foreignKey' => 'education_grade_id']);

    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.custom.onUpdateToolbarButtons' => 'onUpdateToolbarButtons'


        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function onUpdateToolbarButtons(EventInterface $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
        switch ($action) {
            case 'index':
                    // $toolbarButtons['edit'] = $buttons['index'];
                    // $toolbarButtons['edit']['url'][0] = 'index';
                    // $toolbarButtons['edit']['action'] = 'abc';
                    // $toolbarButtons['edit']['type'] = 'button';
                    // $toolbarButtons['edit']['label'] = '<i class="fa fa-folder"></i>';
                    // $toolbarButtons['edit']['attr'] = $attr;
                    // $toolbarButtons['edit']['attr']['title'] = __('Archive');
                    if($toolbarButtons['edit']['url']['action'] == 'Absences'){
                        $toolbarButtons['edit']['url']['action'] = 'InstitutionStudentAbsencesArchived';
                    }
                break;
        }
    }

    public function beforeAction($event)
    {
        // $this->fields['student_absence_reason_id']['type'] = 'select';
        $this->fields['institution_student_absence_day_id']['visible'] = false;
        // POCOR-5245
        $queryString = $this->request->getQuery['queryString'];
        if ($queryString) {
            $event->stopPropagation();
            $condition = $this->paramsDecode($queryString);
            $entity = $this->get($condition['id']);
            $institutionStudentAbsenceDaysEntity = $this->InstitutionStudentAbsenceDays->get($entity->institution_student_absence_day_id);
            $this->InstitutionStudentAbsenceDays->delete($institutionStudentAbsenceDaysEntity);
            TableRegistry::getTableLocator()->get('InstitutionStudentAbsenceDetails')
                    ->deleteAll(['student_id'=>$entity->student_id,
                            'date'=>$entity->date,
                            ]);

            $this->delete($entity);
            $this->Alert->success('StudentAbsence.deleteRecord', ['reset'=>true]);
            return $this->controller->redirect(['plugin' => $this->controller->getPlugin(), 'controller' => $this->controller->getName(), 'action' => 'Absences','index']);
        }
    }

    public function indexBeforeAction(EventInterface $event)
    {
       // $query = $this->request->query;


        // $this->fields['end_date']['visible'] = false;
        // $this->fields['full_day']['visible'] = false;
        // $this->fields['start_time']['visible'] = false;
        // $this->fields['end_time']['visible'] = false;
        $this->fields['institution_id']['visible'] = false;
        //$this->fields['comment']['visible'] = true;
        $this->fields['student_id']['visible'] = false;
        $this->fields['academic_period_id']['visible'] = false;

        // $this->ControllerAction->addField('days');
        // $this->ControllerAction->addField('comment');
        // $this->ControllerAction->addField('time');

        $order = 0;
        // $this->ControllerAction->setFieldOrder('start_date', $order++);
        $this->ControllerAction->setFieldOrder('date', $order++);
        $this->ControllerAction->setFieldOrder('days', $order++);
        // $this->ControllerAction->setFieldOrder('time', $order++);
        // $this->ControllerAction->setFieldOrder('student_absence_reason_id', $order++);
    }

    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
    {
        parent::onUpdateActionButtons($event, $entity, $buttons);

        if (isset($buttons['view'])) {
            $institutionId = $entity->institution->id;
            $url = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'StudentAbsences',
                'view',
                $this->paramsEncode(['id' => $entity->id]),
                'institution_id' => $institutionId,
            ];
            $buttons['view']['url'] = $url;

            // POCOR-1893 unset the view button on profiles controller
            if ($this->controller->getName() == 'Profiles') {
                unset($buttons['view']);
            }
            // end POCOR-1893
        }

        if (isset($buttons['remove'])) {
            $institutionId = $entity->institution->id;
            $url = [
                'plugin' => 'Student',
                'controller' => 'Students',
                'action' => 'Absences',
                'remove',
                'queryString' => $this->paramsEncode(['id' => $entity->id])
            ];
            $buttons['remove']['url'] = $url;

            // POCOR-5245 unset the view button on profiles controller
            if ($this->controller->getName() == 'Profiles') {
                unset($buttons['remove']);
            }
            // end POCOR-5245
        }

        return $buttons;
    }

    private function setupTabElements()
    {
        $options['type'] = 'student';
        $tabElements = $this->controller->getAcademicTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function indexAfterAction(EventInterface $event, $data)
    {
        $this->setupTabElements();
    }

    public function beforeFind( EventInterface $event, Query $query )
    {
        $userData = $this->Session->read();

       if ($userData['Auth']['User']['is_guardian'] == 1) {
            $sId = $userData['Student']['ExaminationResults']['student_id'];
            if ($sId) {
                $studentId = $this->ControllerAction->paramsDecode($sId)['id'];
            }
            $studentId = $userData['Student']['Students']['id'];
        } else {
            $studentId = $userData['Auth']['User']['id'];
        }

        if(!empty($userData['System']['User']['roles']) & !empty($userData['Student']['Students']['id'])) {

        } else {
            if (!empty($studentId)) {
                $where[$this->aliasField('student_id')] = $studentId;
            }
        }

        $InstitutionStudentAbsenceDetails = TableRegistry::getTableLocator()->get('Institution.InstitutionStudentAbsenceDetails');
            $query
                ->find('all')
                ->autoFields(true)
                ->select([
                    'comment' => $InstitutionStudentAbsenceDetails->aliasField('comment')
                ])
                ->leftJoin(
                [$InstitutionStudentAbsenceDetails->alias() => $InstitutionStudentAbsenceDetails->table()],
                [
                    $InstitutionStudentAbsenceDetails->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $InstitutionStudentAbsenceDetails->aliasField('date = ') . $this->aliasField('date'),
                    $InstitutionStudentAbsenceDetails->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
                    $InstitutionStudentAbsenceDetails->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                    $InstitutionStudentAbsenceDetails->aliasField('institution_class_id = ') . $this->aliasField('institution_class_id')
                ]
            )
            ->where($where);
    }

}
