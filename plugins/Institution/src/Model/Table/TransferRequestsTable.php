<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\I18n\Time;
use Cake\I18n\Date;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Controller\Component;
use Cake\Utility\Inflector;

use App\Model\Table\ControllerActionTable;

class TransferRequestsTable extends ControllerActionTable
{
    private $selectedAcademicPeriod;
    private $selectedGrade;
    private $InstitutionGrades;

    // Type for application
    const NEW_REQUEST = 0;
    const APPROVED = 1;
    const REJECTED = 2;

    // Type status for admission
    const TRANSFER = 2;
    const ADMISSION = 1;

    public function initialize(array $config)
    {
        $this->table('institution_student_admission');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('PreviousInstitutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('StudentTransferReasons', ['className' => 'Student.StudentTransferReasons']);
        $this->belongsTo('NewEducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->addBehavior('OpenEmis.Section');
        $this->InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');

        $this->toggle('search', false);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $validator
            ->add('requested_date', 'ruleInAcademicPeriod', [
                'rule' => ['inAcademicPeriod', 'academic_period_id', []],
            ])
            ->requirePresence('new_education_grade_id')
            ->add('student_id', 'ruleNoNewWithdrawRequestInGradeAndInstitution', [
                'rule' => ['noNewWithdrawRequestInGradeAndInstitution'],
                'on' => 'create'
            ])
            ->add('student_id', 'ruleStudentNotEnrolledInAnyInstitutionAndSameEducationSystem', [
                'rule' => ['studentNotEnrolledInAnyInstitutionAndSameEducationSystem', [
                    'excludeInstitutions' => ['previous_institution_id'],
                    'targetInstitution' => ['previous_institution_id']
                    ]
                ],
                'on' => 'create'
            ])
            ->add('student_id', 'ruleStudentNotCompletedGrade', [
                'rule' => ['studentNotCompletedGrade', [
                    'educationGradeField' => 'new_education_grade_id',
                    'studentIdField' => 'student_id'
                ]],
                'on' => 'create'
            ])
            ->add('institution_id', 'rulecompareStudentGenderWithInstitution', [
                'rule' => ['compareStudentGenderWithInstitution']
            ])
        ;
        $this->setValidationCode('student_name.ruleStudentNotCompletedGrade', 'Institution.Students');
        return $validator;
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        $events['ControllerAction.Model.associated'] = 'associated';

        return $events;
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona)
    {
        $Navigation->substituteCrumb('Transfers', 'TransferRequests', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'TransferRequests']);
        $Navigation->addCrumb(ucfirst($this->action));
    }

    public function associated(Event $event, ArrayObject $extra)
    {
        $this->Alert->error($this->aliasField('unableToTransfer'));
        $sessionKey = $this->registryAlias() . '.associated';

        $currentEntity = $this->Session->read($sessionKey);
        $dataBetweenDate = $this->Session->read($sessionKey.'Data');

        $extra['config']['form'] = true;
        $extra['elements']['edit'] = ['name' => 'OpenEmis.ControllerAction/edit'];
        $this->fields = []; // reset all the fields

        $this->field('student');
        $this->field('requested_date');
        $this->field('associated_records', ['type' => 'readonly']);

        $entity = $this->newEntity();

        $this->controller->set('data', $entity);
        return $entity;
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $statusToshow = [self::NEW_REQUEST, self::REJECTED];
        $typeToShow = [];

        if ($this->AccessControl->check(['Institutions', 'TransferRequests', 'view'])) {
            $typeToShow[] = self::TRANSFER;
        }

        $query->where([$this->aliasField('previous_institution_id') => $institutionId], [], true);

        $query->where([$this->aliasField('type').' IN' => $typeToShow, $this->aliasField('status').' IN' => $statusToshow]);
    }

    public function onGetStudentId(Event $event, Entity $entity)
    {
        $urlParams = $this->url('index');
        $action = $urlParams['action'];
        if ($entity->status == self::NEW_REQUEST) {
            if ($this->AccessControl->check(['Institutions', 'StudentAdmission', 'edit'])) {
                return $event->subject()->Html->link($entity->user->name, [
                    'plugin' => $urlParams['plugin'],
                    'controller' => $urlParams['controller'],
                    'action' => $action,
                    '0' => 'edit',
                    '1' => $this->paramsEncode(['id' => $entity->id])
                ]);
            }
        }
    }

    public function onGetStatus(Event $event, Entity $entity)
    {
        $statusName = "";
        switch ($entity->status) {
            case self::NEW_REQUEST:
                $statusName = __('New');
                break;
            case self::APPROVED:
                $statusName = __('Approved');
                break;
            case self::REJECTED:
                $statusName = __('Rejected');
                break;
            default:
                $statusName = $entity->status;
                break;
        }
        return __($statusName);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('institution_class_id', ['visible' => false]);
        if ($this->action == 'index') {
            $this->toggle('add', false);
        } else if ($this->action == 'associated') {
            // back button
            $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
            $toolbarAttr = [
                'class' => 'btn btn-xs btn-default',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false
            ];
            $toolbarButtonsArray['back']['type'] = 'button';
            $toolbarButtonsArray['back']['label'] = '<i class="fa kd-back"></i>';
            $toolbarButtonsArray['back']['attr'] = $toolbarAttr;
            $toolbarButtonsArray['back']['attr']['title'] = __('Back');
            $toolbarButtonsArray['back']['url'] = $this->url('add');
            $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
            // end back button
        } else {
            $hash = $this->request->query('hash');
            if (!empty($hash)) { // if value is empty, redirect back to the list page
                $params = $this->getUrlParams([$this->controller->name, $this->alias(), 'add'], $hash);

                // back button direct to student user view
                $backBtn = $extra['toolbarButtons']['back'];
                $backBtn['url']['action'] = 'StudentUser';
                $backBtn['url'][0] = 'view';
                $backBtn['url'][1] = $this->paramsEncode(['id' => $params['user_id']]);
                $backBtn['url']['id'] = $params['student_id'];
                $extra['toolbarButtons']['back'] = $backBtn;

                $extra['params'] = $params;
            }
        }
    }

    public function addAfterPatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $entityError = $entity->errors();
        if (!empty($entityError)) {
            $entityStudentError = $entity->errors('student_id');
            if (!empty($entityStudentError)) {
                // for 'add' putting the validation message in the correct place (unable to just validate on 'student' as 'notBlank' will trigger and was unable to remove)
                $entity->errors('student', $entity->errors('student_id'));
            }
        } else {
            $Students = TableRegistry::get('Institution.Students');
            $id = $extra['params']['student_id'];
            $institutionStudentData = $Students->get($id);

            $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
            $statusCodeList = array_flip($StudentStatuses->findCodeList());

            $isPromotedOrGraduated = in_array($statusCodeList[$institutionStudentData->student_status_id], ['GRADUATED', 'PROMOTED']);
            if ($isPromotedOrGraduated) {
                // when transfering a $isPromotedOrGraduated, it would be transfering to another academic period, therefore the end_date has to change accordingly
                $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
                $targetAcademicPeriodData = $AcademicPeriods->get($entity->academic_period_id);
                $entity->start_date = $targetAcademicPeriodData->start_date->format('Y-m-d');
                $entity->end_date = $targetAcademicPeriodData->end_date->format('Y-m-d');
            }
        }
    }

    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        // get the data between requested date and today date (if its back date)
        $dataBetweenDate = $this->getDataBetweenDate($requestData, $this->alias());

