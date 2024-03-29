<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Cake\Controller\Component;
use Cake\I18n\Date;
use App\Model\Table\ControllerActionTable;
use Workflow\Model\Behavior\WorkflowBehavior;
use Cake\Log\Log;
use Cake\Datasource\ResultSetInterface;//POCOR-6982

class StudentTransferTable extends ControllerActionTable
{
    private $Grades = null;
    private $GradeStudents = null;
    private $StudentTransfers = null;
    private $Students = null;

    private $institutionClasses = null;
    private $institutionId = null;
    private $currentPeriod = null;
    private $statuses = [];	// Student Status

    public function initialize(array $config)
    {
        $this->table('institution_students');
        parent::initialize($config);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('PreviousInstitutionStudents', ['className' => 'Institution.Students', 'foreignKey' => 'previous_institution_student_id']);

        $this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
        $this->addBehavior('Institution.ClassStudents');

        $this->toggle('index', false);
        $this->toggle('edit', false);
        $this->toggle('view', false);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->requirePresence('from_academic_period_id')
            ->requirePresence('class')
            ->requirePresence('assignee_id')
            ->requirePresence('education_grade_id')
            ->notEmpty('education_grade_id', 'This field is required.')
            ->requirePresence('next_academic_period_id')
            ->notEmpty('next_academic_period_id', 'This field is required.')
            ->requirePresence('next_education_grade_id')
            ->notEmpty('next_education_grade_id', 'This field is required.')
            ->requirePresence('next_institution_id')
            ->notEmpty('next_institution_id', 'This field is required.')
            ->requirePresence('student_transfer_reason_id')
            ->notEmpty('student_transfer_reason_id', 'This field is required.');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        return $events;
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona)
    {
        $url = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Students'];
        $Navigation->substituteCrumb('Transfer', 'Students', $url);
        $Navigation->addCrumb('Transfer');
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->Grades = TableRegistry::get('Institution.InstitutionGrades');
        $this->GradeStudents = TableRegistry::get('Institution.StudentTransfer');
        $this->StudentTransfers = TableRegistry::get('Institution.InstitutionStudentTransfers');
        $this->Students = TableRegistry::get('Institution.Students');

        $institutionClassTable = TableRegistry::get('Institution.InstitutionClasses');
        $this->institutionId = $this->Session->read('Institution.Institutions.id');
        $this->institutionClasses = $institutionClassTable->find('list')
            ->where([$institutionClassTable->aliasField('institution_id') => $this->institutionId])
            ->toArray();
        $this->statuses = $this->StudentStatuses->findCodeList();
        // set back button url
        $extra['toolbarButtons']['back']['url'] = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'Students',
            '0' => 'index'
        ];
    }

    public function addOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        // To clear the query string from the previous page to prevent logic conflict on this page
        $this->request->query = [];
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('student_status_id');//POCOR-6230
        $this->field('student_id', ['visible' => false]);
        $this->field('start_date', ['visible' => false]);
        $this->field('end_date', ['visible' => false]);
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('from_academic_period_id');
        $this->field('education_grade_id');
        $this->field('class');
        $this->field('next_academic_period_id');
        $this->field('next_education_grade_id');
        $this->field('area_id');
        $this->field('next_institution_id');
        $this->field('student_transfer_reason_id');
        $this->field('students');
        $this->field('assignee_id');
        $this->setFieldOrder([
            'student_status_id','from_academic_period_id', 'education_grade_id', 'class',
            'next_academic_period_id', 'next_education_grade_id', 'area_id', 'next_institution_id', 'student_transfer_reason_id','assignee_id'
        ]); //POCOR-6230 add student_status_id in setFieldOrder
    }
    //POCOR-6230 Starts
    public function onUpdateFieldStudentStatusId(Event $event, array $attr, $action, Request $request)
    {
        $Status = $this->StudentStatuses->findCodeList();
        $statusNames = $this->StudentStatuses->find('list')->where([$this->StudentStatuses->aliasField('id IN ') => [$Status['CURRENT'],$Status['PROMOTED'],$Status['GRADUATED']]])->toArray();//comment graduated status because we will work on it in next ticket in future as per umairah says
        $attr['options'] = $statusNames;
        $attr['onChangeReload'] = true;
        return $attr;
    }//POCOR-6230 Ends

    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
