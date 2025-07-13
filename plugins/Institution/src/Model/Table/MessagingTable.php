<?php

namespace Institution\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use ArrayObject;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
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
use Cake\Http\Session;
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

    public function initialize(array $config): void
    {
        $this->setTable('messaging');
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
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['Messaging' =>['id', 'academic_period_id']
            ]
        ]);
    }
     public function validationDefault(Validator $validator): Validator {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        return  $validator // POCOR-8286 start
            ->notEmptyArray('security_role_id')
            ->notEmptyString('recipient_level_id')
            ->notEmptyString('recipient_group_id')
            ->add('security_role_id', 'custom', [
                'rule' => function ($value, $context) {
                    return (!empty($value['_ids']) && is_array($value['_ids']));
                },
                'message' => __('This field cannot be left empty')
            ])
            ->add('method', 'notEmptyString', [
                'rule' => function ($value) {
                    return !empty($value) && is_string($value);
                },
                'message' => __('This field cannot be left empty')
            ])
            ->notEmptyString('subject')
            ->notEmptyArray('message');
         // POCOR-8286 end
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
        $this->field('method', [ // POCOR-8286
            'after' => 'recipient_group_id',
//            'entity' => $entity,
        ]);
        $this->field('security_role_id',['visible' => ['index' => false, 'view' => true, 'edit' => false, 'add' => true]]);
        $this->field('subject',['sort'=>false]);
        $this->field('status', ['visible' => ['index' => true, 'view' => true, 'edit' => false, 'add' => false]]);

    }
    public function beforeSave(Event $event, Entity $entity, ArrayObject $data)
    {
        $entity->institution_id  = $this->getInstitutionID();
    }
    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id');
        $this->field('security_role_id', ['entity' => $entity, 'visible' => true]);
        $this->field('message');
        $this->field('recipient_level_id', ['entity' => $entity]);
        $this->field('recipient_group_id', ['entity' => $entity]);
        $this->field('method', ['entity' => $entity, // POCOR-8286
            'after' => 'recipient_group_id',
            'attr' => ['required' => true]
        ]);

        $this->setFieldOrder(['academic_period_id',
            'recipient_level_id',
            'recipient_group_id',
            'security_role_id',
            'method', // POCOR-8286
            'subject', 'message']);
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $requestData)
    {
        $this->getConnection()->begin();

        try {
            // Ensure institution ID is set
            if (!$entity->institution_id) {
                $entity->institution_id = $this->getInstitutionID();
            }

            // Parse methods
            $methods = array_map('trim', explode(',', $entity->method ?? ''));
            $roles = $this->getSecurityRolesFromEntity($entity);
            $recipients = $this->getRecipientList($entity);

            // 1. Handle MessagingSecurityRoles sync
            $this->syncMessagingSecurityRoles($entity->id, $entity->security_role_id['_ids'] ?? []);

            // 2. Build final recipient list based on method(s)
            $finalRecipientIds = $this->getRecipientIdsByMethod($methods, $roles, $recipients);
//            dd([$methods, $roles, $recipients, $finalRecipientIds]);
            // 3. Handle MessageRecipients sync
            $this->syncMessageRecipients($entity->id, $finalRecipientIds);

            $this->getConnection()->commit();

        } catch (\Exception $e) {
            $this->getConnection()->rollback();
            Log::error('Error in afterSave: ' . $e->getMessage());
            throw $e;
        }
    }

    private function getSecurityRolesFromEntity(Entity $entity): array
    {
        return TableRegistry::get('Security.SecurityRoles')
            ->find()
            ->where(['id IN' => $entity->security_role_id['_ids'] ?? []])
            ->extract('code')
            ->map(function ($val) {
                return strtolower($val);
            })
            ->toArray();

//        return $lowered;
    }

    private function getRecipientIdsByMethod(array $methods, array $roles, array $recipients): array
    {
        $ids = [];

        foreach ($methods as $method) {
            switch (strtolower($method)) {
                case 'email':
                    $ids = array_merge($ids, $this->getEmailRecipientIds($recipients, $roles));
                    break;

                case 'sms':
                    $ids = array_merge($ids, $this->getSmsRecipientIds($recipients, $roles));
                    break;

                default:
                    Log::warning("Unknown messaging method: $method");
            }
        }

        return array_unique($ids);
    }

    private function getEmailRecipientIds(array $recipients, array $roles): array
    {
        $ids = [];

        foreach ($recipients as $person) {
            if (in_array('student', $roles) && !empty($person['student_email'])) {
                $ids[] = $person['student_id'];
            }

            if (in_array('guardian', $roles) && !empty($person['guardian_email'])) {
                $ids[] = $person['guardian_id'];
            }
        }

        return $ids;
    }

    private function getSmsRecipientIds(array $recipients, array $roles): array
    {
        $ids = [];

        foreach ($recipients as $person) {
            if (in_array('student', $roles) && !empty($person['student_phone'])) {
                $ids[] = $person['student_id'];
            }

            if (in_array('guardian', $roles) && !empty($person['guardian_phone'])) {
                $ids[] = $person['guardian_id'];
            }
        }

        return $ids;
    }

    private function syncMessagingSecurityRoles(int $messageId, array $newRoleIds): void
    {
        $existingRoleIds = $this->MessagingSecurityRoles
            ->find()
            ->where(['message_id' => $messageId])
            ->extract('security_role_id')
            ->toArray();

        $toAdd = array_diff($newRoleIds, $existingRoleIds);
        $toRemove = array_diff($existingRoleIds, $newRoleIds);

        if ($toRemove) {
            $this->MessagingSecurityRoles->deleteAll([
                'message_id' => $messageId,
                'security_role_id IN' => $toRemove
            ]);
        }

        foreach ($toAdd as $roleId) {
            $entity = $this->MessagingSecurityRoles->newEntity([
                'message_id' => $messageId,
                'security_role_id' => $roleId
            ]);
            $this->MessagingSecurityRoles->save($entity);
        }
    }

    private function syncMessageRecipients(int $messageId, array $newRecipientIds): void
    {
        $existingIds = $this->MessageRecipients
            ->find()
            ->where(['message_id' => $messageId])
            ->extract('recipient_id')
            ->toArray();

        $toAdd = array_diff($newRecipientIds, $existingIds);
        $toRemove = array_diff($existingIds, $newRecipientIds);

        if ($toRemove) {
            $this->MessageRecipients->deleteAll([
                'message_id' => $messageId,
                'recipient_id IN' => $toRemove
            ]);
        }

        foreach ($toAdd as $recipientId) {
            $entity = $this->MessageRecipients->newEntity([
                'message_id' => $messageId,
                'recipient_id' => $recipientId
            ]);
            $this->MessageRecipients->save($entity);
        }
    }

    public function addEditOnsendMessage(Event $event, Entity $entity, ArrayObject $data, ArrayObject $patchOptions, ArrayObject $extra)
    {
        // POCOR-8286 start
        if (!$entity->institution_id) {
            $entity->institution_id = $this->getInstitutionID();
        }
        if ($entity->status == 1) {
            $this->Alert->warning('Message Already Sent', ['type' => 'string', 'reset' => true]);
            $event->stopPropagation();
            return $this->controller->redirect($this->url('index'));
        }
        $patchOptions['validate'] = true;
        $entity = $this->patchEntity($entity, $data->getArrayCopy(), $patchOptions->getArrayCopy());

        $result = $this->save($entity);

        if (!$result) {
            $errors = $entity->getErrors(); // This includes any validation failures on save
            $this->log('Save failed. Errors: ' . print_r($errors, true), 'error');

            $this->Alert->error(__('Failed to send: Validation or Save Error.'), ['type' => 'string', 'reset' => true]);

            return;
        }

        $methods = array_map('trim', explode(',', $entity->method));
        $recipientList = $this->getRecipientList($entity);
        $SecurityRoles = [];

        foreach ($entity->security_role_id['_ids'] as $key => $value) {
            $SecurityRoles[] = strtolower(TableRegistry::get('Security.SecurityRoles')->get($value)->code);
        }
        foreach ($methods as $method) {
            switch (strtolower($method)) {
                case 'email':
                    $this->sendEmailMessages($entity, $recipientList, $SecurityRoles);
                    break;

                case 'sms':
                    $this->sendSmsMessages($entity, $recipientList, $SecurityRoles);
                    break;

                default:
                    $this->log("Unknown method '$method'", 'error');
            }
        }
        if ($entity->status === 1) {
            $result = $this->save($entity);
            $this->Alert->success('Messaging.email');
            $event->stopPropagation();
            return $this->controller->redirect($this->url('index'));
        }
    }
    // POCOR-8286 end


