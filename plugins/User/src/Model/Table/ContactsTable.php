<?php

namespace User\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Http\ServerRequest;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use App\Model\Traits\OptionsTrait;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\RulesChecker;

class ContactsTable extends ControllerActionTable
{
    use OptionsTrait;

    private $ContactOptionsTable;

    // POCOR-8080-1
    // I've checked, the old code used old CODES. This is just for reference
    // [MOB] => 1 [PHO] => 2 [FAX] => 3 [EMA] => 4 [EMG] => 5 [FBK] => 6 [TGM] => 7 [WHA] => 8 [OTH] => 9
    private $contactOptionsArray;

    public function initialize(array $config): void
    {
        $this->setTable('user_contacts');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('ContactTypes', ['className' => 'User.ContactTypes']);
        $this->addBehavior(
            'Institution.InstitutionTab',
            [
                'implementedMethods' =>
                [
                    'setUserTabElements' => 'setUserTabElements',
                ],
            ]
        );
        $this->addBehavior('User.SetupTab');
        $this->addBehavior('User.UserTab');

        $this->ContactOptionsTable = TableRegistry::getTableLocator()->get('User.ContactOptions');
        $this->contactOptionsArray = $this->ContactOptionsTable->findCodeList();
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {

        $this->field('description', []);
        $this->field('contact_type_id', ['visible' => false]);

        $this->setFieldOrder(['description', 'value', 'preferred']);

        // Start POCOR-5188
        if ($this->request->getParam('controller') == 'Staff') {
            $is_manual_exist = $this->getManualUrl('Institutions', 'Contacts', 'Staff - General');
            if (!empty($is_manual_exist)) {
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target' => '_blank'
                ];

                $helpBtn['url'] = $is_manual_exist['url'];
                $helpBtn['type'] = 'button';
                $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
                $helpBtn['attr'] = $btnAttr;
                $helpBtn['attr']['title'] = __('Help');
                $extra['toolbarButtons']['help'] = $helpBtn;
            }
        } elseif ($this->request->getParam('controller') == 'Students') {
            $is_manual_exist = $this->getManualUrl('Institutions', 'Contacts', 'Students - General');
            if (!empty($is_manual_exist)) {
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target' => '_blank'
                ];

                $helpBtn['url'] = $is_manual_exist['url'];
                $helpBtn['type'] = 'button';
                $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
                $helpBtn['attr'] = $btnAttr;
                $helpBtn['attr']['title'] = __('Help');
                $extra['toolbarButtons']['help'] = $helpBtn;
            }
        } elseif ($this->request->getParam('controller') == 'Directories') {
            $is_manual_exist = $this->getManualUrl('Directory', 'Contacts', 'General');
            if (!empty($is_manual_exist)) {
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target' => '_blank'
                ];

                $helpBtn['url'] = $is_manual_exist['url'];
                $helpBtn['type'] = 'button';
                $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
                $helpBtn['attr'] = $btnAttr;
                $helpBtn['attr']['title'] = __('Help');
                $extra['toolbarButtons']['help'] = $helpBtn;
            }
        } elseif ($this->request->getParam('controller') == 'Profiles') {
            $is_manual_exist = $this->getManualUrl('Personal', 'Contacts', 'General');
            if (!empty($is_manual_exist)) {
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target' => '_blank'
                ];

                $helpBtn['url'] = $is_manual_exist['url'];
                $helpBtn['type'] = 'button';
                $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
                $helpBtn['attr'] = $btnAttr;
                $helpBtn['attr']['title'] = __('Help');
                $extra['toolbarButtons']['help'] = $helpBtn;
            }
        }
        // End POCOR-5188


    }

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        // POCOR-8080-1
        // they need entity to set value in EDIT or restart
        $this->field('contact_option_id', ['type' => 'select', 'entity' => $entity]);
        $this->field('contact_type_id', ['type' => 'select', 'entity' => $entity]);
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        if ($this->request->getParam('controller') == 'Staff') {
            $userId = $this->getUserID();
            $this->field('security_user_id', ['attr' => ['value' => $userId], 'type' => 'hidden']);
        }
        $this->fields['preferred']['type'] = 'select';
        $this->fields['preferred']['options'] = $this->getSelectOptions('general.yesno');
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        //to check if contact is new for its type. if yes, then set as preferred
        // POCOR-9760: Respect the user's preferred choice for new email contacts.
        // Email sync is handled separately in afterSave().
        //POCOR-9760 --end
        if ($entity->isNew() && $entity->contact_option_id != $this->contactOptionsArray['EMA']) {
            $contactOption = $entity->contact_option_id;
            $contacts = $this->find()
                ->matching('ContactTypes', function ($q) use ($contactOption) {
                    return $q->where(['ContactTypes.contact_option_id' => $contactOption]);
                })
                ->where([
                    $this->aliasField('security_user_id') => $entity->security_user_id
                ]);
            if (empty($contacts->toArray())) {
                $entity->preferred = 1;
            }
        }
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        //if preferred set, then unset other preferred for the same contact option
        // POCOR-8080-1
        // ->dirty changed to ->isDirty
        if (($entity->isDirty('preferred') && $entity->preferred == 1) || $entity->preferred == 1) {
            $contactOption = $entity->contact_option_id;
            $contacts = $this->find()
                ->matching('ContactTypes', function ($q) use ($contactOption) {
                    return $q->where(['ContactTypes.contact_option_id' => $contactOption]);
                })
                ->where([
                    $this->aliasField('id !=') => $entity->id,
                    $this->aliasField('security_user_id') => $entity->security_user_id
                ]);

            if (!empty($contacts->toArray())) {
                foreach ($contacts->toArray() as $key => $value) {
                    $value->preferred = 0;
                    $this->save($value);
                }
            }

            // POCOR-8080-1
            // I've checked the new code
            //POCOR-8660 update mobile no in security user table
            $isSyncableOption = $contactOption == $this->contactOptionsArray['MOB'] || $contactOption == $this->contactOptionsArray['PHO'];
            $isEmailOption = $contactOption == $this->contactOptionsArray['EMA'];

            //POCOR-9760 --start
            //Cache the current email for sync and history checks.
            $currentEmailOnFile = null;
            if ($isEmailOption) {
                $Users = TableRegistry::getTableLocator()->get('User.Users');
                $currentUser = $Users->find()
                    ->select(['email'])
                    ->where([$Users->aliasField($Users->getPrimaryKey()) => $entity->security_user_id])
                    ->first();
                $currentEmailOnFile = $currentUser->email ?? null;
            }

            // POCOR-9760: Preserve the old synced email when an existing contact is edited.
            if ($isEmailOption && !$entity->isNew() && !empty($currentEmailOnFile) && $currentEmailOnFile !== $entity->value) {
                $this->preserveEmailInContacts($entity->security_user_id, $currentEmailOnFile, $entity->contact_type_id);
            }
            //POCOR-9760 --end

            // POCOR-9760: Sync email only when it meets the preferred/uniqueness rules.
            if ($isSyncableOption || ($isEmailOption && $this->shouldSyncPreferredEmail($entity, $currentEmailOnFile))) {
                $key = array_search($contactOption, $this->contactOptionsArray);
                $entity->set('contact_option_code', $key);
                //update information on security user table
                $listeners = [
                    TableRegistry::getTableLocator()->get('User.Users')
                ];

                $this->dispatchEventToModels('Model.UserContacts.onChange', [$entity], $this, $listeners);
                unset($entity['contact_option_code']);
            }
            //POCOR-8660 end
        }
    }

    //POCOR-9760 --start
    /**
     * Checks whether a preferred email can be synced to security_users.email.
     * New contacts cannot replace an existing email, and the email must be unique.
    */
    private function shouldSyncPreferredEmail(Entity $entity, $currentEmailOnFile): bool
    {
        if ((int)$entity->preferred !== 1) {
            return false;
        }

        if ($entity->isNew() && !empty($currentEmailOnFile)) {
            return false;
        }

        $Users = TableRegistry::getTableLocator()->get('User.Users');
        $emailTakenByAnotherUser = $Users->exists([
            'email' => $entity->value,
            $Users->aliasField($Users->getPrimaryKey()) . ' !=' => $entity->security_user_id,
        ]);

        return !$emailTakenByAnotherUser;
    }
    //POCOR-9760 --end

    //POCOR-9760 --start
    /**
     * Keeps an email from disappearing out of the user's contact history when
     * security_users.email is about to change or be cleared - if no user_contacts
     * row for this user still holds that value, insert one with Preferred = No.
     */
    private function preserveEmailInContacts($securityUserId, $email, $contactTypeId): void
    {
        if (empty($email) || empty($contactTypeId)) {
            return;
        }

        $stillExists = $this->exists([
            $this->aliasField('security_user_id') => $securityUserId,
            $this->aliasField('value') => $email,
        ]);

        if ($stillExists) {
            return;
        }

        $backupContact = $this->newEntity([
            'contact_option_id' => $this->contactOptionsArray['EMA'],
            'contact_type_id' => $contactTypeId,
            'value' => $email,
            'preferred' => 0,
            'security_user_id' => $securityUserId,
        ]);
        $this->save($backupContact);
    }
    //POCOR-9760 --end

    public function afterDelete(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $contactOption = $this->getContactOptionID($entity);
        $isEmailOption = $contactOption == $this->contactOptionsArray['EMA'];

        //POCOR-9760 --start
        // Clear security_users.email when the synced email is deleted.
        // Do not preserve it as history; deletion is intentional.
        if ($isEmailOption && $entity->preferred == 1) {
            $Users = TableRegistry::getTableLocator()->get('User.Users');
            $currentUser = $Users->find()
                ->select(['email'])
                ->where([$Users->aliasField($Users->getPrimaryKey()) => $entity->security_user_id])
                ->first();

            if (!empty($currentUser) && $currentUser->email === $entity->value) {
                $Users->updateAll(
                    ['email' => null],
                    [$Users->aliasField($Users->getPrimaryKey()) => $entity->security_user_id]
                );
            }
        }
        //POCOR-9760 --end

        if ($entity->preferred == 1) { //if the preferred contact deleted

            $query = $this->find()
                ->matching('ContactTypes', function ($q) use ($contactOption) {
                    return $q->where(['ContactTypes.contact_option_id' => $contactOption]);
                })
                ->where([
                    $this->aliasField('security_user_id') => $entity->security_user_id,
                ])
                ->order($this->aliasField('created') . ' DESC')
                ->first();

            if (!empty($query)) {
                $this->updateAll(
                    ['preferred' => 1],
                    ['id' => $query->id]
                );
                // POCOR-8080-1
                // I've checked the new code
                if ($isEmailOption) { //if the deleted contact option is email
                    //POCOR-9760 --start
                    // POCOR-9760: Sync the promoted email only if it passes the uniqueness check.
                    $query->preferred = 1;
                    if ($this->shouldSyncPreferredEmail($query, null)) {
                        //update information on security user table
                        $listeners = [
                            TableRegistry::getTableLocator()->get('User.Users')
                        ];
                        $this->dispatchEventToModels('Model.UserContacts.onChange', [$query], $this, $listeners);
                    }
                    //POCOR-9760 --end
                }
            }
        }
    }

    public function validationDefault(Validator $validator): Validator
    {
        // POCOR-8080-1
        $validator->setProvider('custom', $this) //POCOR-8080 here is the
            ->setStopOnFailure()
            ->requirePresence('contact_option_id')
            ->notEmpty('contact_option_id')
            ->requirePresence('contact_type_id')
            ->notEmpty('contact_type_id')
            ->requirePresence('value')
            ->notEmpty('value')
            ->add('value', 'ruleContactValuePattern', [
                'rule' => ['validateContactValuePattern'],
                'provider' => 'table',
                'last' => true,
                'on' => function ($context) {
                    //only trigger validation when contact_type_id has value
                    $contactTypeId = '';
                    if (array_key_exists('contact_type_id', $context['data'])) {
                        $contactTypeId = $context['data']['contact_type_id'];
                    }
                    return ($contactTypeId);
                },
            ])
            ->add('value', 'ruleValidateNumeric', [
                'rule' => ['numericPositive'],
                'provider' => 'table',
                'on' => function ($context) {
                    $contactTypeId = $context['data']['contact_type_id'];
                    // POCOR-8080-1 start
                    // I've cleaned the new code
                    $contactOptionId = (isset($context['data']['contact_option_id'])) ? $context['data']['contact_option_id'] : null;
                    if (!is_numeric($contactOptionId)) {
                        $contactOption = $this->ContactOptionsTable
                            ->find('all')
                            ->select(['id' => $this->ContactOptionsTable->aliasField('id')])
                            ->where([$this->ContactOptionsTable->aliasField('name') => $contactOptionId])
                            ->first();
                        if ($contactOption) {
                            $contactOptionId = $contactOption->id;
                        }
                    }

                    $query = $this->ContactTypes
                        ->find()
                        ->where([
                            $this->ContactTypes->aliasField($this->ContactTypes->getPrimaryKey()) => $contactTypeId,
                            $this->ContactTypes->aliasField('validation_pattern') . ' IS NOT NULL'
                        ])
                        ->count();

                    if ($query > 0) {
                        $contactOptionId = 0;
                    }
                    $in_array = false;
                    if (in_array($contactOptionId, [$this->contactOptionsArray['MOB'], $this->contactOptionsArray['PHO'], $this->contactOptionsArray['FAX']])) {
                        $in_array = true;
                    };

                    return $in_array;
                    // POCOR-8080-1 end
                },
            ])
            ->add('value', 'ruleValidateEmail', [
                'rule' => ['email'],
                'on' => function ($context) {
                    // POCOR-8080-1 start
                    // I've cleaned the new code
                    $contactOptionId = (isset($context['data']['contact_option_id'])) ? $context['data']['contact_option_id'] : null;
                    if (!is_numeric($contactOptionId)) {
                        $contactOption = $this->ContactOptionsTable
                            ->find('all')
                            ->select(['id' => $this->ContactOptionsTable->aliasField('id')])
                            ->where([$this->ContactOptionsTable->aliasField('name') => $contactOptionId])
                            ->first();
                        if ($contactOption) {
                            $contactOptionId = $contactOption->id;
                        }
                    }
                    return ($contactOptionId == $this->contactOptionsArray['EMA']);
                    // POCOR-8080-1 end
                },
            ])
            ->add('value', 'ruleValidateEmergency', [
                'rule' => 'notBlank',
                'on' => function ($context) {
                    // POCOR-8080-1 start
                    // I've cleaned the new code
                    $contactOptionId = (isset($context['data']['contact_option_id'])) ? $context['data']['contact_option_id'] : null;
                    if (!is_numeric($contactOptionId)) {
                        $contactOption = $this->ContactOptionsTable
                            ->find('all')
                            ->select(['id' => $this->ContactOptionsTable->aliasField('id')])
                            ->where([$this->ContactOptionsTable->aliasField('name') => $contactOptionId])
                            ->first();
                        if ($contactOption) {
                            $contactOptionId = $contactOption->id;
                        }
                    }
                    return ($contactOptionId == $this->contactOptionsArray['EMG']);
                    // POCOR-8080-1 end
                },
            ])
            ->add('preferred', 'ruleValidatePreferred', [
                'rule' => ['validateContact'],
            ])
            ->add('value', 'unique', [
            'rule' => function ($value, $context) {
                if (!isset($context['data']['contact_type_id'])) {
                    return false;
                }

                $contactTypeId = $context['data']['contact_option_id'];

                // POCOR-9760 -- start: Duplicate emails are allowed; uniqueness only controls email sync.
                if ($contactTypeId == 4) {
                    return true;
                }
                //POCOR-9760 --end

                if ($contactTypeId != 1) {
                    return true;
                }

                $users = TableRegistry::getTableLocator()->get('User.Users');
                if (!$users) {
                    throw new \RuntimeException('Users table could not be found.');
                }

                $userId = $context['data']['security_user_id'] ?? null;
                $query = $users->find()->where(['mobile_number' => $value]);

                // Exclude the current user if editing
                if (!empty($userId)) {
                    $query->where(['id !=' => $userId]);
                }

                // Fetch the first matching record
                $existing = $query->first();

                return empty($existing); // Return true if no duplicate is found
            },
            'message' => 'This Record is already in use.'
        ]); //POCOR-8911 add validation email, mobile_number
        return $validator;
    }

    public function onUpdateFieldContactOptionId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {

        // POCOR-8080-1 start
        if ($action == 'add') {
            $contactOptions = $this->ContactOptionsTable
                ->find('list')
                ->find('order')
                ->toArray();
            $attr['options'] = $contactOptions;
            $attr['onChangeReload'] = 'changeContactOption';
        }
        if ($action == 'edit') {
            $entity = $attr['entity'];
            $contactTypeId = $entity->contact_type_id;
            $contactOptionID = $this->ContactTypes
                ->find('all')
                ->select('contact_option_id')
                ->where([$this->ContactTypes->aliasField('id') => $contactTypeId])
                ->first();

            $contact_option_id = $contactOptionID['contact_option_id'];
            $contactOption = $this->ContactOptionsTable
                ->find('all')
                ->select('name')
                ->where([
                    $this->ContactOptionsTable->aliasField('id') => $contact_option_id
                ])->first();
            //POCOR-8660 start
            // $attr['value'] = $contactOption->name;
            $attr['value'] = $contact_option_id;
            // POCOR-8660 end
            $attr['attr']['value'] = $contactOption->name;
            $attr['type'] = 'readonly';
            // dd( $attr);


        }
        // POCOR-8080-1 end
        return $attr;
    }

    public function onUpdateFieldContactTypeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {

        // POCOR-8080-1 start
        $queryData = $request->getData();
        $alias = $this->getAlias();
        $contactOptionId = null;
        $entity = $attr['entity'];
        if ($action == 'add' || $action == 'edit') {
            if (isset($queryData[$alias])) {
                if (
                    isset($queryData[$alias]['contact_option_id']) &&
                    is_numeric($queryData[$alias]['contact_option_id'])
                ) {
                    $contactOptionId = $queryData[$alias]['contact_option_id'];
                }
            }
            if (!$contactOptionId) {
                $entity = $attr['entity'];
                $contactTypeId = $entity->contact_type_id;
                if ($contactTypeId) {
                    $contactOption = $this->ContactTypes
                        ->find('all')
                        ->select('contact_option_id')
                        ->where([$this->ContactTypes->aliasField('id') => $contactTypeId])
                        ->first();
                    $contactOptionId = $contactOption['contact_option_id'];
                }
            }
            if ($contactOptionId) {
                $contactTypes = $this->ContactTypes
                    ->find('list')
                    ->find('order')
                    ->where([$this->ContactTypes->aliasField('contact_option_id') => $contactOptionId])
                    ->toArray();
            } else {
                $contactTypes = [];
            }
            $attr['value'] = $entity->contact_type_id;
            $attr['attr']['value'] = $attr['value'];
            $attr['options'] = $contactTypes;
        }
        // POCOR-8080-1 end

        return $attr;
    }

    public
    function addEditOnChangeContactOption(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        // POCOR-8080-1 start
        $alias = $this->getAlias();
        $newContactOption = null;
        if (isset($data[$alias])) {
            $newContactOption = $data[$alias]['contact_option_id'];
        }
        if (!$newContactOption) {
            return;
        }
        $param = 'contact_option_id';
        $value = $newContactOption;
        $this->addQueryParam($param, $value);
        // POCOR-8080-1 end
    }

    /*POCOR-6267 Starts*/
    public
    function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $userId = $this->getUserID();
        $query->where([$this->aliasField('security_user_id IS') => $userId])->orderDesc('preferred');

        $query->where([$this->aliasField('security_user_id IS') => $userId]);
    }

    /*POCOR-6267 Ends*/

    public
    function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
    }

    /**
     * @param Entity $entity
     * @return mixed
     */
    private function getContactOptionID(Entity $entity)
    {
        return $this->ContactTypes->get($entity->contact_type_id)->contact_option_id;
    }
}
