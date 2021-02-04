<?php
namespace Student\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;

use App\Model\Table\ControllerActionTable;

class StudentSubjectsTable extends ControllerActionTable
{

    public function initialize(array $config)
    {
        $this->table('institution_subject_students');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('InstitutionSubjects', ['className' => 'Institution.InstitutionSubjects']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);

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
        $module = __('Subjects');
        $contentHeader = $studentName . ' - ' . $module;
        $this->controller->set('contentHeader', $contentHeader);
        $this->controller->Navigation->substituteCrumb(__('Student Subjects'), $module);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['student_status_id']['visible'] = false;

        $this->field('academic_period_id', ['type' => 'integer', 'order' => 0]);
        $this->field('institution_id', ['type' => 'integer', 'after' => 'academic_period_id']);
        $this->field('total_mark', ['after' => 'institution_subject_id']);

        $extra['elements']['controls'] = ['name' => 'Student.Subjects/controls', 'data' => [], 'options' => [], 'order' => 1];

        if (!empty($this->request->query['institution_subject_id'])) {
            $action = 'view';
            $hasAllSubjectsPermission = $this->AccessControl->check(['Institutions', 'AllSubjects', $action]);
   
            $hasMySubjectsPermission = $this->AccessControl->check(['Institutions', 'Subjects', $action]);
            $url = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'Subjects',
                'view',
                $this->paramsEncode(['id' => $this->request->query['institution_subject_id']]),
                'institution_id' => $this->request->query['institution_id'],
            ];

            if ($hasAllSubjectsPermission) {
                return $this->controller->redirect($url);
            }

            if ($hasMySubjectsPermission) {
                $userId = $this->Auth->user('id');

                $institutionSubjectStaffTable = TableRegistry::get('Institution.InstitutionSubjectStaff');
                $subjectsTeaching = $institutionSubjectStaffTable->find()
                    ->find('list', ['keyField' => 'subject', 'valueField' => 'subject'])
                    ->select (['subject' => $institutionSubjectStaffTable->aliasField('institution_subject_id'),
                                'id' => $institutionSubjectStaffTable->aliasField('id')])
                    ->where([
                        $institutionSubjectStaffTable->aliasField('staff_id') => $userId,
                    ])
                    ->toArray();

                if (!empty($subjectsTeaching) && array_key_exists($this->request->query['institution_subject_id'],$subjectsTeaching)) {
                    return $this->controller->redirect($url);
                }
            }
            $this->Alert->error('security.noAccess');
        }

    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // Academic Periods filter
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $selectedAcademicPeriod = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        //End

        // Institution and Grade filter
        $studentId = $this->Session->read('Student.Students.id');
        $InstitutionStudents = TableRegistry::get('Institution.Students');
        $institutionQuery = $InstitutionStudents->find()
            ->contain(['Institutions', 'StudentStatuses', 'EducationGrades'])
            ->where([
                $InstitutionStudents->aliasField('student_id') => $studentId,
                $InstitutionStudents->aliasField('academic_period_id') => $selectedAcademicPeriod
            ])
            ->order([$InstitutionStudents->aliasField('created') => 'DESC'])
            ->toArray();

        $institutionOptions = [];
        $gradeOptions = [];
        foreach ($institutionQuery as $key => $obj) {
            // only get the latest student status of each institution
            $institutionId = $obj->institution_id;
            if (!isset($institutionOptions[$institutionId])) {
                $institutionOptions[$institutionId] = $obj->institution_student_status;
            }

            // default grade options when no institution is selected
            if ($obj->has('education_grade')) {
                $gradeOptions[$obj->education_grade_id] = $obj->education_grade->name;
            }
        }

        $institutionOptions = ['-1' => __('All Institutions')] + $institutionOptions;
        $selectedInstitution = !is_null($this->request->query('institution_id')) ? $this->request->query('institution_id') : -1;
        $this->controller->set(compact('institutionOptions', 'selectedInstitution'));

        if ($selectedInstitution != -1) {
            $where[$this->aliasField('institution_id')] = $selectedInstitution;

            // get available grades with student status in the selected institution
            $gradeOptions = $InstitutionStudents->find('list', [
                'keyField' => 'education_grade_id',
                'valueField' => 'education_grade_student_status'
            ])
            ->contain(['StudentStatuses', 'EducationGrades'])
            ->where([
                $InstitutionStudents->aliasField('student_id') => $studentId,
                $InstitutionStudents->aliasField('academic_period_id') => $selectedAcademicPeriod,
                $InstitutionStudents->aliasField('institution_id') => $selectedInstitution
            ])
            ->order([$InstitutionStudents->aliasField('created') => 'DESC'])
            ->toArray();
        }

        $gradeOptions = ['-1' => __('All Grades')] + $gradeOptions;
        $selectedGrade = !is_null($this->request->query('education_grade_id')) ? $this->request->query('education_grade_id') : -1;
        $this->controller->set(compact('gradeOptions', 'selectedGrade'));

        if ($selectedGrade != -1) {
            $where['ClassGrades.education_grade_id'] = $selectedGrade;
        }
        // End
		
		
		$userData = $this->Session->read();
		$studentId = $userData['Auth']['User']['id'];

		if(!empty($userData['System']['User']['roles']) & !empty($userData['Student']['Students']['id'])) {

		} else {
			if (!empty($studentId)) {
				$where[$this->aliasField('student_id')] = $studentId;
			}
		}
		
        $query
            ->matching('InstitutionClasses.ClassGrades')
            ->where($where);
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        if (array_key_exists('view', $buttons)) {
            $institutionId = $entity->institution_class->institution_id;
            $url = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'Subjects',
                'view',
                $this->paramsEncode(['id' => $entity->institution_subject->id]),
                'institution_id' => $institutionId,
            ];

            if ($this->controller->name == 'Directories') {
                $url = [
                    'plugin' => 'Directory',
                    'controller' => 'Directories',
                    'action' => 'StudentSubjects',
                    'index',
                    'type' => 'student',
                    'institution_subject_id' => $entity->institution_subject->id,
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
        $this->controller->set('selectedAction', 'Subjects');
    }
}
