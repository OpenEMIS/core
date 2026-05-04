<?php

namespace Institution\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use App\Model\Traits\MessagesTrait;
use Cake\Utility\Inflector; // POCOR-9274
use Cake\Datasource\EntityInterface; // POCOR-9274

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
    const GRADE = 3;
    const GRADE_CLASS = 4;
    const SUBJECT = 5;

    const HOMEROOM_TEACHER_ROLE = 5; //POCOR-9509
    const TEACHER_ROLE          = 6; //POCOR-9509
    const STAFF_ROLE            = 7; //POCOR-9509
    const STUDENT_ROLE  = 8; // POCOR-9274
    const GUARDIAN_ROLE = 9; // POCOR-9274

    //status
    const DRAFT = 0;
    const SEND = 1;
    public $recipientlevelOptions = [];

    public function initialize(array $config): void
    {
        $this->setTable('messaging');
        parent::initialize($config);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']); // POCOR-9274
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
    public function beforeAction(EventInterface $event, ArrayObject $extra)
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
    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $data)
    {
        $entity->institution_id  = $this->getInstitutionID();
    }
    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
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

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $requestData)
    {
        $this->getConnection()->begin();

        try {
            // Ensure institution ID is set
            if (!$entity->institution_id) {
                $entity->institution_id = $this->getInstitutionID();
            }

            // POCOR-9274 start

            [$role_ids, $recipient_ids] = $this->getRoleRecipientList($entity);

            // 1. Handle MessagingSecurityRoles sync
            $this->syncMessagingSecurityRoles($entity->id, $role_ids ?? []);

            // 3. Handle MessageRecipients sync
            $this->syncMessageRecipients($entity->id, $recipient_ids);
            // POCOR-9274 end
            $this->getConnection()->commit();

        } catch (\Exception $e) {
            $this->getConnection()->rollback();
            Log::error('Error in afterSave: ' . $e->getMessage());
            throw $e;
        }
    }

    private function getSecurityRolesFromEntity(Entity $entity): array
    {
        return TableRegistry::getTableLocator()->get('Security.SecurityRoles')
            ->find()
            ->where(['id IN' => $entity->security_role_id['_ids'] ?? []])
            ->extract('id') // POCOR-9274
            ->toArray();

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
        $ids = array_unique($ids); // POCOR-9274
        sort($ids); // POCOR-9274

        return $ids; // POCOR-9274
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

    public function addEditOnsendMessage(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $patchOptions, ArrayObject $extra)
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

        // POCOR-9274 start
        $no_result = false;
        if($entity->hasErrors()){
            $no_result = true;
        }
        if ($no_result) {
            $errors = $entity->getErrors(); // This includes any validation failures on save
            $this->log('Save failed. Errors: ' . print_r($errors, true), 'error');
            $this->Alert->error(__('Failed to send: Validation or Save Error.'), ['type' => 'string', 'reset' => true]);
            $event->stopPropagation();
            return false;
        }

        $methods = array_map('trim', explode(',', $entity->method));
        $MessageRecipients = self::getDynamicTableInstance('message_recipients');
        $recipients = $MessageRecipients
            ->find('all')
            ->where([$MessageRecipients->aliasField('message_id') => $entity->id])
            ->select(['recipient_id' => $MessageRecipients->aliasField('recipient_id')])
            ->disableHydration()
            ->toArray();

        if(empty($recipients)){
            $this->Alert->error(__('Failed to send: No Recipients Found.'), ['type' => 'string', 'reset' => true]);
            $event->stopPropagation();
            return $this->controller->redirect($this->url('index'));
        }

        $recipient_ids = array_unique(array_column($recipients, 'recipient_id'));;
        sort($recipient_ids);

        foreach ($methods as $method) {
            switch (strtolower($method)) {
                case 'email':
                    $sending_email_result = $this->sendEmailMessages($entity, $recipient_ids);
                    break;
                case 'sms':
                    $sending_sms_result = $this->sendSmsMessages($entity, $recipient_ids);
                    break;
                default:
                    $this->log("Unknown method '$method'", 'error');
            }
        }
        if ($sending_sms_result || $sending_email_result) {
            $entity->status = 1;
            $result = $this->save($entity);
            $this->Alert->success('Messaging.email');
            $event->stopPropagation();
            return $this->controller->redirect($this->url('index'));
        }
        $this->Alert->error(__('Failed to send: No Recipients With Contacts Found.'), ['type' => 'string', 'reset' => true]);
        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
        // POCOR-9274 end
    }
    // POCOR-8286 end

    /**
     * POCOR-9274
     * @param EntityInterface $entity
     * @param int[]           $recipientIds
     * @return bool           True if we sent at least one email
     */
    private function sendEmailMessages(EntityInterface $entity, array $recipientIds): bool
    {
        $users = $this->fetchUsers(
            $recipientIds,
            ['id', 'first_name', 'last_name', 'email'],
            'email'
        );

        if (empty($users)) {
            return false;
        }

        // Format “First Last <email>” and de-dupe by email address
        $emailList = [];
        foreach ($users as $u) {
            $emailList[$u['email']] = sprintf(
                '%s %s <%s>',
                $u['first_name'],
                $u['last_name'],
                $u['email']
            );
        }

        $this->logAlerts('Email', array_values($emailList), $entity);

        return true;
    }

    /**
     * POCOR-9274
     * @param EntityInterface $entity
     * @param int[]           $recipientIds
     * @return bool           True if we sent at least one SMS
     */
    private function sendSmsMessages(EntityInterface $entity, array $recipientIds): bool
    {
        $users = $this->fetchUsers(
            $recipientIds,
            ['id', 'mobile_number'],
            'mobile_number'
        );

        if (empty($users)) {
            return false;
        }

        // De-dupe numbers
        $phoneList = array_unique(array_column($users, 'mobile_number'));

        $this->logAlerts('SMS', $phoneList, $entity);

        return true;
    }

    /**
     * POCOR-9274
     * Load a plain array of user records (disableHydration) filtered to non-null $contactField.
     *
     * @param int[]  $ids
     * @param string[] $selectCols  e.g. ['id','first_name','email']
     * @param string $contactField  the column that must be non-null
     * @return array
     */
    private function fetchUsers(array $ids, array $selectCols, string $contactField): array
    {
        $tbl = self::getDynamicTableInstance('security_users');

        return $tbl->find()
            ->select($selectCols)
            ->where([$tbl->aliasField('id') . ' IN' => $ids])
            ->whereNotNull($tbl->aliasField($contactField))
            ->enableHydration(false)
            ->toArray();
    }

    /**
     * POCOR-9274
     * Insert one AlertLog per recipient.
     *
     * @param string           $type       'Email' or 'SMS'
     * @param string[]         $recipients e.g. [ 'joe@example.com', ... ]
     * @param EntityInterface  $entity     message entity with subject/message
     * @return void
     */
    private function logAlerts(string $type, array $recipients, EntityInterface $entity): void
    {
        $logs = self::getDynamicTableInstance('Alert.AlertLogs');
        foreach ($recipients as $to) {
            $logs->insertAlertLog(
                $type,
                'Messaging',
                $to,
                $entity->subject,
                $entity->message
            );
        }
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
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
    public function viewEditBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
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
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
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
    public function indexAfterAction(EventInterface $event, Query $query)
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
    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
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

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        if (isset($extra['selectedAcademicPeriodOptions'])) {
            $query->where([
                $this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodOptions'],
                $this->aliasField('institution_id') =>  $this->getInstitutionID()
            ], [], true);
        }
    }

    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        if ($entity->status == "Sent"
            || $entity->status == "Send"
            || $entity->status == self::SEND) {
            unset($buttons['edit']);
        }
        return $buttons;
    }
    public function onGetRecipientLevelId(EventInterface $event, Entity $entity)
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
    public function onGetRecipientGroupId(EventInterface $event, Entity $entity)
    {
        $option=$this->getRecipientGroupOptions($entity->recipient_level_id, $entity->institution_id); // POCOR-9274
        $result= $option[$entity->recipient_group_id];
        return $result;
    }
    public function onGetCreated(EventInterface $event, Entity $entity)
    {

        return date_format($entity->created, 'd M Y');
    }
    public function onGetFormButtons(EventInterface $event, ArrayObject $buttons)
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
    public function onGetStatus(EventInterface $event, Entity $entity)
    {

        if ($entity->status == self::DRAFT) {
            return __("Draft"); // POCOR-8286
        } else if ($entity->status == self::SEND) {
            return __("Sent"); // POCOR-8286
        }
    }
    public function onGetSecurityRoleId(EventInterface $event, Entity $entity)
    {
        $table = TableRegistry::getTableLocator()->get('Security.SecurityRoles');
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
    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
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
    public function onUpdateFieldRecipientLevelId(EventInterface $event, array $attr, $action, ServerRequest $request)
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
    public function onUpdateFieldRecipientGroupId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {

        if (
            $action == 'add' || $action == 'edit'
        ) {
            // POCOR-9274 start
            $alias = $this->getAlias();
            $data = $request->getData($alias);
            $entity = $attr['entity'] ?? [];
            if(empty($entity)){
            if (isset($data['recipient_level_id'])) {
                $recipient_level_id = $data['recipient_level_id'];
            }
            if (isset($data['institution_id'])) {
                $institution_id = $data['institution_id'];
            }
            }else{
                $institution_id = $entity->institution_id;
                $recipient_level_id = $entity->recipient_level_id;
            }
            if(!$institution_id){
                $institution_id = $this->getInstitutionID();
            }

            if ($recipient_level_id && $institution_id) {
                $attr['type'] = 'select';
                $attr['select'] = true;
                $data = $this->getRecipientGroupOptions($recipient_level_id, $institution_id);
                $attr['options'] = $data;
            }
        }
        // POCOR-9274 end

        return $attr;
    }
    public function onUpdateFieldSecurityRoleId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {

        // POCOR-9274 start
        $alias = $this->getAlias();
        $data = $request->getData($alias);
        $entity = $attr['entity'] ?? [];
        if(empty($entity)){
            if (isset($data['recipient_level_id'])) {
                $recipient_level_id = $data['recipient_level_id'];
            }
            if (isset($data['institution_id'])) {
                $institution_id = $data['institution_id'];
            }
        }else{
            $institution_id = $entity->institution_id;
            $recipient_level_id = $entity->recipient_level_id;
        }

        if(empty($institution_id)){
            $institution_id = $this->getInstitutionID();
        }
        if(empty($institution_id)){
            return [];
        }
        if(empty($recipient_level_id)){
            return [];
        }
        $securityRoleIds = self::getInstitutionSecurityRoleIds($institution_id, $recipient_level_id);
        if(empty($securityRoleIds)){
            return [];
        }

        $SecurityRoles = TableRegistry::getTableLocator()->get('Security.SecurityRoles');
        $options = $SecurityRoles->find('list', [
            'keyField' => 'id',
            'valueField' => 'name',
        ])->where([$SecurityRoles->aliasField('id IN') => $securityRoleIds])
            ->toArray();
        $attr['type'] = 'chosenSelect';
        $attr['attr']['multiple'] = true;
        $attr['options'] = $options;
        $attr['attr']['required'] = true;

        //POCOR-9509: warn for institution scope — super-admin without institution link won't receive
        if ((int) $recipient_level_id === self::INSTITUTION) {
            $attr['after'] = __('Note: Only users who have the selected role assigned to this institution via a security group will receive this message. System administrators or users with roles not linked to this institution will not be included.');
        }

        return $attr;
        // POCOR-9274 end
    }

    /**
     * POCOR-9274
     * @param $institution_id
     * @return array
     */
    private static function getInstitutionSecurityRoleIds($institution_id, $recipient_level_id)
    {
        if (in_array($recipient_level_id, [self::PROGRAMME, self::GRADE])) {
            return [
                self::STUDENT_ROLE,
                self::GUARDIAN_ROLE,
            ];
        }

        //POCOR-9509: Class and Subject scope — only roles that can be linked to a class
        if (in_array($recipient_level_id, [self::GRADE_CLASS, self::SUBJECT])) {
            return [
                self::HOMEROOM_TEACHER_ROLE,
                self::TEACHER_ROLE,
                self::STAFF_ROLE,
                self::STUDENT_ROLE,
                self::GUARDIAN_ROLE,
            ];
        }
        $securityGroupInstitutions = self::getDynamicTableInstance('security_group_institutions');
        $distinctResults = $securityGroupInstitutions
            ->find('all')
            ->innerJoin(['SecurityGroupUsers' => 'security_group_users'], [
                [
                    'SecurityGroupUsers.security_group_id = ' . $securityGroupInstitutions->aliasField('security_group_id'),
                ]
            ])
            ->select(['security_role_id' => 'SecurityGroupUsers.security_role_id'])
            ->distinct(['SecurityGroupUsers.security_role_id'])
            ->where(['institution_id' => $institution_id])
            ->disableHydration()
            ->toArray();

        $distinctResultsValues = array_column($distinctResults, 'security_role_id');

        if (sizeof($distinctResultsValues) > 0) {
            $distinctResultsValues[] =  self::GUARDIAN_ROLE;
            $uniqu_array = array_unique($distinctResultsValues);
        } else {
            $uniqu_array = [0];
        };
        sort($uniqu_array);
        return $uniqu_array;
    }

    /**
     * POCOR-9162 added
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName
     * @return \Cake\ORM\Table
     */
    private static function getDynamicTableInstance(string $tableName): Table
    {
        // Parse plugin and table names if dot notation is used
        $locator = TableRegistry::getTableLocator();
        try {
            return $locator->get($tableName);
        } catch (\Exception $exception) {

        }
        $parts = explode('.', $tableName);
        $plugin = count($parts) > 1 ? $parts[0] : null;
        $table = count($parts) > 1 ? $parts[1] : $parts[0];

        // Convert the table name to camel case as expected by CakePHP conventions
        $tableFullAlias = Inflector::camelize($tableName);
        $tableAlias = Inflector::camelize($table);

        // Create the fully qualified class name if a plugin is specified
        if ($plugin) {
            $className = $plugin . '\\Model\\Table\\' . $tableAlias . 'Table';
        } else {
            $className = 'App\\Model\\Table\\' . $tableAlias . 'Table';
        }
        // Check if the table instance already exists
        if (!$locator->exists($tableFullAlias)) {
            // Check if the specific table class exists
            if (!class_exists($className)) {
                $className = Table::class; // Fallback to generic Table class
            }

            // Configure a new table instance
            $locator->setConfig($tableAlias, [
                'className' => $className,
                'table' => $table,
                'alias' => $tableAlias,
            ]);
        }

        // Return the table instance
        return $locator->get($tableFullAlias);
    }


    public function onUpdateFieldMessage(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $attr['type'] = 'text';
        return $attr;
    }
    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'edit' || $action == "add") {

            $selectedPeriod  = $this->getSelectedAcademicPeriod($this->request->getQuery('period'));
            $attr['attr']['value'] = $this->AcademicPeriods->get($selectedPeriod)->name;
            $attr['type'] = 'readonly';
            $attr['value'] = $selectedPeriod;
        }
        return $attr;
    }

    /**
     * POCOR-9274 start
     * @param $recipient_level_id
     * @param $institution_id
     * @return array
     */
    public function getRecipientGroupOptions($recipient_level_id, $institution_id){

        $academicPeriodId =TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods')->getCurrent();
        $institutions = $this->Institutions;
        if ($institution_id) {
            try {
                $institution = $institutions->get($institution_id);
            } catch (\Exception $exception) {
                Log::debug($exception->getMessage());
            }
            $institution_name = $institution->name;
        }
        $option=[];
        switch ($recipient_level_id) {
            case self::INSTITUTION:
            case "Institution":
                $option[$institution_id] = $institution_name; // POCOR-9274 end
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
        $InstitutionGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades');
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
        $InstitutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
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
    public function onUpdateFieldMethod(EventInterface $event, array $attr, $action, ServerRequest $request)
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

    /**
     * POCOR-9274
     * @param $entity
     * @return array
     */
    public function getRoleRecipientList($entity)
    {
        $role_ids = $this->getSecurityRolesFromEntity($entity);
        $allRecipients = [];
        foreach ($role_ids as $roleId) {
            if (in_array($roleId, [self::STUDENT_ROLE, self::GUARDIAN_ROLE], true)) {
                // students & guardians
                $rows = $this->getStudentGuardianRecipients($entity);
            } else {
                // staff
                $rows = $this->getStaffRecipients($entity, $roleId);

            }
            // flatten
            $allRecipients = array_merge($allRecipients, $rows);
        }


        $student_ids = array_values(array_filter(array_column($allRecipients, 'student_id')));
        $security_user_ids = array_values(array_filter(array_column($allRecipients, 'security_user_id')));
        $guardian_ids = array_values(array_filter(array_column($allRecipients, 'guardian_id'))); //POCOR-9509: filter NULLs from leftJoin when student has no guardian

        $recipient_ids = array_unique(array_merge($student_ids, $security_user_ids, $guardian_ids));
        // otherwise assume staff
        sort($recipient_ids);

        return [$role_ids, $recipient_ids];
    }

    //POCOR-8016::modify query Start
    // POCOR-8286 start
    public function getStudentGuardianRecipients($entity)
    {

        // POCOR-9274 start
        // POCOR-9274 start
        $where = $this->buildStudentGuardianRecipientWhere($entity);

        $isSubjectLevel = in_array($entity->recipient_level_id, [self::GRADE_CLASS, self::SUBJECT]);

        $tableName = $isSubjectLevel ? 'Institution.InstitutionSubjectStudents'
            : 'Institution.InstitutionStudents';
        $Table = self::getDynamicTableInstance($tableName);
        // POCOR-9274 end
        $aliasPrefix = $isSubjectLevel ? 'InstitutionSubjectStudents' : 'InstitutionStudents';

        $query = $Table->find()
            ->select([
                'student_id' => $aliasPrefix . '.student_id',
                'guardian_id' => 'StudentGuardians.guardian_id',
            ])
            ->innerJoin(
                ['EducationGrades' => 'education_grades'],
                ['EducationGrades.id = ' . $aliasPrefix . '.education_grade_id']
            )->innerJoin(
                ['AcademicPeriods' => 'academic_periods'],
                ['AcademicPeriods.id = ' . $aliasPrefix . '.academic_period_id']
            )
            ->innerJoin(['SU' => 'security_users'], ['SU.id = ' . $aliasPrefix . '.student_id']) //POCOR-9509: skip students deleted from security_users
            ->leftJoin(
                ['StudentGuardians' => 'student_guardians'],
                ['StudentGuardians.student_id = ' . $aliasPrefix . '.student_id']
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
            ->group([$aliasPrefix . '.student_id', 'StudentGuardians.guardian_id'])
            ->toArray();
        // POCOR-9274 end
        return $query;
    }

    /**
     * // POCOR-9274 start
     * @param $entity
     * @param $roleId
     * @return array
     */
    protected function getStaffRecipients($entity, $roleId)
    {
        switch ((int)$entity->recipient_level_id) {
            case self::INSTITUTION:
                return $this->recipientsByInstitutionStaff($entity, $roleId);
//            case self::PROGRAMME: // Only students or guardians
//            case self::GRADE:
//                // aggregate all classes & subjects in that programme/grade
//                return $this->recipientsByProgrammeOrGradeStaff($entity, $roleId);
            case self::GRADE_CLASS:
                return $this->recipientsByClassStaff($entity, $roleId);
            case self::SUBJECT:
                return $this->recipientsBySubjectStaff($entity, $roleId);
            default:
                return [];
        }
    }

    protected function recipientsBySubjectStaff($e, $role_id)
    {
        $Subjects = self::getDynamicTableInstance('Institution.InstitutionSubjects');
        $parts = explode('-', $e->recipient_group_id);
        if (count($parts) === 2) {
            $subject_id = $parts[1];
        } else {
            return [];
        }
        $SubjectStaff = self::getDynamicTableInstance('Institution.InstitutionSubjectStaff');
        $staffQuery = $SubjectStaff->find('all')
            ->select(['security_user_id' => $SubjectStaff->aliasField('staff_id')])
            ->innerJoin([$Subjects->getAlias() => $Subjects->getTable()],
                [$SubjectStaff->aliasField('institution_subject_id') . ' = ' . $Subjects->aliasField('id')])
            ->innerJoin(['SGI' => 'security_group_institutions'],
                ['SGI.institution_id = ' . $Subjects->aliasField('institution_id')])
            ->innerJoin(['SGU' => 'security_group_users'],
                ['SGI.security_group_id = SGU.security_group_id',
                    'SGU.security_user_id = ' . $SubjectStaff->aliasField('staff_id')])
            ->innerJoin(['SU' => 'security_users'], ['SU.id = ' . $SubjectStaff->aliasField('staff_id')]) //POCOR-9509: skip orphaned security_group_users rows
            ->where([$SubjectStaff->aliasField('institution_subject_id = ') . $subject_id,
                'SGU.security_role_id = ' . $role_id]);

        $staff = $staffQuery->toArray();
        return $staff;
    }
    protected function recipientsByClassStaff($e, $role_id)
    {
        // primary teacher
        $Classes = self::getDynamicTableInstance('Institution.InstitutionClasses');
        $primary = $Classes->find('all')
            ->select(['security_user_id' => $Classes->aliasField('staff_id')])
            ->innerJoin(['SGI' => 'security_group_institutions'],
                ['SGI.institution_id = ' . $Classes->aliasField('institution_id')])
            ->innerJoin(['SGU' => 'security_group_users'],
                ['SGI.security_group_id = SGU.security_group_id',
                    'SGU.security_user_id = ' . $Classes->aliasField('staff_id')])
            ->innerJoin(['SU' => 'security_users'], ['SU.id = ' . $Classes->aliasField('staff_id')]) //POCOR-9509: skip orphaned security_group_users rows
            ->where([$Classes->aliasField('id = ') . $e->recipient_group_id,
                'SGU.security_role_id = ' . $role_id]);

        // secondary teachers
        $Secondary = self::getDynamicTableInstance('institution_classes_secondary_staff');
        $secondary = $Secondary->find('all')
            ->select(['security_user_id' => $Secondary->aliasField('secondary_staff_id')])
            ->innerJoin([$Classes->getAlias() => $Classes->getTable()],
                [$Secondary->aliasField('institution_class_id') . ' = ' . $Classes->aliasField('id')])
            ->innerJoin(['SGI' => 'security_group_institutions'],
                ['SGI.institution_id = ' . $Classes->aliasField('institution_id')])
            ->innerJoin(['SGU' => 'security_group_users'],
                ['SGI.security_group_id = SGU.security_group_id',
                    'SGU.security_user_id = ' . $Secondary->aliasField('secondary_staff_id')])
            ->innerJoin(['SU' => 'security_users'], ['SU.id = ' . $Secondary->aliasField('secondary_staff_id')]) //POCOR-9509: skip orphaned security_group_users rows
            ->where([$Secondary->aliasField('institution_class_id') => $e->recipient_group_id,
                'SGU.security_role_id' => $role_id]);
        return $primary->union($secondary)
            ->toArray();
    }

    protected function recipientsByInstitutionStaff($e, $role_id)
    {
        $SGU = self::getDynamicTableInstance('security_group_users');
        $result = $SGU->find('all')
            ->select([
                'security_user_id'    => $SGU->aliasField('security_user_id'),
            ])
            ->innerJoin(
                ['SGI'=>'security_group_institutions'],
                ['SGI.security_group_id = ' . $SGU->aliasField('security_group_id')]
            )
            ->innerJoin(['SU' => 'security_users'], ['SU.id = ' . $SGU->aliasField('security_user_id')]) //POCOR-9509: skip orphaned security_group_users rows
            ->where([
                $SGU->aliasField('security_role_id') => $role_id,
                'SGI.institution_id' => $e->institution_id,
                ])
            ->group([$SGU->aliasField('security_user_id')])
            ->toArray();
        return $result;
    }

    private function buildStudentGuardianRecipientWhere($entity): array
    {
        $where = [];

        switch ((int) $entity->recipient_level_id) {
            case self::PROGRAMME: // POCOR-9274
                $where['EducationGrades.education_programme_id'] = $entity->recipient_group_id;
                break;

            case self::GRADE: // POCOR-9274
                $where['InstitutionStudents.education_grade_id'] = $entity->recipient_group_id;
                break;

            case self::GRADE_CLASS: // POCOR-9274
                $where['InstitutionSubjectStudents.institution_class_id'] = $entity->recipient_group_id;
                break;

            case self::SUBJECT: // POCOR-9274
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