//        $this->log('addBeforeSave', 'debug');
//        $this->log($requestData[$this->alias()], 'debug');

        if (array_key_exists($this->alias(), $requestData)) {
            $nextAcademicPeriodId = null;
            $currentAcademicPeriodId = null;
            $nextEducationGradeId = null;
            $nextInstitutionId = null;
            $studentTransferReasonId = null;
            $currentEducationGradeId = null;
            $AssigneeId = null;

            if (array_key_exists('next_academic_period_id', $requestData[$this->alias()])) {
                $nextAcademicPeriodId = $requestData[$this->alias()]['next_academic_period_id'];
            }
            if (array_key_exists('from_academic_period_id', $requestData[$this->alias()])) {
                $currentAcademicPeriodId = $requestData[$this->alias()]['from_academic_period_id'];
            }
            if (array_key_exists('next_education_grade_id', $requestData[$this->alias()])) {
                $nextEducationGradeId = $requestData[$this->alias()]['next_education_grade_id'];
            }
            if (array_key_exists('next_institution_id', $requestData[$this->alias()])) {
                $nextInstitutionId = $requestData[$this->alias()]['next_institution_id'];
            }
            if (array_key_exists('student_transfer_reason_id', $requestData[$this->alias()])) {
                $studentTransferReasonId = $requestData[$this->alias()]['student_transfer_reason_id'];
            }
            if (array_key_exists('education_grade_id', $requestData[$this->alias()])) {
                $currentEducationGradeId = $requestData[$this->alias()]['education_grade_id'];
            }
            if (array_key_exists('assignee_id', $requestData[$this->alias()])) {
                $AssigneeId = $requestData[$this->alias()]['assignee_id'];
            }

            if (!empty($nextAcademicPeriodId)
                && !empty($AssigneeId)
                && !empty($currentAcademicPeriodId)
                && !empty($nextEducationGradeId)
                && !empty($nextInstitutionId)
                && !empty($studentTransferReasonId)
                && !empty($currentEducationGradeId)) {
                if (array_key_exists('students', $requestData[$this->alias()])) {
                    $StudentTransferOut = TableRegistry::get('Institution.StudentTransferOut');
                    $institutionId = $requestData[$this->alias()]['institution_id'];

                    $tranferCount = 0;
                    foreach ($requestData[$this->alias()]['students'] as $key => $studentObj) {
                        if (isset($studentObj['selected']) && $studentObj['selected']) {
                            unset($studentObj['selected']);
                            $studentObj['status_id'] = WorkflowBehavior::STATUS_OPEN;
                            $studentObj['institution_id'] = $nextInstitutionId;
                            $studentObj['academic_period_id'] = $nextAcademicPeriodId;
                            $studentObj['education_grade_id'] = $nextEducationGradeId;
                            $studentObj['previous_institution_id'] = $institutionId;
                            $studentObj['previous_academic_period_id'] = $currentAcademicPeriodId;
                            $studentObj['previous_education_grade_id'] = $currentEducationGradeId;
                            $studentObj['student_transfer_reason_id'] = $studentTransferReasonId;
                            $studentObj['assignee_id'] = $AssigneeId;

                            $nextPeriod = $this->AcademicPeriods->get($nextAcademicPeriodId);
                            $studentObj['requested_date'] = new Date();

                            $entity = $StudentTransferOut->newEntity($studentObj, ['validate' => 'bulkTransfer']);
                            if ($StudentTransferOut->save($entity)) {
                                $tranferCount++;
                            } else {
                                $this->log($this->alias() . $entity . print_r($entity->errors(), true), 'error');
                                $this->Alert->error('general.add.failed', ['reset' => true]);
                            }
                        }
                    }

                    if ($tranferCount == 0) {
                        $this->Alert->error('general.notSelected');
                    } else {
                        $this->Alert->success($this->aliasField('success'), ['reset' => true]);
                        $url = $this->url('add');
                        $event->stopPropagation();
                        return $this->controller->redirect($url);
                    }
                }
            }
        }
//        $this->log('addBeforeSaveAfter', 'debug');

    }

    /**
     * @param Event $event
     * @param array $attr
     * @param $action
     * @param Request $request
     * @return array
     * @author of fixes Dr. Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function onUpdateFieldFromAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($request->data[$this->alias()]['from_academic_period_id'])) {
            $fromAcademicPeriodId = $request->data[$this->alias()]['from_academic_period_id'];
            if (!empty($fromAcademicPeriodId)) {
                $this->currentPeriod = $this->AcademicPeriods->get($fromAcademicPeriodId);
            } else {
                $this->currentPeriod = null;
            }
        } else {
            $this->currentPeriod = null;
        }
        $selectedAcademicPeriodId = null;
        if($this->currentPeriod){
            $selectedAcademicPeriodId = $this->currentPeriod->id;
        }
//        $InstitutionGrades = $this->Grades;
        $yearOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $InstitutionStudents = $this;
        $institutionId = $this->institutionId;
        $selectedStudentStatusId = $request['data']['StudentTransfer']['student_status_id'];
        $this->advancedSelectOptions($yearOptions, $selectedAcademicPeriodId, [
            'selectOption' => false,
            'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStudents')),
            'callable' => function($key) use ($InstitutionStudents,
                $institutionId,
                $selectedStudentStatusId) {
                $where = [
                    $InstitutionStudents->aliasField('institution_id') => $institutionId,
                    $InstitutionStudents->aliasField('academic_period_id') => $key,
                    $InstitutionStudents->aliasField('student_status_id') => $selectedStudentStatusId,
                ];
                $gradeStudentsCounter = $InstitutionStudents
                    ->find()
                    /*POCOR-6544 starts*/
//                            ->matching('StudentStatuses', function ($q) {
//                                return $q->where(['StudentStatuses.code NOT IN ' => ['TRANSFERRED', 'WITHDRAWN']]);
//                            })
                    /*POCOR-6544 ends*/
                    ->where($where)
                    ->count();
