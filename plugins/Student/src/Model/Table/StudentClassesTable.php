<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;

use App\Model\Traits\MessagesTrait;
use App\Model\Table\ControllerActionTable;

class StudentClassesTable extends ControllerActionTable
{
    use MessagesTrait;

    public function initialize(array $config)
    {
        $this->table('institution_class_students');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('NextInstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' =>'next_institution_class_id']);

        $this->hasMany('InstitutionClassGrades', ['className' => 'Institution.InstitutionClassGrades', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        $this->toggle('search', false);

        $this->addBehavior('Restful.RestfulAccessControl');
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $contentHeader = $this->controller->viewVars['contentHeader'];
        list($studentName, $module) = explode(' - ', $contentHeader);
        $module = __('Classes');
        $contentHeader = $studentName . ' - ' . $module;
        $this->controller->set('contentHeader', $contentHeader);
        $this->controller->Navigation->substituteCrumb(__('Student Classes'), __('Classes'));
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['education_grade_id']['visible'] = false;
        $this->fields['institution_id']['visible'] = false;
        $this->fields['academic_period_id']['visible'] = false;

        $this->field('academic_period', []);
        $this->field('institution', []);
        $this->field('current_class', []);
        $this->field('education_grade', []);
        $this->field('homeroom_teacher_name', []);

        $order = 0;
        $this->setFieldOrder('academic_period', $order++);
        $this->setFieldOrder('institution', $order++);
        $this->setFieldOrder('current_class', $order++);
        $this->setFieldOrder('education_grade', $order++);
        $this->setFieldOrder('institution_class_id', $order++);
        $this->setFieldOrder('homeroom_teacher_name', $order++);
        
        if (!empty($this->request->query['institution_class_id'])) {
            $action = 'view';
            $hasAllClassesPermission = $this->AccessControl->check(['Institutions', 'AllClasses', $action]);
            $hasMyClassesPermission = $this->AccessControl->check(['Institutions', 'Classes', $action]);
            
            $url = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'Classes',
                'view',
                $this->paramsEncode(['id' => $this->request->query['institution_class_id']]),
                'institution_id' => $this->request->query['institution_id'],
            ];

            if ($hasAllClassesPermission) {
                return $this->controller->redirect($url);
            } 
            
            if ($hasMyClassesPermission) {
                $userId = $this->Auth->user('id');
                if ($userId == $this->request->query['staff_id'] || $userId == $this->request->query['secondary_staff_id']) {
                    return $this->controller->redirect($url);
                }
            }
            
            $this->Alert->error('security.noAccess');
        }

    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
		$userData = $this->Session->read();
		$studentId = $userData['Auth']['User']['id'];

		$condition = [];
		if(!empty($userData['System']['User']['roles']) & !empty($userData['Student']['Students']['id'])) {
			$condition = [];
		} else {
			if (!empty($studentId)) {
				$conditions[$this->aliasField('student_id')] = $studentId;
			}
		}
		
        $query->contain([
            'InstitutionClasses',
            'StudentStatuses'
        ])
		->where($conditions)
        ->toArray();
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        if (array_key_exists('view', $buttons)) {
            $institutionId = $entity->institution_class->institution_id;
            $url = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'Classes',
                'view',
                $this->paramsEncode(['id' => $entity->institution_class->id]),
                'institution_id' => $institutionId,
            ];

            if ($this->controller->name == 'Directories') {
                $url = [
                    'plugin' => 'Directory',
                    'controller' => 'Directories',
                    'action' => 'StudentClasses',
                    'index',
                    'type' => 'student',
                    'staff_id' => $entity->institution_class->staff_id,
                    'secondary_staff_id' => $entity->institution_class->secondary_staff_id,
                    'institution_class_id' => $entity->institution_class->id,
                    'institution_id' => $institutionId,
                ];
            }
            $buttons['view']['url'] = $url;
        }
        return $buttons;
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $options = ['type' => 'student'];
        $tabElements = $this->controller->getAcademicTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Classes');
    }

    public function getClassStudents($classId, $periodId, $institutionId)
    {
        $enrolledStatus = TableRegistry::get('Student.StudentStatuses')->getIdByCode('CURRENT');
        return $this->find()
                ->contain('Users')
                ->where([
                    $this->aliasField('institution_class_id') => $classId,
                    $this->aliasField('academic_period_id') => $periodId,
                    $this->aliasField('institution_id') => $institutionId,
                    $this->aliasField('student_status_id') => $enrolledStatus
                ])
                ->toArray();
    }
}