        if (!empty($dataBetweenDate)) {
            // redirect if have student data between date
            $url = $this->url('associated');
            $session = $this->Session;
            $session->write($this->registryAlias().'.associated', $entity);
            $session->write($this->registryAlias().'.associatedData', $dataBetweenDate);

            $event->stopPropagation();
            return $this->controller->redirect($url);
        }
    }

    public function getDataBetweenDate($data, $alias)
    {
// pr($this->request);
// pr($this->alias());
// pr($data);
// pr($alias);
// die;
        $StudentAbsences = TableRegistry::get('Institution.InstitutionStudentAbsences');
        $StudentBehaviours = TableRegistry::get('Institution.StudentBehaviours');
        $AssessmentItemResults = TableRegistry::get('Assessment.AssessmentItemResults');

        $relatedModels = [$StudentAbsences, $StudentBehaviours, $AssessmentItemResults];

        $studentId = $data[$alias]['student_id'];
        $institutionId = $data[$alias]['institution_id'];
        $academicPeriodId = $data[$alias]['academic_period_id'];
        $dateRequested = new Date($data[$alias]['requested_date']);
        $today = new Date();

        if ($alias == 'TransferApprovals') {
            $dateRequested = new Date($data[$alias]['start_date']);
        }

        $dataBetweenDate = [];
        foreach ($relatedModels as $model) {
            switch ($model->alias()) {
                case 'InstitutionStudentAbsences':
                    $data = $model->find()
                        ->where([
                            $model->aliasField('student_id') => $studentId,
                            $model->aliasField('start_date >=') => $dateRequested,
                            $model->aliasField('end_date <=') => $today
                        ])
                        ->all();

                    if (count($data)) {
                        $dataBetweenDate [$model->alias()] = count($data);
                    }
                    break;

                case 'StudentBehaviours':
                    $data = $model->find()
                        ->where([
                            $model->aliasField('student_id') => $studentId,
                            $model->aliasField('date_of_behaviour >=') => $dateRequested,
                            $model->aliasField('date_of_behaviour <=') => $today
                        ])
                        ->all();

                    if (count($data)) {
                        $dataBetweenDate [$model->alias()] = count($data);
                    }
                    break;

                case 'AssessmentItemResults':
                    $data = $model->find()
                        ->where([
                            $model->aliasField('student_id') => $studentId,
                            $model->aliasField('academic_period_id') => $academicPeriodId,
                        ])
                        ->all();

                    if (count($data)) {
                        $dataBetweenDate [$model->alias()] = count($data);
                    }
                    break;
            }
        }

        return $dataBetweenDate;
    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        $extra['redirect']['action'] = 'StudentUser';
        $extra['redirect'][0] = 'view';
        $extra['redirect'][1] = $this->paramsEncode(['id' => $entity->student_id]);
        $extra['redirect']['id'] = $extra['params']['student_id'];
    }

    public function addOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $id = $extra['params']['student_id'];

        $Students = TableRegistry::get('Institution.Students');
        $studentData = $Students->get($id);
        $selectedStudent = $studentData->student_id;
        $selectedPeriod = $studentData->academic_period_id;
        $selectedGrade = $studentData->education_grade_id;

        $StudentPromotion = TableRegistry::get('Institution.StudentPromotion');
        $student = $StudentPromotion
            ->find()
            ->where([
                $StudentPromotion->aliasField('institution_id') => $institutionId,
                $StudentPromotion->aliasField('student_id') => $selectedStudent,
                $StudentPromotion->aliasField('academic_period_id') => $selectedPeriod,
                $StudentPromotion->aliasField('education_grade_id') => $selectedGrade
            ])
            ->first();

        $entity->student_id = $student->student_id;
        $entity->academic_period_id = $student->academic_period_id;
        $entity->education_grade_id = $student->education_grade_id;
        $entity->student_status_id = $studentData->student_status_id;
        if ($student->start_date instanceof Time || $student->start_date instanceof Date) {
            $entity->start_date = $student->start_date->format('Y-m-d');
        } else {
            $entity->start_date = date('Y-m-d', strtotime($student->start_date));
        }

        if ($student->end_date instanceof Time || $student->end_date instanceof Date) {
            $entity->end_date = $student->end_date->format('Y-m-d');
        } else {
            $entity->end_date = date('Y-m-d', strtotime($student->end_date));
        }

        $entity->previous_institution_id = $institutionId;

        $this->request->data[$this->alias()]['student_id'] = $entity->student_id;
        $this->request->data[$this->alias()]['academic_period_id'] = $entity->academic_period_id;
        $this->request->data[$this->alias()]['education_grade_id'] = $entity->education_grade_id;
        $this->request->data[$this->alias()]['start_date'] = $entity->start_date;
        $this->request->data[$this->alias()]['end_date'] = $entity->end_date;
        $this->request->data[$this->alias()]['previous_institution_id'] = $entity->previous_institution_id;
        $this->request->data[$this->alias()]['student_status_id'] = $entity->student_status_id;
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->addSections();
        $this->field('transfer_status');
        $this->field('student');
        $this->field('student_id');
        $this->field('academic_period_id', ['student_id' => $extra['params']['student_id']]);
        $this->field('education_grade_id');
        $this->field('new_education_grade_id', ['student_id' => $extra['params']['student_id']]);
        $this->field('area_id');
        $this->field('institution_id');
        $this->field('status');
        $this->field('start_date');
        $this->field('end_date');
        $this->field('student_transfer_reason_id', ['type' => 'select']);
        $this->field('comment');
        $this->field('previous_institution_id');
        $this->field('type', ['type' => 'hidden', 'value' => self::TRANSFER]);
        $this->field('student_status_id', ['type' => 'hidden']);
        $this->field('requested_date');

        $this->setFieldOrder([
            'transfer_status_header', 'transfer_status',
            'existing_information_header', 'student', 'previous_institution_id', 'education_grade_id',
            'new_information_header', 'new_education_grade_id', 'area_id', 'institution_id',
            'academic_period_id',
            'status', 'start_date', 'end_date',
            'transfer_reasons_header', 'requested_date', 'student_transfer_reason_id', 'comment'
        ]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('start_date', ['visible' => false]);
        $this->field('end_date', ['visible' => false]);
        $this->field('previous_institution_id', ['visible' => false]);
        $this->field('type', ['visible' => false]);
        $this->field('comment', ['visible' => false]);
        $this->field('student_id');
        $this->field('status');
        $this->field('institution_id', ['visible' => false]);
        $this->field('academic_period_id');
        $this->field('education_grade_id');
        $this->field('new_education_grade_id');
        $this->field('comment');
        $this->field('created', ['visible' => false]);
        $this->field('student_transfer_reason_id', ['visible' => true]);

        // back button
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $toolbarAttr = [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];
        $toolbarButtonsArray['back']['type'] = 'button';
        $toolbarButtonsArray['back']['label'] = '<i class="fa kd-back"></i>';
        $toolbarButtonsArray['back']['attr'] = $toolbarAttr;
        $toolbarButtonsArray['back']['attr']['title'] = __('Back');
        $toolbarButtonsArray['back']['url']['plugin'] = 'Institution';
        $toolbarButtonsArray['back']['url']['controller'] = 'Institutions';
        $toolbarButtonsArray['back']['url']['action'] = 'Students';
        $toolbarButtonsArray['back']['url'][0] = 'index';
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        // End
    }

    private function addSections()
    {
        $this->field('transfer_status_header', ['type' => 'section', 'title' => __('Transfer Status')]);
        $this->field('existing_information_header', ['type' => 'section', 'title' => __('Transfer From')]);
        $this->field('new_information_header', ['type' => 'section', 'title' => __('Transfer To')]);
        $this->field('transfer_reasons_header', ['type' => 'section', 'title' => __('Other Details')]);
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->addSections();
        $this->field('student_transfer_reason_id', ['visible' => true]);
        $this->field('start_date', ['visible' => true]);
        $this->field('end_date', ['visible' => true]);
        $this->field('requested_date', ['visible' => true]);
        $this->field('previous_institution_id', ['visible' => false]);
        $this->field('type', ['visible' => false]);
        $this->field('comment', ['visible' => true]);
        $this->field('student_id');
        $this->field('status');
        $this->field('institution_id', ['visible' => true]);
        $this->field('academic_period_id', ['type' => 'readonly']);
        $this->field('education_grade_id');
        $this->field('new_education_grade_id');
        $this->field('comment');
        $this->field('created', ['visible' => true]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->request->data[$this->alias()]['status'] = $entity->status;
        $this->setFieldOrder([
            'transfer_status_header', 'created', 'status', 'type',
            'existing_information_header', 'student_id', 'academic_period_id', 'education_grade_id', 'start_date', 'end_date',
            'new_information_header', 'new_education_grade_id', 'institution_id',
            'transfer_reasons_header', 'requested_date', 'student_transfer_reason_id', 'comment'
        ]);
    }

    // add viewAfterAction to perform redirect if type is not 2
    // do the same for TransferApproval

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($entity->type != self::TRANSFER) {
            $event->stopPropagation();
            return $this->controller->redirect(['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Students']);
        }
        $this->addSections();
        $this->field('transfer_status');
        $this->field('student');
        $this->field('student_id');
        $this->field('area_id');
        $this->field('institution_id');
        $this->field('academic_period_id');
        $this->field('education_grade_id');
        $this->field('new_education_grade_id', [
            'type' => 'readonly',
            'attr' => [
                'value' => $this->NewEducationGrades->get($entity->new_education_grade_id)->programme_grade_name
            ]
        ]);
        $this->field('status');
        $this->field('start_date');
        $this->field('end_date');
        $this->field('requested_date', ['entity' => $entity]);
        $this->field('student_transfer_reason_id', ['type' => 'select']);
        $this->field('comment');
        $this->field('previous_institution_id');
        $this->field('type', ['type' => 'hidden', 'value' => self::TRANSFER]);

        $this->setFieldOrder([
            'transfer_status_header', 'transfer_status',
            'existing_information_header', 'student', 'previous_institution_id', 'education_grade_id',
            'new_information_header', 'new_education_grade_id', 'area_id', 'institution_id',
            'academic_period_id',
            'status', 'start_date', 'end_date',
            'transfer_reasons_header', 'requested_date', 'student_transfer_reason_id', 'comment'
        ]);
    }

    public function editOnInitialize(Event $event, Entity $entity)
    {
        // pr('editOnInitialize');
        // pr($entity);
        // die;
        // Set all selected values only
        $this->request->data[$this->alias()]['academic_period_id'] = $entity->academic_period_id;
        $this->request->data[$this->alias()]['transfer_status'] = $entity->status;
        $this->request->data[$this->alias()]['student_id'] = $entity->student_id;
        $this->request->data[$this->alias()]['institution_id'] = $entity->institution_id;
        $this->request->data[$this->alias()]['new_education_grade_id'] = $entity->new_education_grade_id;
        $this->request->data[$this->alias()]['education_grade_id'] = $entity->education_grade_id;
        $this->request->data[$this->alias()]['start_date'] = $entity->start_date;
        $this->request->data[$this->alias()]['end_date'] = $entity->end_date;
        $this->request->data[$this->alias()]['student_transfer_reason_id'] = $entity->student_transfer_reason_id;
    }

    public function deleteAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($extra->offsetExists('params')) {
            $extra['redirect']['action'] = 'StudentUser';
            $extra['redirect'][0] = 'view';
            $extra['redirect'][1] = $this->paramsEncode(['id' => $extra['params']['user_id']]);
            $extra['redirect']['id'] = $extra['params']['student_id'];
        }
    }


    /* to be implemented with custom autocomplete
    public function onUpdateIncludes(Event $event, ArrayObject $includes, $action) {
        if ($action == 'edit') {
            $includes['autocomplete'] = [
                'include' => true,
                'css' => ['OpenEmis.lib/jquery/jquery-ui.min', 'OpenEmis.../plugins/autocomplete/css/autocomplete'],
                'js' => ['OpenEmis.lib/jquery/jquery-ui.min', 'OpenEmis.../plugins/autocomplete/js/autocomplete']
            ];
        }
    }
    */

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
        if ($this->action == 'index') {
            $buttons->exchangeArray([]);
        } else if ($this->action == 'add') {
            $buttons[0]['name'] = '<i class="fa fa-check"></i> ' . __('Next');
        } else if ($this->action == 'associated') {
            $sessionKey = $this->registryAlias() . '.associatedData';
            if ($this->Session->check($sessionKey) && !empty($this->Session->read($sessionKey))) {
                unset($buttons[0]);
                unset($buttons[1]);
            }
        } else {
            if (isset($this->request->data[$this->alias()]['end_date'])) {
                $studentEndDate = new Time($this->request->data[$this->alias()]['end_date']);
                $todayDate = Time::now();

                if ($studentEndDate < $todayDate) { //disable save transfer request if student end_date is already passed.
                    $this->Alert->warning('TransferRequests.invalidEndDate');
                    unset($buttons[0]);
                    unset($buttons[1]);
                }
            }
        }
    }

    public function onUpdateFieldAssociatedRecords(Event $event, array $attr, $action, $request)
    {
        $dataBetweenDate = [];
        switch ($action) {
            case 'associated':
                $sessionKey = $this->registryAlias() . '.associatedData';
                if ($this->Session->check($sessionKey)) {
                    $dataBetweenDate = $this->Session->read($sessionKey);
                }
                break;
        }

        $attr['type'] = 'element';
        $attr['element'] = 'Institution.StudentTransfer/associatedRecords';
        $attr['data'] = $dataBetweenDate;

        return $attr;
    }

    public function onUpdateFieldTransferStatus(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $attr['type'] = 'readonly';
            $attr['attr']['value'] = __('New');
        } else if ($action == 'edit') {
            $transferStatus = $request->data[$this->alias()]['transfer_status'];

            $attr['type'] = 'readonly';
            if ($transferStatus == 0) {
                $attr['attr']['value'] = __('New');
            } else if ($transferStatus == 1) {
                $attr['attr']['value'] = __('Approve');
            } else if ($transferStatus == 2) {
                $attr['attr']['value'] = __('Reject');
            }
        }

        return $attr;
    }

    public function onUpdateFieldStudent(Event $event, array $attr, $action, $request)
    {
        if ($action == 'associated') {
            $selectedStudent = $this->Session->read('Student.Students.id');
        } else {
            $selectedStudent = $request->data[$this->alias()]['student_id'];
        }

        $attr['type'] = 'readonly';
        $attr['attr']['value'] = $this->Users->get($selectedStudent)->name_with_id;

        return $attr;
    }

    public function onUpdateFieldStudentId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $selectedStudent = $request->data[$this->alias()]['student_id'];
            $attr['type'] = 'hidden';
            $attr['attr']['value'] = $selectedStudent;
            return $attr;
        }
    }

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, $request)
    {
        $today = Date::now();
        if ($action == 'add') {
            $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
            $InstitutionStatuses = TableRegistry::get('Institution.Statuses');
            $institutionId = $this->Session->read('Institution.Institutions.id');

            $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $selectedAcademicPeriodData = $AcademicPeriods->get($this->selectedAcademicPeriod);

            if ($selectedAcademicPeriodData->start_date instanceof Time || $selectedAcademicPeriodData->start_date instanceof Date) {
                $academicPeriodStartDate = $selectedAcademicPeriodData->start_date->format('Y-m-d');
            } else {
                $academicPeriodStartDate = date('Y-m-d', $selectedAcademicPeriodData->start_date);
            }

            if ($selectedAcademicPeriodData->end_date instanceof Time || $selectedAcademicPeriodData->end_date instanceof Date) {
                $academicPeriodEndDate = $selectedAcademicPeriodData->end_date->format('Y-m-d');
            } else {
                $academicPeriodEndDate = date('Y-m-d', $selectedAcademicPeriodData->end_date);
            }

            $institutionOptions = $this->Institutions
                ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                ->join([
                    'table' => $InstitutionGrades->table(),
                    'alias' => $InstitutionGrades->alias(),
                    'conditions' => [
                        $InstitutionGrades->aliasField('institution_id =') . $this->Institutions->aliasField('id'),
                        $InstitutionGrades->aliasField('institution_id') . ' <> ' . $institutionId, // Institution list will not contain the old institution.
                        $InstitutionGrades->aliasField('education_grade_id') => $this->selectedGrade,
                        $InstitutionGrades->aliasField('start_date') . ' <= ' => $academicPeriodEndDate,
                        'OR' => [
                            $InstitutionGrades->aliasField('end_date') . ' IS NULL',
                            // Previously as long as the programme end date is later than academicPeriodStartDate, institution will be in the list.
                            // POCOR-3134 request to only displayed institution with active grades (end-date is later than today-date)
                            $InstitutionGrades->aliasField('end_date') . ' >=' => $today->format('Y-m-d'),
                        ]
                    ]
                ])
                ->where([$this->Institutions->aliasField('institution_status_id') => $InstitutionStatuses->getIdByCode('ACTIVE')])
                ->order([$this->Institutions->aliasField('code')]);

                if (!empty($request->data[$this->alias()]['area_id'])) {
                    $institutionOptions->where([$this->Institutions->aliasField('area_id') => $request->data[$this->alias()]['area_id']]);
                }

            $attr['type'] = 'chosenSelect';
            $attr['attr']['multiple'] = false;
            $attr['select'] = true;
            $attr['options'] = $institutionOptions->toArray();

            /* to be implemented with custom autocomplete
            $attr['type'] = 'string';
            $attr['attr'] = [
                'class' => 'autocomplete',
                'autocomplete-url' => '/core_v3/Institutions/Transfers/ajaxInstitutionAutocomplete',
                'autocomplete-class' => 'error-message',
                'autocomplete-no-results' => __('No Institution found.'),
                'value' => ''
            ];
            */
        } else if ($action == 'edit') {
            $selectedInstitution = $request->data[$this->alias()]['institution_id'];
            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $this->Institutions->get($selectedInstitution)->code_name;
        }

        return $attr;
    }

    public function onUpdateFieldNewEducationGradeId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $id = $attr['student_id'];
            $Students = TableRegistry::get('Institution.Students');

            $studentInfo = $Students->find()->contain(['EducationGrades', 'StudentStatuses'])->where([$Students->aliasField($Students->primaryKey()) => $id])->first();

            $studentStatusCode = null;
            if ($studentInfo) {
                $studentStatusCode = $studentInfo->student_status->code;
            }

            switch ($studentStatusCode) {
                case 'GRADUATED': case 'PROMOTED':
                        $moreAdvancedEducationGrades = [];
                        $currentProgrammeGrades = $this->EducationGrades
                            ->find('list', [
                                'keyField' => 'id',
                                'valueField' => 'programme_grade_name'
                            ])
                            ->find('visible')
                            ->where([
                                $this->EducationGrades->aliasField('order').' > ' => $studentInfo->education_grade->order,
                                $this->EducationGrades->aliasField('education_programme_id') => $studentInfo->education_grade->education_programme_id
                            ])
                            ->toArray();

                        $EducationProgrammesNextProgrammesTable = TableRegistry::get('Education.EducationProgrammesNextProgrammes');
                        $educationProgrammeId = $studentInfo->education_grade->education_programme_id;
                        $nextEducationGradeList = $EducationProgrammesNextProgrammesTable->getNextGradeList($educationProgrammeId);
                        $moreAdvancedEducationGrades = $currentProgrammeGrades + $nextEducationGradeList;
                        if (isset($request->data[$this->alias()]['new_education_grade_id'])) {
                            $this->selectedGrade = $request->data[$this->alias()]['new_education_grade_id'];
                            if (!array_key_exists($this->selectedGrade, $moreAdvancedEducationGrades)) {
                                reset($moreAdvancedEducationGrades);
                                $this->selectedGrade = key($moreAdvancedEducationGrades);
                            }
                        }

                        $attr['options'] = $moreAdvancedEducationGrades;
                        $attr['onChangeReload'] = 'changeNewEducationGradeId';

                    break;

                default:
                    // only able to transfered on the same grade, new education grade id = education grade id.
                    $this->selectedGrade = $request->data[$this->alias()]['education_grade_id'];
                    $request->data[$this->alias()]['new_education_grade_id'] = $this->selectedGrade;
                    $attr['type'] = 'readonly';
                    $attr['attr']['value'] = $this->EducationGrades->get($this->selectedGrade)->programme_grade_name;
                    break;
            }
        } elseif ($action == 'edit') {
            $this->selectedGrade = $request->data[$this->alias()]['new_education_grade_id'];
            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $this->EducationGrades->get($this->selectedGrade)->programme_grade_name;
        }
        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $StudentStatusesTable = TableRegistry::get('Student.StudentStatuses');
            $status = $StudentStatusesTable->findCodeList();
            $studentStatus = $request->data[$this->alias()]['student_status_id'];
            switch ($studentStatus) {
                case $status['PROMOTED']:
                case $status['GRADUATED']:
                    $id = $attr['student_id'];
                    $Students = TableRegistry::get('Institution.Students');
                    $studentInfo = $Students->find()->contain(['AcademicPeriods'])->where([$Students->aliasField($Students->primaryKey()) => $id])->first();

                    $academicPeriodStartDate = $studentInfo->academic_period->start_date;

                    if ($studentInfo->academic_period->start_date instanceof Time || $studentInfo->academic_period->start_date instanceof Date) {
                        $academicPeriodStartDate = $studentInfo->academic_period->start_date->format('Y-m-d');
                    } else {
                        $academicPeriodStartDate = date('Y-m-d', strtotime($studentInfo->academic_period->start_date));
                    }
                    $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
                    $academicPeriodsAfter = $AcademicPeriods
                        ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                        ->where([$AcademicPeriods->aliasField('start_date') .' > '  => $academicPeriodStartDate])
                        ->where([$AcademicPeriods->aliasField('academic_period_level_id') => $studentInfo->academic_period->academic_period_level_id])
                        ->order($AcademicPeriods->aliasField('start_date').' asc')
                        ->toArray()
                        ;

                    $this->selectedAcademicPeriod = $request->data[$this->alias()]['academic_period_id'];
                    if (!array_key_exists($this->selectedAcademicPeriod, $academicPeriodsAfter)) {
                        reset($academicPeriodsAfter);
                        $this->selectedAcademicPeriod = key($academicPeriodsAfter);
                    }

                    $attr['options'] = $academicPeriodsAfter;

                    break;

                case $status['CURRENT']:
                    $this->selectedAcademicPeriod = $request->data[$this->alias()]['academic_period_id'];
                    $attr['type'] = 'hidden';
                    break;
            }
        } else if ($action == 'edit') {
            $attr['type'] = 'hidden';
        }
        return $attr;
    }

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $educationGradeId = $request->data[$this->alias()]['education_grade_id'];
            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $this->EducationGrades->get($educationGradeId)->programme_grade_name;
        }

        return $attr;
    }

    public function onUpdateFieldStatus(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $status = 0; // New

            $attr['type'] = 'hidden';
            $attr['attr']['value'] = $status;
        } else if ($action == 'edit') {
            $attr['type'] = 'hidden';
        }

        return $attr;
    }

    public function onUpdateFieldPreviousInstitutionId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $institutionId = $this->Session->read('Institution.Institutions.id');

            $attr['type'] = 'hidden';
            $attr['attr']['value'] = $institutionId;
        } else if ($action == 'edit') {
            $attr['type'] = 'hidden';
        }

        return $attr;
    }

    public function onUpdateFieldStartDate(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $startDate = $request->data[$this->alias()]['start_date'];

            $attr['type'] = 'hidden';
            $attr['attr']['value'] = date('d-m-Y', strtotime($startDate));
        }

        return $attr;
    }

    public function onUpdateFieldEndDate(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $endDate = $request->data[$this->alias()]['end_date'];

            $attr['type'] = 'hidden';
            $attr['attr']['value'] = date('d-m-Y', strtotime($endDate));
        }

        return $attr;
    }

    public function onUpdateFieldRequestedDate(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' ) {
            $data = $request->data[$this->alias()];
            $academicPeriodId = $data['academic_period_id'];
            $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $periodStartDate = $AcademicPeriods->get($academicPeriodId)->start_date;
            $periodEndDate = $AcademicPeriods->get($academicPeriodId)->end_date;
            $studentStartDate = new Date($data['start_date']);
            $studentEndDate = new Date($data['end_date']);

            // for date_options, date restriction
            $startDate = ($studentStartDate >= $periodStartDate) ? $studentStartDate: $periodStartDate;
            $endDate = ($studentEndDate <= $periodStartDate) ? $studentEndDate: $periodEndDate;
            $attr['date_options'] = [
                'startDate' => $startDate->format('d-m-Y'),
                'endDate' => $endDate->format('d-m-Y')
            ];
            $attr['date_options']['todayBtn'] = false;
            // end date_options
        } else if ($action == 'edit') {
            $requestedDate = $attr['entity']->requested_date;

            $attr['type'] = 'readonly';
            $attr['value'] = $requestedDate->format('d-m-Y');
            $attr['attr']['value'] = $requestedDate->format('d-m-Y');
        } else if ($action == 'associated') {
            $sessionKey = $this->registryAlias() . '.associated';
            $currentEntity = $this->Session->read($sessionKey);
            $requestedDate = $currentEntity->requested_date;

            $attr['type'] = 'readonly';
            $attr['value'] = $requestedDate->format('d-m-Y');
            $attr['attr']['value'] = $requestedDate->format('d-m-Y');
        }

        return $attr;
    }

    public function onUpdateFieldAreaId(Event $event, array $attr, $action, $request) {
        if ($action == 'add') {
            $institutionId = $this->Session->read('Institution.Institutions.id');

            $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $selectedAcademicPeriodData = $AcademicPeriods->get($this->selectedAcademicPeriod);

            if ($selectedAcademicPeriodData->start_date instanceof Time || $selectedAcademicPeriodData->start_date instanceof Date) {
                $academicPeriodStartDate = $selectedAcademicPeriodData->start_date->format('Y-m-d');
            } else {
                $academicPeriodStartDate = date('Y-m-d', $selectedAcademicPeriodData->start_date);
            }

            if ($selectedAcademicPeriodData->end_date instanceof Time || $selectedAcademicPeriodData->end_date instanceof Date) {
                $academicPeriodEndDate = $selectedAcademicPeriodData->end_date->format('Y-m-d');
            } else {
                $academicPeriodEndDate = date('Y-m-d', $selectedAcademicPeriodData->end_date);
            }

            $Areas = $this->Institutions->Areas;
            $areaOptions = $Areas->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'code_name'
                ])
                ->innerJoinWith('Institutions.InstitutionGrades')
                ->where(['InstitutionGrades.education_grade_id' => $this->selectedGrade,
                    $this->Institutions->aliasField('id').' <> ' => $institutionId,
                    'InstitutionGrades.start_date <=' => $academicPeriodEndDate,
                    'OR' => [
                            'InstitutionGrades.end_date IS NULL',
                            'InstitutionGrades.end_date >=' => $academicPeriodStartDate
                    ]
                ])
                ->order([$Areas->aliasField('order')]);

            $attr['type'] = 'chosenSelect';
            $attr['attr']['multiple'] = false;
            $attr['select'] = true;
            $attr['options'] = $areaOptions->toArray();
            $attr['onChangeReload'] = true;

        } else if ($action == 'edit') {
            $Areas = $this->Institutions->Areas;
            $selectedInstitution = $request->data[$this->alias()]['institution_id'];
            $selectedArea = $this->Institutions->get($selectedInstitution)->area_id;

            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $Areas->get($selectedArea)->code_name;
        }

        return $attr;
    }
}