////    public function addEditOnsendMessage(Event $event, Entity $entity, ArrayObject $data, ArrayObject $patchOptions, ArrayObject $extra)
//    {
//
//        $entity->institution_id = $this->getInstitutionID();
//        $patchOptions['validate'] = true;
//        $entity = $this->patchEntity($entity, $data->getArrayCopy(), $patchOptions->getArrayCopy());
//        $entity->recipient_group_id = $data['Messaging']['recipient_group_id'];
//
//        $AlertLogs = TableRegistry::get('Alert.AlertLogs');
//        $query = $this->getRecipientList($entity);
//        $SecurityRoles = [];
//
//        foreach ($entity->security_role_id['_ids'] as $key => $value) {
//            $SecurityRoles[] = strtolower(TableRegistry::get('Security.SecurityRoles')->get($value)->code);
//        }
//        //for sending email and inserting message logs
//        $emailList = [];
//        if (!empty($query)) {
//            foreach ($query as $key => $studentData) {
//                if (in_array("student", $SecurityRoles)) {
//                    if (!empty($studentData->student_email)) {
//                        $email = $studentData->student_email;
//                        $name = $studentData->student_first_name . " " . $studentData->student_last_name;
//                        $recipient = $name . ' <' . $email . '>';
//                        if (!in_array($recipient, $emailList)) {
//                            $emailList[] = $recipient;
//                        }
//                    }
//                }
//                if (in_array("guardian", $SecurityRoles)) {
//                    if (!empty($studentData->guardian_email)) {
//                        $email = $studentData->guardian_email;
//                        $name = $studentData->guardian_first_name . " " . $studentData->guardian_last_name;
//                        $recipient = $name . ' <' . $email . '>';
//                        if (!in_array($recipient, $emailList)) {
//                            $emailList[] = $recipient;
//                        }
//                    }
//                }
//            }
//        }
//        if (!empty($emailList)) {
//            foreach ($emailList as $key => $value) {
//                $emailSubject = $entity->subject;
//                $emailMessage = $entity->message;
//                $AlertLogs->insertAlertLog("Email", "Messaging", $value, $emailSubject, $emailMessage);
//            }
//        }
//        $entity->status = 1;
//        $result = $this->save($entity);
//        $this->Alert->success('Messaging.email');
//        $event->stopPropagation();
//        return $this->controller->redirect($this->url('index'));
//    }

    // POCOR-8286
    private function sendEmailMessages(&$entity, $recipientList, $SecurityRoles): void
    {
        $emailList = [];

        foreach ($recipientList as $studentData) {
            if (in_array("student", $SecurityRoles) && !empty($studentData->student_email)) {
                $emailList[] = $this->formatEmail($studentData->student_first_name, $studentData->student_last_name, $studentData->student_email);
            }
            if (in_array("guardian", $SecurityRoles) && !empty($studentData->guardian_email)) {
                $emailList[] = $this->formatEmail($studentData->guardian_first_name, $studentData->guardian_last_name, $studentData->guardian_email);
            }
        }

        $emailList = array_unique($emailList);

        $AlertLogs = TableRegistry::get('Alert.AlertLogs');
        foreach ($emailList as $recipient) {
            $AlertLogs->insertAlertLog("Email", "Messaging", $recipient, $entity->subject, $entity->message);
        }
        if($emailList){
            $entity->status = 1;
        }
    }

    // POCOR-8286
    private function sendSmsMessages(&$entity, $recipients, $SecurityRoles): void
    {
        $phoneList = [];
        $AlertLogs = TableRegistry::get('Alert.AlertLogs');

        foreach ($recipients as $studentData) {
            if (in_array("student", $SecurityRoles) && !empty($studentData->student_phone)) {
                $phoneList[] = $studentData->student_phone;
            }
            if (in_array("guardian", $SecurityRoles) && !empty($studentData->guardian_phone)) {
                $phoneList[] = $studentData->guardian_phone;
            }
        }

        $phoneList = array_unique($phoneList);

        foreach ($phoneList as $recipient) {
            // Assuming you have a working sendTwilioSms method
            $AlertLogs->insertAlertLog("SMS", "Messaging", $recipient, $entity->subject, $entity->message);

//            $this->sendTwilioSms($number, $entity->message);
        }
        if($phoneList){
            $entity->status = 1;
        }
    }

    // POCOR-8286 start
    private function formatEmail($firstName, $lastName, $email)
    {
        return $firstName . ' ' . $lastName . ' <' . $email . '>';
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

    // POCOR-8286
    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
//        if (isset($data['submit']) && $data['submit'] == 'save' || $data['submit'] == 'Send') {
        // POCOR-8286 start
        if (isset($data['method'])
            && !empty($data['method'])
            && isset($data['method']['_ids'])
            && !empty($data['method']['_ids'])
        ) {
            $data['method'] = implode(',', array_map('trim', $data['method']['_ids']));
        }
//        }
    }
    public function indexAfterAction(Event $event, Query $query)
    {
        $this->field('message', ['visible' => false]);
        $this->field('academic_period_id', ['visible' => false]);
        $this->setFieldOrder(['created',
            'created_user_id',
            'academic_period_id',
            'recipient_level_id',
            'recipient_group_id',
            'security_role_id',
            'method', // POCOR-8286
            'subject', 'message']);

    }
    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $extra['selectedAcademicPeriodOptions'] = $this->getSelectedAcademicPeriod($this->request);
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $extra['elements']['control'] = [
            'name' => 'Institution.Messaging/controls',
            'data' => [
                'encodedQueryString' => $encodedQueryString,
                'periodOptions' => $academicPeriodOptions,
                'selectedPeriod' => $extra['selectedAcademicPeriodOptions']
            ],
            'order' => 3
        ];
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        if (isset($extra['selectedAcademicPeriodOptions'])) {
            $query->where([
                $this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodOptions'],
                $this->aliasField('institution_id') =>  $this->getInstitutionID()
            ], [], true);
        }
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        if ($entity->status == "Sent"
            || $entity->status == "Send"
            || $entity->status == self::SEND) {
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
        $institution_id = $this->getInstitutionID();
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
                    //'name' => '<i class="fa fa-check"></i>' . __('Send'),
                    'name' =>  __('Send'),
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
            return __("Draft"); // POCOR-8286
        } else if ($entity->status == self::SEND) {
            return __("Sent"); // POCOR-8286
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
            case 'academic_period_id':
                return __('Academic Period');
            case 'created_user_id':
                return __('Created By');
            case 'created':
                return __('Created');
            case 'institution_id':
                return __('Institution');
            case 'recipient_level_id':
                return __('Recipient Level');
            case 'recipient_group_id':
                return __('Recipient Group');
            case 'subject':
                return __('Subject');
             case 'message':
                return __('Message');
             case 'security_role_id':
                return __('Security Role');
            case 'status':
                return __('Message status');
            case 'modified_user_id':
                return __('Modified By');
            case 'modified':
                return __('Modified ');

            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
    public function onUpdateFieldRecipientLevelId(Event $event, array $attr, $action, ServerRequest $request)
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
    public function onUpdateFieldRecipientGroupId(Event $event, array $attr, $action, ServerRequest $request)
    {

        if (
            $action == 'add' || $action == 'edit'
        ) {
            $recipient_level_id =$request->getData()['Messaging']['recipient_level_id'];
            if($action == "edit"){
                if(!empty($request->getAttribute('params')['pass'][1])){
                    $entity = $this->get($this->paramsDecode($request->getAttribute('params')['pass'][1])['id']);
                }else{
                    $entity = $this->get($this->paramsDecode($this->request->getAttribute('params')['?']['queryString'])['id']);
                }
                $recipient_level_id = $entity->recipient_level_id;
            }
            $attr['type'] = 'select';
            $attr['select'] = true;
            $data = $this->getRecipientGroupOptions($recipient_level_id);
            $attr['options']=$data;
        }

        return $attr;
    }
    public function onUpdateFieldSecurityRoleId(Event $event, array $attr, $action, ServerRequest $request)
    {

        $entity = $attr['entity'];
        $SecurityRoles = TableRegistry::get('Security.SecurityRoles');
        $options = $SecurityRoles->find('list', [
            'keyField' => 'id',
            'valueField' => 'name',
        ])->toArray();
        $attr['type'] = 'chosenSelect';
        $attr['attr']['multiple'] = true;
        $attr['options'] = $options;
        $attr['attr']['required'] = true;
        return $attr;
    }
    public function onUpdateFieldMessage(Event $event, array $attr, $action, ServerRequest $request)
    {
        $attr['type'] = 'text';
        return $attr;
    }
    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'edit' || $action == "add") {

            $selectedPeriod  = $this->getSelectedAcademicPeriod($this->request->getQuery('period'));
            $attr['attr']['value'] = $this->AcademicPeriods->get($selectedPeriod)->name;
            $attr['type'] = 'readonly';
            $attr['value'] = $selectedPeriod;
        }
        return $attr;
    }
    public function getRecipientGroupOptions($recipient_level_id){

        $institution_id=$this->getInstitutionID();
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
        if ($this->action == 'index' || $this->action == 'view' ||$this->action == 'edit'
        ) {
            if (!is_null($this->request->getQuery()) && array_key_exists('period', $this->request->getQuery())
            ) {
                $selectedAcademicPeriod = $this->request->getQuery('period');
            } else {
                $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
            }
        } elseif ($this->action == 'add') {
            $selectedAcademicPeriod = $selectedAcademicPeriod = $period = $this->request->getQuery('period') === null ? $this->AcademicPeriods->getCurrent() : $this->request->getQuery('period');
        }

        return $selectedAcademicPeriod;
    }

    // POCOR-8286
    public function getMethods()
    {
        $methods = ['Email', 'SMS'];
//        if (!empty($feature)) {
//            $alertTypeDetails = $this->getAlertTypeDetailsByFeature($feature);
//            $method = $alertTypeDetails[$feature]['method'];
//        }

        return $methods;
    }

    // POCOR-8286
    public function onUpdateFieldMethod(Event $event, array $attr, $action, ServerRequest $request)
    {

        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];
            // POCOR-8286 start

            $methods = $this->getMethods();

            if (!is_array($methods)) {
                $attr['type'] = 'readonly';
            } else {
                $attr['type'] = 'chosenSelect';
                $attr['options'] = array_combine($methods, $methods);
                $attr['required'] = true;

                // NEW: parse the string into array
                if (!empty($entity->method)) {
                    $attr['value'] = explode(',', $entity->method);
                    $attr['attr']['value'] = $attr['value'];
                }

            }
            // POCOR-8286 end

        }

        return $attr;
    }

    //POCOR-8016::modify query Start
    // POCOR-8286 start
    public function getRecipientList($entity)
    {

        $where = $this->buildRecipientWhere($entity);

        $isSubjectLevel = in_array($entity->recipient_level_id, [4, 5]);

        $tableName = $isSubjectLevel ? 'Institution.InstitutionSubjectStudents' : 'Institution.InstitutionStudents';
        $Table = TableRegistry::get($tableName);

        $aliasPrefix = $isSubjectLevel ? 'InstitutionSubjectStudents' : 'InstitutionStudents';

        $query = $Table->find()
            ->select([
                'student_openemis' => 'StudentInfo.openemis_no',
                'student_id' => $aliasPrefix . '.student_id',
                'student_email' => 'StudentInfo.email',
                'student_phone' => 'StudentInfo.mobile_number',
                'student_first_name' => 'StudentInfo.first_name',
                'student_last_name' => 'StudentInfo.last_name',
                'guardian_id' => 'StudentGuardians.guardian_id',
                'guardian_openemis' => 'GuardianInfo.openemis_no',
                'guardian_email' => 'GuardianInfo.email',
                'guardian_phone' => 'GuardianInfo.mobile_number',
                'guardian_first_name' => 'GuardianInfo.first_name',
                'guardian_last_name' => 'GuardianInfo.last_name',
            ])
            ->innerJoin(
                ['EducationGrades' => 'education_grades'],
                ['EducationGrades.id = ' . $aliasPrefix . '.education_grade_id']
            )
            ->innerJoin(
                ['StudentInfo' => 'security_users'],
                ['StudentInfo.id = ' . $aliasPrefix . '.student_id']
            )
            ->innerJoin(
                ['AcademicPeriods' => 'academic_periods'],
                ['AcademicPeriods.id = ' . $aliasPrefix . '.academic_period_id']
            )
            ->leftJoin(
                ['StudentGuardians' => 'student_guardians'],
                ['StudentGuardians.student_id = ' . $aliasPrefix . '.student_id']
            )
            ->leftJoin(
                ['GuardianInfo' => 'security_users'],
                ['GuardianInfo.id = StudentGuardians.guardian_id']
            )
            ->where([
                'OR' => [
                    [
                        'CURRENT_DATE >= AcademicPeriods.start_date AND CURRENT_DATE <= AcademicPeriods.end_date',
                        $aliasPrefix . '.student_status_id' => 1,
                    ],
                    [
                        $aliasPrefix . '.student_status_id IN' => [1, 6, 7, 8],
                    ],
                ],
                $aliasPrefix . '.institution_id' => $entity->institution_id,
                $aliasPrefix . '.academic_period_id' => $entity->academic_period_id,
                $where
            ])
            ->group($aliasPrefix . '.student_id')
            ->toArray();

        return $query;
    }

    private function buildRecipientWhere($entity): array
    {
        $where = [];

        switch ((int) $entity->recipient_level_id) {
            case 2:
                $where['EducationGrades.education_programme_id'] = $entity->recipient_group_id;
                break;

            case 3:
                $where['InstitutionStudents.education_grade_id'] = $entity->recipient_group_id;
                break;

            case 4:
                $where['InstitutionSubjectStudents.institution_class_id'] = $entity->recipient_group_id;
                break;

            case 5:
                $parts = explode('-', $entity->recipient_group_id);
                if (count($parts) === 2) {
                    $where['InstitutionSubjectStudents.institution_class_id'] = $parts[0];
                    $where['InstitutionSubjectStudents.institution_subject_id'] = $parts[1];
                }
                break;
        }

        return $where;
    }
    //POCOR-8016::End
}