//                            $this->log('$gradeStudentsCounter', 'debug');
//                            $this->log($gradeStudentsCounter, 'debug');
//                            $this->log($where, 'debug');
                return $gradeStudentsCounter;
            }
        ]);
        $attr['type'] = 'select';
        $attr['options'] = $yearOptions;
        $attr['onChangeReload'] = 'ChangeFromAcademicPeriod';

        return $attr;
    }

    /* public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
     {
         $gradeOptions = [];

         if (!is_null($this->currentPeriod)) {
             $Grades = $this->Grades;
             $GradeStudents = $this->GradeStudents;
             $StudentTransfers = $this->StudentTransfers;
             $Students = $this->Students;

             $institutionId = $this->institutionId;
             $selectedPeriod = $this->currentPeriod->id;
             $statuses = $this->statuses;

             $gradeOptions = $Grades
                 ->find('list', ['keyField' => 'education_grade_id', 'valueField' => 'education_grade.programme_grade_name'])
                 ->contain(['EducationGrades'])
                 ->where([$Grades->aliasField('institution_id') => $institutionId])
                 ->find('academicPeriod', ['academic_period_id' => $selectedPeriod])
                 ->toArray();

             $selectedGrade = $request->query('education_grade_id');
             $pendingTransferStatuses = $this->StudentTransfers->getStudentTransferWorkflowStatuses('PENDING');

             $this->advancedSelectOptions($gradeOptions, $selectedGrade, [
                 'selectOption' => false,
                 'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStudents')),
                 'callable' => function($id) use ($GradeStudents, $StudentTransfers, $Students, $pendingTransferStatuses, $institutionId, $selectedPeriod, $statuses) {

                      return $GradeStudents
                         ->find()
                         ->leftJoin(
                             [$StudentTransfers->alias() => $StudentTransfers->table()],
                             [
                                 $StudentTransfers->aliasField('student_id = ') . $GradeStudents->aliasField('student_id'),
                                 $StudentTransfers->aliasField('status_id IN ') => $pendingTransferStatuses
                             ]
                         )
                         ->leftJoin(
                             [$Students->alias() => $Students->table()],
                             [
                                 $Students->aliasField('student_id = ') . $GradeStudents->aliasField('student_id'),
                                 $Students->aliasField('student_status_id') => $statuses['CURRENT']
                             ]
                         )
                         ->where([
                             $this->aliasField('institution_id') => $institutionId,
                             $this->aliasField('academic_period_id') => $selectedPeriod,
                             $this->aliasField('education_grade_id') => $id,
                             $this->aliasField('student_status_id IN') => [$statuses['PROMOTED'], $statuses['GRADUATED']],
                             $StudentTransfers->aliasField('student_id IS') => NULL,
                             $Students->aliasField('student_id IS') => NULL
                         ])
                         ->count();
                 }
             ]);
         }

         $attr['options'] = $gradeOptions;
         $attr['onChangeReload'] = 'changeGrade';

         return $attr;
     } */
    /**
     * @param Event $event
     * @param array $attr
     * @param $action
     * @param Request $request
     * @return array
     * @author of fixes Dr. Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
        $entity = $attr['entity'];
        $selectedAcademicPeriodId = $this->currentPeriod->id;
        $InstitutionGrades = $this->Grades;
        $gradeOptions = [];
        if (!empty($selectedAcademicPeriodId) && $selectedAcademicPeriodId != -1) {
            $institutionId = $this->institutionId;
            $selectedStudentStatusId = $request['data']['StudentTransfer']['student_status_id'];
            $gradeOptions = $InstitutionGrades
                ->find('list', ['keyField' => 'education_grade_id', 'valueField' => 'education_grade.programme_grade_name'])
                //->contain(['EducationGrades.EducationProgrammes', 'EducationGrades.EducationStages'])
                ->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems', 'EducationGrades.EducationStages'])
                ->where([
                    'EducationSystems.academic_period_id' => $selectedAcademicPeriodId,
                    'EducationProgrammes.visible' => 1 //POCOR-6498
                ])
                ->where([$InstitutionGrades->aliasField('institution_id') => $institutionId])
                //->find('academicPeriod', ['academic_period_id' => $selectedPeriod])
                ->order(['EducationStages.order', 'EducationGrades.order'])
                ->toArray();

            $attr['type'] = 'select';
            $selectedGrade = null;
            $InstitutionStudents = $this;
            $counter = 0;

            $this->advancedSelectOptions($gradeOptions, $selectedGrade, [
                'selectOption' => false,
                'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStudents')),
                'callable' => function($key) use ($InstitutionStudents,
                    $institutionId,
                    $selectedAcademicPeriodId,
                    $selectedStudentStatusId) {
                    $where = [
                        $InstitutionStudents->aliasField('institution_id') => $institutionId,
                        $InstitutionStudents->aliasField('academic_period_id') => $selectedAcademicPeriodId,
                        $InstitutionStudents->aliasField('education_grade_id') => $key,
                        $InstitutionStudents->aliasField('student_status_id') => $selectedStudentStatusId,
                    ];
                    $gradeStudentsCounter = $InstitutionStudents
                        ->find()
                        /*POCOR-6544 starts*/
//                            ->matching('StudentStatuses', function ($q) {
//                                return $q->where(['StudentStatuses.code NOT IN ' => ['TRANSFERRED', 'WITHDRAWN']]);
//                            })
                        /*POCOR-6544 ends*/
                        ->where($where)
                        ->count();
