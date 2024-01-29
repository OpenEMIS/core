<?php

namespace Institution\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use App\Model\Traits\MessagesTrait;
use Cake\Core\Exception\Exception;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Core\Configure;
use Cake\Utility\Security;
use Cake\Mailer\Email;
use Cake\Network\Session;
/**
 * POCOR-7458 (to develop messaging functionality)
 * <author>megha.gupta@mail.valuecoders.com</author>
 */
class MessagingTable extends ControllerActionTable
{
    use MessagesTrait;
    //recipient levels (hard coded)
    const INSTITUTION = 1;
    const PROGRAMME = 2;
    const GRADE=3;
    const GRADE_CLASS = 4;
    const SUBJECT=5;
    //status
    const DRAFT = 0;
    const SEND = 1;
    public $recipientlevelOptions = [];

    public function initialize(array $config)
    {
        $this->table('messaging');
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->hasMany('MessagingSecurityRoles', ['className' => 'Institution.MessagingSecurityRoles','foreignKey'=>"message_id"]);
        $this->hasMany('MessageRecipients', ['className' => 'Institution.MessageRecipients', 'foreignKey' => "message_id"]);
        $this->recipientlevelOptions = [
            '1' => __('Institution'),
            '2' => __('Programme'),
            '3' => __('Grade'),
            '4' => __('Class'),
            '5' => __('Subject')
        ];
    }
     public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);
        return  $validator
                    ->add('security_role_id', 'custom', [
                        'rule' => function($value, $context) {
                            return (!empty($value['_ids']) && is_array($value['_ids']));
                        },
                        'message' => __('This field cannot be left empty')
                    ]);
    }
    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('message');
        $this->field('institution_id', ['visible' =>  ['index' => false, 'view' => false, 'edit' => false, 'add' => false]]);
        $this->field('academic_period_id'); 
        $this->field('created',['visible' => ['index' => true, 'view' => true, 'edit' => false, 'add' => false]]);
        $this->field('created_user_id', ['visible' => ['index' => true, 'view' => true, 'edit' => false, 'add' => false]]);
        $this->field('recipient_level_id');
        $this->field('recipient_group_id');
        $this->field('security_role_id',['required'=>true,'visible' => ['index' => false, 'view' => true, 'edit' => false, 'add' => true]]);
        $this->field('subject',['sort'=>false]);
        $this->field('status', ['visible' => ['index' => true, 'view' => true, 'edit' => false, 'add' => false]]);
           
    }
    public function beforeSave(Event $event, Entity $entity, ArrayObject $data)
    {
        $entity->institution_id  = $this->Session->read('Institution.Institutions.id');
    }
    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id');
        $this->field('security_role_id', ['entity' => $entity, 'visible' => true]);
        $this->field('message');
        $this->field('recipient_level_id', ['entity' => $entity]);
        $this->field('recipient_group_id', [['entity' => $entity]]);

        $this->fields['security_role_id']['required'] = true;
        $this->setFieldOrder(['academic_period_id', 'recipient_level_id', 'recipient_group_id', 'security_role_id', 'subject', 'message']);
    }
    public function afterSave(Event $event, Entity $entity, ArrayObject $requestData)
    {
        $entity->institution_id = $this->Session->read('Institution.Institutions.id');
        if ($this->request->params['pass'][0] == 'edit') {
            //deleting messaging_security_role entries
            $SecurityRoleData = $this->MessagingSecurityRoles->find()->where(['message_id' => $entity->id])->toArray();
            if ($SecurityRoleData) {
                foreach ($SecurityRoleData as $SecurityRoleEntity) {
                    $deleteEntity =  $this->MessagingSecurityRoles->delete($SecurityRoleEntity);
                }
            }
            //deleting message_recipients entries
            $MessageRecipientData = $this->MessageRecipients->find()->where(['message_id' => $entity->id])->toArray();
            if ($MessageRecipientData) {
                foreach ($MessageRecipientData as $MessageRecipientEntity) {
                    $deleteRecipientEntity =  $this->MessageRecipients->delete($MessageRecipientEntity);
                }
            }
        }
        $security_role_data = [];
        //saving messaging_security_roles entries
        if (!empty($entity->security_role_id['_ids'])) {
            foreach ($entity->security_role_id['_ids'] as $key => $value) {
                $SecurityRolesData = ['message_id' => $entity->id, 'security_role_id' => $value];
                $security_role_data[] = $value;
                $SecurityRolesEntity = $this->MessagingSecurityRoles->newEntity($SecurityRolesData);
                $result = $this->MessagingSecurityRoles->save($SecurityRolesEntity);
                unset($SecurityRolesEntity);
                unset($result);
            }
        }
        $studentData = $this->getRecipientList($entity);
        $student = 0;
        $guardian = 0;
        foreach ($security_role_data as $key => $value) {
            $SecurityRoleName = TableRegistry::get('Security.SecurityRoles')->get($value)->name;
            if (strtolower($SecurityRoleName) == "student") {
                $student = 1;
            } else if (strtolower($SecurityRoleName) == "guardian") {
                $guardian = 1;
            }
            unset($SecurityRoleName);
        }
        //saving message_recipients entries
        if (!empty($studentData)) {
            foreach ($studentData as $key => $value) {
                if ($student) {
                    if (!empty($value['student_email'])) {
                        $RecipientEntity = ['message_id' => $entity->id, 'recipient_id' => $value['student_id']];

                        $RecipientEntity = $this->MessageRecipients->newEntity($RecipientEntity);
                        $saveData = $this->MessageRecipients->save($RecipientEntity);
                        unset($RecipientEntity);
                        unset($saveData);
                    }
                }
                if ($guardian) {
                    if (!empty($value['guardian_email'])) {
                        $RecipientEntity = ['message_id' => $entity->id, 'recipient_id' => $value['guardian_id']];
                        $RecipientEntity = $this->MessageRecipients->newEntity($RecipientEntity);
                        $saveData = $this->MessageRecipients->save($RecipientEntity);
                        unset($RecipientEntity);
                        unset($saveData);
                    }
                }
            }
        }
    }
    public function addEditOnsendMessage(Event $event, Entity $entity, ArrayObject $data, ArrayObject $patchOptions, ArrayObject $extra)
    {

        $entity->institution_id = $this->Session->read('Institution.Institutions.id');
        $patchOptions['validate'] = true;
        $entity = $this->patchEntity($entity, $data->getArrayCopy(), $patchOptions->getArrayCopy());
        $entity->recipient_group_id = $data['Messaging']['recipient_group_id'];

        $AlertLogs = TableRegistry::get('Alert.AlertLogs');
        $query = $this->getRecipientList($entity);
        $SecurityRoles = [];

        foreach ($entity->security_role_id['_ids'] as $key => $value) {
            $SecurityRoles[] = strtolower(TableRegistry::get('security_roles')->get($value)->code);
        }
        //for sending email and inserting message logs
        $emailList = [];
        if (!empty($query)) {
            foreach ($query as $key => $studentData) {
                if (in_array("student", $SecurityRoles)) {
                    if (!empty($studentData->student_email)) {
                        $email = $studentData->student_email;
                        $name = $studentData->student_first_name . " " . $studentData->student_last_name;
                        $recipient = $name . ' <' . $email . '>';
                        if (!in_array($recipient, $emailList)) {
                            $emailList[] = $recipient;
                        }
                    }
                }
                if (in_array("guardian", $SecurityRoles)) {
                    if (!empty($studentData->guardian_email)) {
                        $email = $studentData->guardian_email;
                        $name = $studentData->guardian_first_name . " " . $studentData->guardian_last_name;
                        $recipient = $name . ' <' . $email . '>';
                        if (!in_array($recipient, $emailList)) {
                            $emailList[] = $recipient;
                        }
                    }
                }
            }
        }
        if (!empty($emailList)) {
            foreach ($emailList as $key => $value) {
                $emailSubject = $entity->subject;
                $emailMessage = $entity->message;
                $AlertLogs->insertAlertLog("Email", "Messaging", $value, $emailSubject, $emailMessage);
            }
        }
        $entity->status = 1;
        $result = $this->save($entity);
        $this->Alert->success('Messaging.email');
        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }
    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {

        if ($entity->status === 1) {
            unset($extra['toolbarButtons']['edit']);
        }
        $tabElements = $this->controller->getMessagingTabElements();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Messaging');
        $this->field('security_role_id', ['entity' => $entity, 'visible' => true]);
        $this->field('status');
        $this->field('modified');
        $this->field('modified_user_id');
        $this->Session->write('messageId', $entity->id);
        $this->setFieldOrder(['academic_period_id', 'recipient_level_id', 'recipient_group_id', 'security_role_id', 'subject', 'message', 'status', 'modified', 'modified_user_id', 'created', 'created_user_id']);
    }
    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain('MessagingSecurityRoles');
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                $arr = [];
                foreach ($row->messaging_security_roles as $key => $role) {
                    $arr[$key] = ['id' => $role['security_role_id']];
                }
                $row['security_role_id'] = $arr;

                return $row;
            });
        });
    }
    public function indexAfterAction(Event $event, Query $query)
    {
        $this->field('message', ['visible' => false]);
        $this->field('academic_period_id', ['visible' => false]);
        $this->setFieldOrder(['created','created_user_id','academic_period_id', 'recipient_level_id', 'recipient_group_id', 'security_role_id', 'subject', 'message']);

    }
    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $extra['selectedAcademicPeriodOptions'] = $this->getSelectedAcademicPeriod($this->request);
      
        $extra['elements']['control'] = [
            'name' => 'Institution.Messaging/controls',
            'data' => [
                'periodOptions' => $academicPeriodOptions,
                'selectedPeriod' => $extra['selectedAcademicPeriodOptions']
            ],
            'order' => 3
        ];
    }
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        if (array_key_exists('selectedAcademicPeriodOptions', $extra)) {
            $query->where([
                $this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodOptions'],
                $this->aliasField('institution_id') =>  $this->Session->read('Institution.Institutions.id')
            ], [], true);
        }
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        if ($entity->status == "Send" || $entity->status == self::SEND) {
            unset($buttons['edit']);
        }
        return $buttons;
    }
    public function onGetRecipientLevelId(Event $event, Entity $entity)
    {
       $value="";
       switch($entity->recipient_level_id){
            case self::INSTITUTION:
                $value="Institution";
                break;
            case self::PROGRAMME:
                $value="Programme";
                break;
            case self::GRADE:
                $value = "Grade";
                break;
            case self::GRADE_CLASS:
                $value = "Class";
                break;
            case self::SUBJECT:
                $value = "Subject";
                break;
            default:
                $value="";
       }
       return $value;
    }
    public function onGetRecipientGroupId(Event $event, Entity $entity)
    {
        $institution_id = $this->Session->read('Institution.Institutions.id');
        $academicPeriodId = $entity->academic_period_id;
        $option=$this->getRecipientGroupOptions($entity->recipient_level_id);
        $result= $option[$entity->recipient_group_id];
        return $result;
    }
    public function onGetCreated(Event $event, Entity $entity)
    {

        return date_format($entity->created, 'd M Y');
    }
    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
        if ($this->action == 'add' || $this->action == 'edit') {
            $originalButtons = $buttons->getArrayCopy();

            $sendButton = [
                [
                    'name' => '<i class="fa fa-check"></i>' . __('Send'),
                    'attr' => [
                        'class' => 'btn btn-default btn-save',
                        'name' => 'submit',
                        'value' => 'sendMessage',
                        'div' => false
                    ]
                ]
            ];

            array_splice($originalButtons, 1, 0, $sendButton);
            $buttons->exchangeArray($originalButtons);
        }
    }
    public function onGetStatus(Event $event, Entity $entity)
    {

        if ($entity->status == self::DRAFT) {
            return "Draft";
        } else if ($entity->status == self::SEND) {
            return "Send";
        }
    }
    public function onGetSecurityRoleId(Event $event, Entity $entity)
    {
        $table = TableRegistry::get('Security.SecurityRoles');
        $obj = [];
        if ($entity->has('security_role_id')) {

            foreach ($entity->security_role_id as $role) {
                $res = $table->find('list')->where(['id' => $role['id']])->first();
                $obj[] = $res;
            }
        }

        $values = !empty($obj) ? implode(', ', $obj) : __('No Security Roles Selected ');
        return $values;
    }
    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'created_user_id':
                return __('Created By');
            case 'created':
                return __('Created');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
    public function onUpdateFieldRecipientLevelId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit'
        ) {
            $attr['type'] = 'select';
            $attr['select'] = true;
            $attr['options'] = $this->recipientlevelOptions;
            $attr['onChangeReload'] = true;
        }

        return $attr;
    }
    public function onUpdateFieldRecipientGroupId(Event $event, array $attr, $action, Request $request)
    {
      
        if (
            $action == 'add' || $action == 'edit'
        ) {
            $recipient_level_id =$request->data['Messaging']['recipient_level_id'];
            if($action=="edit"){
                $entity = $this->get($this->paramsDecode($request['pass'][1])['id']);
                $recipient_level_id = $entity->recipient_level_id;
            }
            $attr['type'] = 'select';
            $attr['select'] = true;
            $data = $this->getRecipientGroupOptions($recipient_level_id);
            $attr['options']=$data;
        }

        return $attr;
    }
    public function onUpdateFieldSecurityRoleId(Event $event, array $attr, $action, Request $request)
    {

        $entity = $attr['entity'];
        $SecurityRoles = TableRegistry::get('Security.SecurityRoles');
        $options = $SecurityRoles->find('list', [
            'keyField' => 'id',
            'valueField' => 'name',
        ])->where([
            $SecurityRoles->aliasField('name IN') => ['Student', 'Guardian']
        ])->toArray();
        $attr['type'] = 'chosenSelect';
        $attr['attr']['multiple'] = true;
        $attr['options'] = $options;
        $attr['attr']['required'] = true;
        return $attr;
    }
    public function onUpdateFieldMessage(Event $event, array $attr, $action, Request $request)
    {
        $attr['type'] = 'text';
        return $attr;
    }
    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit' || $action == "add") {

            $selectedPeriod  = $this->getSelectedAcademicPeriod($this->request->query('period'));
            $attr['attr']['value'] = $this->AcademicPeriods->get($selectedPeriod)->name;
            $attr['type'] = 'readonly';
            $attr['value'] = $selectedPeriod;
        }
        return $attr;
    }
    public function getRecipientGroupOptions($recipient_level_id){
       
        $institution_id=$this->Session->read('Institution.Institutions.id');
        $academicPeriodId =TableRegistry::get('AcademicPeriod.AcademicPeriods')->getCurrent();
        
        $option=[];
        switch ($recipient_level_id) {
            case self::INSTITUTION:
            case "Institution":
                $option[$institution_id]= $this->Session->read('Institution.Institutions.name');
                 break;
            case self::PROGRAMME:
            case "Programme":
                $result= $this->getSelectOptions($institution_id, $academicPeriodId);
                $programmeData=$result->group('EducationProgrammes.id')->toArray();
                foreach($programmeData as $key => $value) {
                    $option[$value->education_programme_id] = $value->education_programme_name;
                }
                break;
            case self::GRADE:
            case "Grade":
                $gradeData = $this->getSelectOptions($institution_id, $academicPeriodId)->toArray();
                foreach ($gradeData as $key => $value) {
                    $option[$value->education_grade_id] = $value->education_grade_name;
                }
                break;
            case self::GRADE_CLASS:
            case "Class":
                $result = $this->getClassOptions($institution_id, $academicPeriodId);
                $classData = $result->group('InstitutionClasses.id')->toArray();
                foreach ($classData as $key => $value) {
                    $option[$value->id] = $value->name;
                }
                break;
            case self::SUBJECT:
            case "Subject":
                $classData = $this->getClassOptions($institution_id, $academicPeriodId)->toArray();
                foreach ($classData as $key => $value) {
                    foreach($value->institution_subjects as $Key => $Value){ 
                        $option[$value->id."-".$Value->id] = $value->name." ".$Value->name;
                    }
                }
                $option=array_unique($option);
                break;
            default:
                $value = "";
        }
        return $option;
    }
    public function getSelectOptions($institution_id, $academicPeriodId)
    {
        $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
        $programmeOptions = [];

        $query = $InstitutionGrades
            ->find()
            ->select([
                'education_programme_id' => 'EducationProgrammes.id',
                'education_programme_name' => 'EducationProgrammes.name',
                'education_grade_id' => 'EducationGrades.id',
                'education_grade_name' => 'EducationGrades.name'
            ])
            ->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems']) 
            ->where(['EducationSystems.academic_period_id' => $academicPeriodId,
                     'InstitutionGrades.institution_id' => $institution_id
            ])
            ->order(['EducationLevels.order' =>'ASC','EducationCycles.order'=>'ASC','EducationProgrammes.order' => 'ASC','EducationGrades.order' => 'ASC']); //POCOR-8021//POCOR-8048:modified
        return $query;
    }
    public function getClassOptions($institution_id, $academicPeriodId)
    {
        $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $query=$InstitutionClasses->find()->contain('InstitutionSubjects')
                        ->where([
                            $InstitutionClasses->aliasField('academic_period_id') => $academicPeriodId,
                            $InstitutionClasses->aliasField('institution_id') => $institution_id
                        ]);
        return $query;

    }
    private function getSelectedAcademicPeriod($request)
    {
        $selectedAcademicPeriod = '';

        if ($this->action == 'index' || $this->action == 'view' || $this->action == 'edit'
        ) {
            if (isset($request->query) && array_key_exists('period', $request->query)
            ) {
                $selectedAcademicPeriod = $request->query['period'];
            } else {
                $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
            }
        } elseif ($this->action == 'add') {
            $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
        }

        return $selectedAcademicPeriod;
    } 
    
    //POCOR-8016::modify query Start
    public function getRecipientList($entity)
    {
        $InstitutionSubjectStudent = TableRegistry::get('Institution.InstitutionSubjectStudents');
        $InstitutionStudent = TableRegistry::get('Institution.InstitutionStudents');
        $where = [];

        if($entity->recipient_level_id == 1 || $entity->recipient_level_id == 2 || $entity->recipient_level_id == 3){
            if ($entity->recipient_level_id == 1) {
            } else if ($entity->recipient_level_id == 2) {
                $where['EducationGrades.education_programme_id'] = $entity->recipient_group_id;
            } else if ($entity->recipient_level_id == 3) {
                $where['InstitutionStudents.education_grade_id'] = $entity->recipient_group_id;
            }
            
            $query = $InstitutionStudent->find()
                ->select([
                    'student_openemis' => 'StudentInfo.openemis_no',
                    'student_id' => 'InstitutionStudents.student_id',
                    'student_email' => 'StudentInfo.email',
                    'student_first_name' => 'StudentInfo.first_name',
                    'student_last_name' => 'StudentInfo.last_name',
                    'guardian_id' => 'StudentGuardians.guardian_id',
                    'guardian_openemis' => 'GuardianInfo.openemis_no',
                    'guardian_email' => 'GuardianInfo.email',
                    'guardian_first_name' => 'GuardianInfo.first_name',
                    'guardian_last_name' => 'GuardianInfo.last_name',
                ])
                ->innerJoin(
                    ['EducationGrades' => 'education_grades'],
                    ['EducationGrades.id = InstitutionStudents.education_grade_id']
                )
                ->innerJoin(
                    ['StudentInfo' => 'security_users'],
                    ['StudentInfo.id = InstitutionStudents.student_id']
                )
                ->innerJoin(
                    ['AcademicPeriods' => 'academic_periods'],
                    [
                        'AcademicPeriods.id = InstitutionStudents.academic_period_id',
                    ]
                )
                ->leftJoin(
                    ['StudentGuardians' => 'student_guardians'],
                    ['StudentGuardians.student_id = InstitutionStudents.student_id']
                )
                ->leftJoin(
                    ['GuardianInfo' => 'security_users'],
                    ['GuardianInfo.id = StudentGuardians.guardian_id']
                )
                ->where([
                    'OR' => [
                        [
                            'CURRENT_DATE >= AcademicPeriods.start_date AND CURRENT_DATE <= AcademicPeriods.end_date',
                            'InstitutionStudents.student_status_id' => 1,
                        ],
                        [
                            'InstitutionStudents.student_status_id IN' => [1, 7, 6, 8],
                        ],
                    ],
                    'InstitutionStudents.institution_id' => $entity->institution_id,
                    'InstitutionStudents.academic_period_id' => $entity->academic_period_id,
                    $where
                ])
                ->group('InstitutionStudents.student_id')
                ->toArray();

        }elseif($entity->recipient_level_id == 4 || $entity->recipient_level_id == 5 ){
            if ($entity->recipient_level_id == 1) {
            } else if ($entity->recipient_level_id == 2) {
                $where['EducationGrades.education_programme_id'] = $entity->recipient_group_id;
            } else if ($entity->recipient_level_id == 3) {
                $where['InstitutionSubjectStudents.education_grade_id'] = $entity->recipient_group_id;
            } else if ($entity->recipient_level_id == 4) {
                $where['InstitutionSubjectStudents.institution_class_id'] = $entity->recipient_group_id;
            } else if ($entity->recipient_level_id == 5) {
                $recipientGroupData = explode("-", $entity->recipient_group_id);
                $where['InstitutionSubjectStudents.institution_class_id'] = $recipientGroupData[0];
                $where['InstitutionSubjectStudents.institution_subject_id'] = $recipientGroupData[1];
            }
            $query = $InstitutionSubjectStudent->find()
            ->select([
                'student_openemis' => 'StudentInfo.openemis_no',
                'student_id' => 'InstitutionSubjectStudents.student_id',
                'student_email' => 'StudentInfo.email',
                'student_first_name' => 'StudentInfo.first_name',
                'student_last_name' => 'StudentInfo.last_name',
                'guardian_id' => 'StudentGuardians.guardian_id',
                'guardian_openemis' => 'GuardianInfo.openemis_no',
                'guardian_email' => 'GuardianInfo.email',
                'guardian_first_name' => 'GuardianInfo.first_name',
                'guardian_last_name' => 'GuardianInfo.last_name',
            ])
            ->innerJoin(
                ['EducationGrades' => 'education_grades'],
                ['EducationGrades.id = InstitutionSubjectStudents.education_grade_id']
            )
            ->innerJoin(
                ['StudentInfo' => 'security_users'],
                ['StudentInfo.id = InstitutionSubjectStudents.student_id']
            )
            ->innerJoin(
                ['AcademicPeriods' => 'academic_periods'],
                [
                    'AcademicPeriods.id = InstitutionSubjectStudents.academic_period_id',
                ]
            )
            ->leftJoin(
                ['StudentGuardians' => 'student_guardians'],
                ['StudentGuardians.student_id = InstitutionSubjectStudents.student_id']
            )
            ->leftJoin(
                ['GuardianInfo' => 'security_users'],
                ['GuardianInfo.id = StudentGuardians.guardian_id']
            )
            ->where([
                'OR' => [
                    [
                        'CURRENT_DATE >= AcademicPeriods.start_date AND CURRENT_DATE <= AcademicPeriods.end_date',
                        'InstitutionSubjectStudents.student_status_id' => 1,
                    ],
                    [
                        'InstitutionSubjectStudents.student_status_id IN' => [1, 7, 6, 8],
                    ],
                ],
                'InstitutionSubjectStudents.institution_id' => $entity->institution_id,
                'InstitutionSubjectStudents.academic_period_id' => $entity->academic_period_id,
                $where
            ])
            ->group('InstitutionSubjectStudents.student_id')
            ->toArray();
        }
        return $query;
    }
    //POCOR-8016::End
   
}
