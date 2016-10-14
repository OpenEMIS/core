<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class SinglePromotionTable extends ControllerActionTable
{
    // private $InstitutionGrades = null;
    // private $institutionId = null;
    // private $currentPeriod = null;
    // private $statuses = []; // Student Status

    public function initialize(array $config) {
        $this->table('institution_students');
        parent::initialize($config);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        // $this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
        // $this->addBehavior('Institution.UpdateStudentStatus');
        // $this->addBehavior('Institution.ClassStudents');
        $this->addBehavior('OpenEmis.Section');
    }

    // public function addOnInitialize(Event $event, Entity $entity)
    // {
    //     // To clear the query string from the previous page to prevent logic conflict on this page
    //     $this->request->query = [];
    // }

    // public function validationDefault(Validator $validator) {
    //     $validator = parent::validationDefault($validator);

    //     return $validator
    //         ->requirePresence('from_academic_period_id')
    //         ->requirePresence('next_academic_period_id')
    //         ->requirePresence('grade_to_promote')
    //         ->requirePresence('education_grade_id')
    //         ->requirePresence('class');
    // }

    // public function implementedEvents() {
    //     $events = parent::implementedEvents();
    //     $events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    //     $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
    //     return $events;
    // }

    // public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona=false) {
    //     $url = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Students'];
    //     $Navigation->substituteCrumb('Promotion', 'Students', $url);
    //     $Navigation->addCrumb('Promotion');
    // }

    // public function beforeAction(Event $event) {
    //     $this->InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
    //     $this->institutionId = $this->Session->read('Institution.Institutions.id');
    //     $institutionClassTable = TableRegistry::get('Institution.InstitutionClasses');
    //     $this->institutionClasses = $institutionClassTable->find('list')
    //         ->where([$institutionClassTable->aliasField('institution_id') => $this->institutionId])
    //         ->toArray();
    //     $selectedPeriod = $this->AcademicPeriods->getCurrent();
    //     $this->currentPeriod = $this->AcademicPeriods->get($selectedPeriod);
    //     $this->statuses = $this->StudentStatuses->findCodeList();
    // }

    public function addOnInitialize(Event $event, Entity $entity) {
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $id = $this->Session->read($this->registryAlias().'.id');

        $Students = TableRegistry::get('Institution.Students');
        $studentData = $Students->get($id);

        $entity->id = $id;
        $entity->student_id = $studentData->student_id;
        $entity->academic_period_id = $studentData->academic_period_id;
        $entity->education_grade_id = $studentData->education_grade_id;

        // pr($entity);

        // $this->request->data[$this->alias()]['student_id'] = $entity->student_id;
        // $this->request->data[$this->alias()]['academic_period_id'] = $entity->academic_period_id;
        // $this->request->data[$this->alias()]['education_grade_id'] = $entity->education_grade_id;
        // $this->request->data[$this->alias()]['start_date'] = $entity->start_date;
        // $this->request->data[$this->alias()]['end_date'] = $entity->end_date;
        // $this->request->data[$this->alias()]['previous_institution_id'] = $entity->previous_institution_id;
        // $this->request->data[$this->alias()]['student_status_id'] = $entity->student_status_id;
    }

    private function addSections()
    {
        $this->field('existing_information_header', ['type' => 'section', 'title' => __('Promote From')]);
        $this->field('new_information_header', ['type' => 'section', 'title' => __('Promote To')]);
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->fields = [];
        $this->addSections();
        $this->field('student_id', ['entity' => $entity]);
        $this->field('from_academic_period_id', ['entity' => $entity]);
        $this->field('from_education_grade_id', ['entity' => $entity]);
        $this->field('next_academic_period_id');
        $this->field('next_education_grade_id');
        $this->field('student_status_id', ['entity' => $entity]);

        $this->setFieldOrder([
            'student_id',
            'existing_information_header', 'from_academic_period_id', 'from_education_grade_id',
            'new_information_header', 'next_academic_period_id', 'student_status_id', 'next_education_grade_id']);
    }

    public function onUpdateFieldStudentId(Event $event, array $attr, $action, Request $request)
    {
        $studentId = $attr['entity']->student_id;
        $request->data[$this->alias()]['student_id'] = $studentId;

        $attr['type'] = 'readonly';
        $attr['attr']['value'] = $this->Users->get($studentId)->name_with_id;
        return $attr;
    }

    public function onUpdateFieldFromAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        $academicPeriodId = $attr['entity']->academic_period_id;

        $attr['type'] = 'readonly';
        $attr['attr']['value'] = $this->AcademicPeriods->get($academicPeriodId)->name;
        return $attr;
    }

    public function onUpdateFieldFromEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
        $educationGradeId = $attr['entity']->education_grade_id;

        $attr['type'] = 'readonly';
        $attr['attr']['value'] = $this->EducationGrades->get($educationGradeId)->programme_grade_name;
        return $attr;
    }

    public function onUpdateFieldNextAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);

        $attr['type'] = 'select';
        $attr['options'] = $academicPeriodOptions;
        return $attr;
    }

    public function onUpdateFieldStudentStatusId(Event $event, array $attr, $action, Request $request) {
        $studentStatusesList = $this->StudentStatuses->find('list')->toArray();
        $statusCodes = $this->StudentStatuses->findCodeList();
        $statusOptions = [];

        $educationGradeId = $attr['entity']->education_grade_id;
        $nextGrades = $this->EducationGrades->getNextAvailableEducationGrades($educationGradeId, false);

        // If there is no more next grade in the same education programme then the student may be graduated
        if (count($nextGrades) == 0) {
            $statusOptions[$statusCodes['GRADUATED']] = __($studentStatusesList[$statusCodes['GRADUATED']]);
        } else {
            $statusOptions[$statusCodes['PROMOTED']] = __($studentStatusesList[$statusCodes['PROMOTED']]);
        }

        $statusOptions[$statusCodes['REPEATED']] = __($studentStatusesList[$statusCodes['REPEATED']]);

        $attr['options'] = $statusOptions;
        $attr['onChangeReload'] = 'changeStudentStatus';
        return $attr;
    }

    public function onUpdateFieldNextEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
        $studentStatusId = $request->query('student_status');

        if (!empty($studentStatusId)) {
            $statuses = $this->StudentStatuses->findCodeList();

            if (!in_array($studentStatusId, [$statuses['REPEATED']])) {
                $educationGradeId = $request->query('grade_to_promote');
                $institutionId = $this->institutionId;

                // list of grades available to promote to
                $listOfGrades = $this->EducationGrades->getNextAvailableEducationGrades($educationGradeId);

                // list of grades available in the institution
                $today = date('Y-m-d');
                $listOfInstitutionGrades = $this->InstitutionGrades
                    ->find('list', [
                        'keyField' => 'education_grade_id',
                        'valueField' => 'education_grade.programme_grade_name'])
                    ->contain(['EducationGrades.EducationProgrammes'])
                    ->where([
                        $this->InstitutionGrades->aliasField('institution_id') => $institutionId,
                        'OR' => [
                            [
                                $this->InstitutionGrades->aliasField('end_date IS NULL'),
                                $this->InstitutionGrades->aliasField('start_date <= ') => $today
                            ],
                            [
                                $this->InstitutionGrades->aliasField('end_date IS NOT NULL'),
                                $this->InstitutionGrades->aliasField('start_date <= ') => $today,
                                $this->InstitutionGrades->aliasField('end_date >= ') => $today
                            ]
                        ]
                    ])
                    ->order(['EducationProgrammes.order', 'EducationGrades.order'])
                    ->toArray();

                // Only display the options that are available in the institution and also linked to the current programme
                $options = array_intersect_key($listOfInstitutionGrades, $listOfGrades);

                if (count($options) == 0) {
                    $attr['select'] = false;
                    $options = [0 => $this->getMessage($this->aliasField('noAvailableGrades'))];
                }
                $attr['type'] = 'select';
                $attr['options'] = $options;
            } else {
                $attr['type'] = 'readonly';
            }
        } else {
            $attr['type'] = 'readonly';
            $attr['attr']['value'] = '';
        }

        return $attr;
    }
}