//                            $this->log('$gradeStudentsCounter', 'debug');
//                            $this->log($gradeStudentsCounter, 'debug');
//                            $this->log($where, 'debug');
                    return $gradeStudentsCounter;
                }
            ]);

            foreach ($gradeOptions as $key=>$value) {
                $where = [
                    $InstitutionStudents->aliasField('institution_id') => $institutionId,
                    $InstitutionStudents->aliasField('academic_period_id') => $selectedAcademicPeriodId,
                    $InstitutionStudents->aliasField('education_grade_id') => $key,
                    $InstitutionStudents->aliasField('student_status_id') => $selectedStudentStatusId,
                ];
                $gradeStudentsCounter = $InstitutionStudents
                    ->find()
                    /*POCOR-6544 starts*/
//                    ->matching('StudentStatuses', function ($q) {
//                        return $q->where(['StudentStatuses.code NOT IN ' => ['TRANSFERRED', 'WITHDRAWN']]);
//                    })
                    /*POCOR-6544 ends*/
                    ->where($where)
                    ->count();
                $counter += $gradeStudentsCounter;
            }
            if ($counter == 0) {
                $attr['attr']['value'] = "";
            }
        }
        $attr['onChangeReload'] = 'changeGrade';
        $attr['options'] = $gradeOptions;
        return $attr;
    }

    public function onUpdateFieldNextAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        $nextPeriodOptions = [];
        if (!is_null($this->currentPeriod)) {
            $Grades = $this->Grades;
            $institutionId = $this->institutionId;
            $selectedPeriod = $this->currentPeriod->id;
            $periodLevelId = $this->currentPeriod->academic_period_level_id;
            $startDate = $this->currentPeriod->start_date->format('Y-m-d');
            //POCOR-6982 Starts
            $nexteducationgradeforenrolledStatus= false;
            $selectedStudentStatus = $request['data']['StudentTransfer']['student_status_id'];
            if($selectedStudentStatus == 1){//student_status_id is Enrolled
                $where = [
                    $this->AcademicPeriods->aliasField('id') => $selectedPeriod,
                    $this->AcademicPeriods->aliasField('academic_period_level_id') => $periodLevelId,
                    $this->AcademicPeriods->aliasField('start_date >=') => $startDate
                ];
            }else{//student_status_id is Promoted or Graduated
                $where = [
                    $this->AcademicPeriods->aliasField('id <>') => $selectedPeriod,
                    $this->AcademicPeriods->aliasField('academic_period_level_id') => $periodLevelId,
                    $this->AcademicPeriods->aliasField('start_date >=') => $startDate
                ];
            }//POCOR-6982 Ends

            $nextPeriodOptions = $this->AcademicPeriods
                ->find('list')
                ->find('visible')
                ->find('editable', ['isEditable' => true])
                ->find('order')
                ->where($where)
                ->toArray();

            $nextPeriodId = $request->query('next_academic_period_id');
            $this->advancedSelectOptions($nextPeriodOptions, $nextPeriodId, [
                'selectOption' => false,
                'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noGrades')),
                'callable' => function($id) use ($Grades, $institutionId) {
                    return $Grades
                        ->find()
                        ->where([$Grades->aliasField('institution_id') => $institutionId])
                        ->find('academicPeriod', ['academic_period_id' => $id])
                        ->count();
                }
            ]);
        }

        $attr['options'] = $nextPeriodOptions;
        $attr['onChangeReload'] = 'changeNextPeriod';
        return $attr;
    }

    public function onUpdateFieldNextEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
        $selectedGrade = $request->query('education_grade_id');
        if (array_key_exists('next_academic_period_id', $request->query)) {
            $nextPeriodId = $request->query('next_academic_period_id');
        } else {
            $nextPeriodId = $request->data[$this->alias()]['next_academic_period_id'];
        }
        //POCOR-6230 Starts
        $nexteducationgradeforenrolledStatus= false;
        $selectedStudentStatus = $this->request['data']['StudentTransfer']['student_status_id'];
        if($selectedStudentStatus == 1){
            $nexteducationgradeforenrolledStatus= true;
        }//POCOR-6230 Ends

        $nextGradeOptions = [];
        if (!empty($selectedGrade) && !empty($nextPeriodId)) {
            /*POCOR-6498 starts*/
            $isLastGrade = $this->EducationGrades->isLastGradeInEducationProgrammes($selectedGrade);
            if ($isLastGrade) {
                $nextGradeOptions = $this->EducationGrades->getNextEducationGrades($selectedGrade, $nextPeriodId, true, true, $nexteducationgradeforenrolledStatus);//POCOR-6998 starts
            } else {
                $nextGradeOptions = $this->EducationGrades->getNextEducationGradesForTransfer($selectedGrade,$nextPeriodId,true,true,$nexteducationgradeforenrolledStatus);//POCOR-6230
            }
            /*POCOR-6498 ends*/
            $gradeResult = $nextGradeOptions;
            $nextGradeId = $request->query('next_education_grade_id');
            if (is_null($nextPeriodId)) {
                $nextGradeId = key($nextGradeOptions);
                $this->advancedSelectOptions($nextGradeOptions, $nextGradeId);
            } else {
                $Institutions = $this->Institutions;
                $Grades = $this->Grades;
                $institutionId = $this->institutionId;

                $nextPeriodData = $this->AcademicPeriods->get($nextPeriodId);
                if ($nextPeriodData->start_date instanceof Time || $nextPeriodData->start_date instanceof Date) {
                    $nextPeriodStartDate = $nextPeriodData->start_date->format('Y-m-d');
                } else {
                    $nextPeriodStartDate = date('Y-m-d', strtotime($nextPeriodData->start_date));
                }
            }
            $this->request->query['next_education_grade_id'] = $nextGradeId;
        }

        $attr['options'] = $gradeResult;
        $attr['onChangeReload'] = 'changeNextGrade';
        return $attr;
    }

    public function getGrandEducationOptions() {
        $gradeOptions = [];
        $Grades = $this->Grades;
        $GradeStudents = $this->GradeStudents;
        $StudentTransfers = $this->StudentTransfers;
        $Students = $this->Students;

        $institutionId = $this->institutionId;
        $selectedPeriod = $this->currentPeriod->id;
        $statuses = $this->statuses;
        $gradeOptions = $Grades
            ->find('list', ['keyField' => 'education_grade_id', 'valueField' => 'education_grade.programme_grade_name'])
            ->contain(['EducationGrades'])
            ->where([$Grades->aliasField('institution_id') => $institutionId])
            ->find('academicPeriod', ['academic_period_id' => $selectedPeriod])
            ->toArray();

        $selectedGrade = $this->request->query('education_grade_id');
        $pendingTransferStatuses = $this->StudentTransfers->getStudentTransferWorkflowStatuses('PENDING');

        $this->advancedSelectOptions($gradeOptions, $selectedGrade, [
            'selectOption' => false,
            'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStudents')),
            'callable' => function($id) use ($GradeStudents, $StudentTransfers, $Students, $pendingTransferStatuses, $institutionId, $selectedPeriod, $statuses) {
                return $GradeStudents
                    ->find()
                    ->leftJoin(
                        [$StudentTransfers->alias() => $StudentTransfers->table()],
                        [
                            $StudentTransfers->aliasField('student_id = ') . $GradeStudents->aliasField('student_id'),
                            $StudentTransfers->aliasField('status_id IN ') => $pendingTransferStatuses
                        ]
                    )
                    ->leftJoin(
                        [$Students->alias() => $Students->table()],
                        [
                            $Students->aliasField('student_id = ') . $GradeStudents->aliasField('student_id'),
                            $Students->aliasField('student_status_id') => $statuses['CURRENT']
                        ]
                    )
                    ->where([
                        $this->aliasField('institution_id') => $institutionId,
                        $this->aliasField('academic_period_id') => $selectedPeriod,
                        $this->aliasField('education_grade_id') => $id,
                        $this->aliasField('student_status_id IN') => [$statuses['PROMOTED'], $statuses['GRADUATED']],
                        $StudentTransfers->aliasField('student_id IS') => NULL,
                        $Students->aliasField('student_id IS') => NULL
                    ])
                    ->count();
            }
        ]);

        if ($gradeOptions) {
            foreach ($gradeOptions as $key => $gradeVal) {
                if (($keyVal = array_search($gradeVal[0], $gradeVal)) == 'disabled') {
                    unset($gradeOptions[$key]);
                }
            }
        }
        return $gradeOptions;
    }

    /**
     * @param Event $event
     * @param array $attr
     * @param $action
     * @param Request $request
     * @return array
     * @author of fixes Dr. Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function onUpdateFieldAreaId(Event $event, array $attr, $action, Request $request) {
        $next_period_id = $request->query('next_academic_period_id');
        $next_grade_id = $request->query('next_education_grade_id');
        $areaOptions = [];

        if (!is_null($next_period_id) && !is_null($next_grade_id)) {
            $Institutions = TableRegistry::get('institutions');
            $InstitutionGrades = TableRegistry::get('institution_grades');
            $InstitutionStatuses = TableRegistry::get('Institution.Statuses');
            $activeId = $InstitutionStatuses->getIdByCode('ACTIVE');
            $institution_id = $this->institutionId;

            $nextPeriodData = $this->AcademicPeriods->get($next_period_id);
            if ($nextPeriodData->start_date instanceof Time) {
                $nextPeriodStartDate = $nextPeriodData->start_date->format('Y-m-d');
            } else {
                $nextPeriodStartDate = date('Y-m-d', strtotime($nextPeriodData->start_date));
            }

            $Areas = $this->Institutions->Areas;
            $areaOptions = $Areas
                ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                ->innerJoin([$Institutions->alias() => $Institutions->table()],
                    [$Institutions->aliasField('area_id = ') . $Areas->aliasField('id')])
                ->innerJoin([$InstitutionGrades->alias() => $InstitutionGrades->table()],
                    [$InstitutionGrades->aliasField('institution_id = ') . $Institutions->aliasField('id')])
                ->where([
                    $InstitutionGrades->aliasField('institution_id <>') => $institution_id,
                    $InstitutionGrades->aliasField('education_grade_id') => $next_grade_id,
                    $InstitutionGrades->aliasField('start_date >=') => $nextPeriodStartDate,
                    $Institutions->aliasField('institution_status_id') =>
                        $activeId,
                    'OR' => [
                        $InstitutionGrades->aliasField('end_date IS NULL'),
                        $InstitutionGrades->aliasField('end_date >=') => $nextPeriodStartDate
                    ]
                ])
                ->orderAsc($Areas->aliasField('parent_id'))
                ->orderAsc($Areas->aliasField('order'))->toArray()
            ;
        }

        $attr['type'] = 'chosenSelect';
        $attr['attr']['multiple'] = false;
        $attr['select'] = true;
        $attr['options'] = $areaOptions;
        $attr['onChangeReload'] = true;

        return $attr;
    }

    /**
     * @param Event $event
     * @param array $attr
     * @param $action
     * @param Request $request
     * @return array
     * @author of fixes Dr. Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function onUpdateFieldNextInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        //bulk student
        $next_period_id = $request->query('next_academic_period_id');
        $next_grade_id = $request->query('next_education_grade_id');
        $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
        $InstitutionStatuses = TableRegistry::get('Institution.Statuses');

        $institution_id = $this->institutionId;

        $institutionOptions = [];
        $area_id = $request->data[$this->alias()]['area_id'];

        if (!is_null($next_period_id) && !is_null($next_grade_id)) {

            $nextPeriodData = $this->AcademicPeriods->get($next_period_id);
            if ($nextPeriodData->start_date instanceof Time) {
                $nextPeriodStartDate = $nextPeriodData->start_date->format('Y-m-d');
            } else {
                $nextPeriodStartDate = date('Y-m-d', strtotime($nextPeriodData->start_date));
            }

            $institutionQuery = $this->Institutions
                ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                ->join([
                    'table' => $InstitutionGrades->table(),
                    'alias' => $InstitutionGrades->alias(),
                    'conditions' => [
                        $InstitutionGrades->aliasField('institution_id = ') .
                        $this->Institutions->aliasField('id'),
                        $InstitutionGrades->aliasField('education_grade_id') => $next_grade_id,
                        $InstitutionGrades->aliasField('start_date >=') => $nextPeriodStartDate,
                        'OR' => [
                            $InstitutionGrades->aliasField('end_date IS NULL'),
                            $InstitutionGrades->aliasField('end_date >=') => $nextPeriodStartDate
                        ]
                    ]
                ])
                ->where([
                    $this->Institutions->aliasField('id <>') => $institution_id,
                    $this->Institutions->aliasField('institution_status_id') =>
                        $InstitutionStatuses->getIdByCode('ACTIVE')
                ])
                ->orderAsc($this->Institutions->aliasField('code'));

            if (!empty($area_id)) {
                $institutionQuery->where([$this->Institutions->aliasField('area_id')
                => $area_id]);
            }
            $institutionOptions = $institutionQuery->toArray();
        }

        $attr['attr']['label'] = __('Institution');
        $attr['type'] = 'chosenSelect';
        $attr['attr']['multiple'] = false;
        $attr['select'] = true;
        $attr['options'] = $institutionOptions;
        $attr['onChangeReload'] = true;

        return $attr;
    }

    public function onUpdateFieldStudentTransferReasonId(Event $event, array $attr, $action, Request $request)
    {
        $StudentTransferReasons = TableRegistry::get('Student.StudentTransferReasons');
        $attr['options'] = $StudentTransferReasons->getList()->toArray();
        return $attr;
    }

    public function onUpdateFieldStudents(Event $event, array $attr, $action, Request $request)
    {
        $institutionId = $this->institutionId;
        $selectedGrade = $request->query('education_grade_id');
        $selectedClass = $request->query('institution_class');
        $nextEducationGradeId = $request->query('next_education_grade_id');

        $students = [];
        if (!empty($selectedGrade) && !is_null($this->currentPeriod)) {
            $selectedPeriod = $this->currentPeriod->id;
            $GradeStudents = $this->GradeStudents;
            $statuses = $this->statuses;

            $studentQuery = $this
                ->find('byNoExistingTransferRequest')
                //->find('byNoEnrolledRecord')
                //->find('byNotCompletedGrade', ['gradeId' => $nextEducationGradeId])
                ->find('byStatus', ['statuses' => [$statuses['CURRENT'], $statuses['PROMOTED'], $statuses['GRADUATED']]])//POCOR-6230 add $statuses['CURRENT']
                ->find('studentClasses', ['institution_class_id' => $selectedClass])
                ->select(['institution_class_id' => 'InstitutionClassStudents.institution_class_id'])
                ->matching('Users.Genders')
                ->where([
                    $this->aliasField('institution_id') => $institutionId,
                    $this->aliasField('academic_period_id') => $selectedPeriod,
                    $this->aliasField('education_grade_id') => $selectedGrade
                ])
                ->group($this->aliasField('student_id'))
                ->order(['Users.first_name'])
                //POCOR-6982 Starts
                ->formatResults(function (ResultSetInterface $res){
                    return $res->map(function ($row){
                        $studentId = $row->student_id;
                        $institutionId = $row->institution_id;
                        $academicPeriodId = $row->academic_period_id;
                        $InstitutionStudents = TableRegistry::get('institution_students');
                        $StudentRecords = $InstitutionStudents
                            ->find()
                            ->where([
                                $InstitutionStudents->aliasField('student_id') => $studentId,
                                $InstitutionStudents->aliasField('institution_id') => $institutionId,
                                $InstitutionStudents->aliasField('academic_period_id >') => $academicPeriodId,
                                $InstitutionStudents->aliasField('student_status_id') => 1
                            ])
                            ->count();
                        //POCOR-7007 Starts
                        if($StudentRecords == 1){
                            $row['student_already_enrolled_in_same_institution'] = 1;
                            $row['student_already_enrolled_in_other_institution'] = 0;
                        }else{
                            $StudentEnrollRecords = $InstitutionStudents
                                ->find()
                                ->where([
                                    $InstitutionStudents->aliasField('student_id') => $studentId,
                                    $InstitutionStudents->aliasField('academic_period_id >') => $academicPeriodId,
                                    $InstitutionStudents->aliasField('student_status_id') => 1
                                ])
                                ->count();
                            if($StudentEnrollRecords == 1){ //this condition check for graduate student who is enrolled in other institution
                                $row['student_already_enrolled_in_same_institution'] = 0;
                                $row['student_already_enrolled_in_other_institution'] = 1;
                            }else{
                                $row['student_already_enrolled_in_same_institution'] = 0;
                                $row['student_already_enrolled_in_other_institution'] = 0;
                            }
                        }//POCOR-7007 Ends
                        return $row;
                    });
                })//POCOR-6982 Ends
                ->autoFields(true);
            $students = $studentQuery->toArray();
            if (empty($students)) {
                $this->Alert->warning($this->aliasField('noData'));
            }
        }

        if (!empty($request->data[$this->alias()]['next_institution_id'])) {
            $nextInstitutionId = $request->data[$this->alias()]['next_institution_id'];
            $institutionGender = $this->Institutions
                ->find()
                ->contain('Genders')
                ->where([
                    $this->Institutions->aliasField('id') => $nextInstitutionId
                ])
                ->select([
                    'Genders.code',
                    'Genders.name'
                ])
                ->first();
            $attr['nextInstitutionGender'] = $institutionGender->Genders->name;
            $attr['nextInstitutionGenderCode'] = $institutionGender->Genders->code;
        }

        $statusOptions = $this->StudentStatuses->find('list')->toArray();
        $attr['type'] = 'element';
        $attr['element'] = 'Institution.StudentTransfer/students';
        $attr['attr']['statusOptions'] = $statusOptions;
        $attr['data'] = $students;
        $attr['classOptions'] = $this->institutionClasses;
        return $attr;
    }

    public function onUpdateFieldClass(Event $event, array $attr, $action, Request $request)
    {
        $attr['type'] = 'select';
        $attr['options'] = [];
        if (!is_null($this->currentPeriod)) {
            $institutionClass = TableRegistry::get('Institution.InstitutionClasses');
            $institutionId = $this->institutionId;
            $selectedPeriod = $this->currentPeriod->id;
            $educationGradeId = $request->query('education_grade_id');

            $classes = $institutionClass
                ->find('list')
                ->innerJoinWith('ClassGrades')
                ->where([
                    $institutionClass->aliasField('academic_period_id') => $selectedPeriod,
                    $institutionClass->aliasField('institution_id') => $institutionId,
                    'ClassGrades.education_grade_id' => $educationGradeId
                ])
                ->toArray();
            $options = ['-1' => __('Students without Class')] + $classes;

            $selectedClass = $request->query('institution_class');
            if (empty($selectedClass)) {
                if (!empty($classes)) {
                    $selectedClass = key($classes);
                }
            }

            $this->advancedSelectOptions($options, $selectedClass);
            $request->query['institution_class'] = $selectedClass;

            $attr['options'] = $options;
            $attr['select'] = false;
            $attr['onChangeReload'] = 'changeClass';
        }

        return $attr;
    }

    public function addOnChangeClass(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        unset($this->request->query['institution_class']);

        if ($this->request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $data)) {
                if (array_key_exists('class', $data[$this->alias()])) {
                    $this->request->query['institution_class'] = $data[$this->alias()]['class'];
                }
            }
        }
    }

    public function findByNoExistingTransferRequest(Query $query, array $options)
    {
        $StudentTransfers = $this->StudentTransfers;
        $pendingTransferStatuses = $this->StudentTransfers->getStudentTransferWorkflowStatuses('PENDING');
        $query->leftJoin(
            [$StudentTransfers->alias() => $StudentTransfers->table()],
            [
                $StudentTransfers->aliasField('student_id = ') . $this->aliasField('student_id'),
                $StudentTransfers->aliasField('status_id IN ') => $pendingTransferStatuses
            ]
        )
            ->where([$StudentTransfers->aliasField('student_id IS') => NULL]);

        return $query;
    }

    public function findByNoEnrolledRecord(Query $query, array $options) {
        $Students = $this->Students;
        $statuses = $this->statuses;
        $query->leftJoin(
            ['StudentEnrolledRecord' => $Students->table()],
            [
                'StudentEnrolledRecord.student_id = ' . $this->aliasField('student_id'),
                'StudentEnrolledRecord.student_status_id' => $statuses['CURRENT']
            ]
        )
            ->where(['StudentEnrolledRecord.student_id IS' => NULL]);

        return $query;
    }

    public function findByNotCompletedGrade(Query $query, array $options) {
        $gradeId = array_key_exists('gradeId', $options)? $options['gradeId']: null;
        if (empty($gradeId)) {
            return $query;
        }

        $Students = $this->Students;
        $statuses = $this->statuses;
        $query->leftJoin(
            ['StudentCompletedGrade' => $Students->table()],
            [
                'StudentCompletedGrade.student_id = ' . $this->aliasField('student_id'),
                'StudentCompletedGrade.student_status_id IN ' => [$statuses['PROMOTED'], $statuses['GRADUATED']],
                'StudentCompletedGrade.education_grade_id' => $gradeId
            ]
        )
            ->where(['StudentCompletedGrade.student_id IS' => NULL]);

        return $query;
    }

    public function findByStatus(Query $query, array $options)
    {
        $studentStatusId = $this->request->data['StudentTransfer']['student_status_id'];//POCOR-6230
        $statuses = array_key_exists('statuses', $options)? $options['statuses']: null;
        if (empty($statuses)) {
            return $query;
        }
        $statuses = $this->statuses;
        //POCOR-6230 Starts
        if($studentStatusId == 1){
            $studentStatusId = $statuses['CURRENT'];
        }else if($studentStatusId == 7){
            $studentStatusId = $statuses['PROMOTED'];
        }else if($studentStatusId == 6){
            $studentStatusId = $statuses['GRADUATED'];
        }else{
            $studentStatusId = 0;
        }//POCOR-6230 Ends
        $query->where([
            //$this->aliasField('student_status_id IN') => [$statuses['CURRENT'], $statuses['PROMOTED'], $statuses['GRADUATED']] //comment this line POCOR-6230
            $this->aliasField('student_status_id') => $studentStatusId //POCOR-6230
        ]);
        return $query;
    }
    public function addOnChangeFromAcademicPeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if (isset($data[$this->alias()]['education_grade_id'])) {
            unset($data[$this->alias()]['education_grade_id']);
        }
        if (isset($data[$this->alias()]['next_academic_period_id'])) {
            unset($data[$this->alias()]['next_academic_period_id']);
        }
    }

    public function addOnChangeGrade(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        unset($this->request->query['education_grade_id']);
        unset($this->request->query['institution_class']);
        unset($this->request->query['next_academic_period_id']);
        unset($this->request->query['next_education_grade_id']);

        if ($this->request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $data)) {
                if (array_key_exists('education_grade_id', $data[$this->alias()])) {
                    $this->request->query['education_grade_id'] = $data[$this->alias()]['education_grade_id'];
                }
            }
        }
    }

    public function addOnChangeNextPeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        unset($this->request->query['next_academic_period_id']);
        unset($this->request->query['next_education_grade_id']);

        if ($this->request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $data)) {
                if (array_key_exists('next_academic_period_id', $data[$this->alias()])) {
                    $this->request->query['next_academic_period_id'] = $data[$this->alias()]['next_academic_period_id'];
                }
            }
        }
    }

    public function addOnChangeNextGrade(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        unset($this->request->query['next_education_grade_id']);

        if ($this->request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $data)) {
                if (array_key_exists('next_education_grade_id', $data[$this->alias()])) {
                    $this->request->query['next_education_grade_id'] = $data[$this->alias()]['next_education_grade_id'];
                }
            }
        }
    }

    //POCOR-6925
    public function onUpdateFieldAssigneeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $workflowModel = 'Institutions > Student Transfer > Sending';
            $workflowModelsTable = TableRegistry::get('workflow_models');
            $workflowStepsTable = TableRegistry::get('workflow_steps');
            $Workflows = TableRegistry::get('Workflow.Workflows');
            $workModelId = $Workflows
                ->find()
                ->select(['id'=>$workflowModelsTable->aliasField('id'),
                    'workflow_id'=>$Workflows->aliasField('id'),
                    'is_school_based'=>$workflowModelsTable->aliasField('is_school_based')])
                ->LeftJoin([$workflowModelsTable->alias() => $workflowModelsTable->table()],
                    [
                        $workflowModelsTable->aliasField('id') . ' = '. $Workflows->aliasField('workflow_model_id')
                    ])
                ->where([$workflowModelsTable->aliasField('name')=>$workflowModel])->first();
            $workflowId = $workModelId->workflow_id;
            $isSchoolBased = $workModelId->is_school_based;
            $workflowStepsOptions = $workflowStepsTable
                ->find()
                ->select([
                    'stepId'=>$workflowStepsTable->aliasField('id'),
                ])
                ->where([$workflowStepsTable->aliasField('workflow_id') => $workflowId])
                ->first();
            $stepId = $workflowStepsOptions->stepId;
            $session = $request->session();
            if ($session->check('Institution.Institutions.id')) {
                $institutionId = $session->read('Institution.Institutions.id');
            }
            $institutionId = $institutionId;
            $assigneeOptions = [];
            if (!is_null($stepId)) {
                $WorkflowStepsRoles = TableRegistry::get('Workflow.WorkflowStepsRoles');
                $stepRoles = $WorkflowStepsRoles->getRolesByStep($stepId);
                if (!empty($stepRoles)) {
                    $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
                    $Areas = TableRegistry::get('Area.Areas');
                    $Institutions = TableRegistry::get('Institution.Institutions');
                    if ($isSchoolBased) {
                        if (is_null($institutionId)) {
                            Log::write('debug', 'Institution Id not found.');
                        } else {
                            $institutionObj = $Institutions->find()->where([$Institutions->aliasField('id') => $institutionId])->contain(['Areas'])->first();
                            $securityGroupId = $institutionObj->security_group_id;
                            $areaObj = $institutionObj->area;
                            // School based assignee
                            $where = [
                                'OR' => [[$SecurityGroupUsers->aliasField('security_group_id') => $securityGroupId],
                                    ['Institutions.id' => $institutionId]],
                                $SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles
                            ];
                            $schoolBasedAssigneeQuery = $SecurityGroupUsers
                                ->find('userList', ['where' => $where])
                                ->leftJoinWith('SecurityGroups.Institutions');
                            $schoolBasedAssigneeOptions = $schoolBasedAssigneeQuery->toArray();

                            // Region based assignee
                            $where = [$SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles];
                            $regionBasedAssigneeQuery = $SecurityGroupUsers
                                ->find('UserList', ['where' => $where, 'area' => $areaObj]);

                            $regionBasedAssigneeOptions = $regionBasedAssigneeQuery->toArray();
                            // End
                            $assigneeOptions = $schoolBasedAssigneeOptions + $regionBasedAssigneeOptions;
                        }
                    } else {
                        $where = [$SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles];
                        $assigneeQuery = $SecurityGroupUsers
                            ->find('userList', ['where' => $where])
                            ->order([$SecurityGroupUsers->aliasField('security_role_id') => 'DESC']);
                        $assigneeOptions = $assigneeQuery->toArray();
                    }
                }
            }
            $attr['type'] = 'chosenSelect';
            $attr['attr']['multiple'] = false;
            $attr['select'] = false;
            $attr['options'] = ['' => '-- ' . __('Select Assignee') . ' --'] + $assigneeOptions;
            $attr['onChangeReload'] = 'changeStatus';
            return $attr;
        }
    }
}
