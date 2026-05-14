<?php

namespace User\Model\Table;

use Exception;
use DateTime;
use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\I18n\Time;
use App\Model\Traits\OptionsTrait;

use App\Model\Table\ControllerActionTable;
use Cake\Log\Log;

class IdentitiesTable extends ControllerActionTable
{
    const ISPREFERRED = 1;
    use OptionsTrait;

    //POCOR-9590: cached per-request for sync_status column rendering — avoids N+1 queries on index, single-fetch on view/edit
    private $_syncCacheLoaded = false; //POCOR-9590: sentinel — null on the two fields below is a valid loaded state
    private $_indexSyncStatus = null; //POCOR-9590: security_users.sync_status for the current user
    private $_indexActiveIdentityTypeId = null; //POCOR-9590: identity_type_id from active external data source, null when no source active

    public function initialize(array $config): void
    {
        $this->setTable('user_identities');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('IdentityTypes', ['className' => 'FieldOption.IdentityTypes']);
        $this->belongsTo('Nationalities', ['className' => 'FieldOption.Nationalities']);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Students' => ['index', 'add'],
            'Staff' => ['index', 'add']
        ]);
        $this->addBehavior('Institution.InstitutionTab',
            ['implementedMethods' => [
                'setUserTabElements' => 'setUserTabElements',
            ],
            ]);
        $this->addBehavior('User.SetupTab');
        $this->addBehavior('User.UserTab');
        $this->excludeDefaultValidations(['security_user_id']);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.Users.afterSave' => 'afterSaveUsers'
        ];

        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        //$this->log('beforeSave', 'debug');
        //$this->log($entity, 'debug');
        //POCOR-9663 start
        $duplicateMessage = $this->checkDuplicateIdentity($entity);
        if ($duplicateMessage != "") {
            $entity->setError('number', $duplicateMessage);
            return false; // stop save
        }  //POCOR-9663 end
        $options = [];
        $options['identity_type_id'] = $entity->identity_type_id;
        $options['identity_number'] = $entity->number;
        if ($entity->isNew()) {
            $userID = $this->getUserID();
            if ($userID) {
                $entity['security_user_id'] = $userID;
            }
        }
        //POCOR-9590: server-side mirror of the readonly UI rule. Block updates to number /
        //identity_type_id on rows whose identity_type is an external lookup key — closes the
        //"swap NIN then re-sync to steal identity" vector even when the request bypasses the form.
        if (!$entity->isNew()
            && $this->isExternalLookupIdentityType((int) $entity->identity_type_id)
            && ($entity->isDirty('number') || $entity->isDirty('identity_type_id'))
        ) {
            Log::write(
                'warning',
                'POCOR-9590: blocked update to external-lookup identity row '
                . $entity->id . ' by user ' . $this->getUserID()
            );
            $event->stopPropagation();
            return false;
        }
        $message = $this->checkCustomIdentityNumber($options);
        if ($message != "") {
            $message = __('Wrong identity number');
            $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
            $event->stopPropagation();
        }
    }

    public function afterSaveUsers(EventInterface $event, Entity $entity)
    {
        //$this->log('beforeSage', 'debug');
        //$this->log($entity, 'debug');
        //whichever identity type and number that came from import user, will be treat as new identity user record.
        $options = [];
        $identity_type_id = $entity->identity_type_id;
        $nationality_id = $entity->nationality_id;
        $identity_number = $entity->identity_number;
        if(!$identity_number || !$nationality_id || !$identity_type_id){
            return;
        }
        $options['identity_type_id'] = $identity_type_id;
        $options['identity_number'] = $identity_number;

        $message = $this->checkCustomIdentityNumber($options);
        if ($message == "") {

            $userIdentityEntity = $this->newEntity([

                'nationality_id' => $nationality_id,
                'identity_type_id' => $identity_type_id,
                'number' => $identity_number,
                'security_user_id' => $entity->id,
                'created_user_id' => 1,
                'created' => new Time()
            ]);
            $this->save($userIdentityEntity);
        }
    }

    private function checkCustomIdentityNumber($options)
    {
        $pattern = '';

        if (isset($options['identity_type_id']) && !empty($options['identity_type_id'])) {
            $identityTypeId = $options['identity_type_id'];
        } else {
            return "";
        }
        if (isset($options['identity_number']) && !empty($options['identity_number'])) {
            $identityNumber = $options['identity_number'];
        } else {
            return "";
        }

        $IdentityTypes = TableRegistry::getTableLocator()->get('FieldOption.IdentityTypes');
        $IdentityTypesData = $IdentityTypes
            ->find()
            ->where([$IdentityTypes->aliasField('id') => $identityTypeId])
            ->first();

        if (!empty($IdentityTypesData->validation_pattern)) {
            $pattern = '/' . $IdentityTypesData->validation_pattern . '/';
        }

        // custom validation is nullable, have to cater for the null pattern.
        if (!empty($pattern) && !preg_match($pattern, $identityNumber)) {
            return __("Please enter a valid Identity Number");
        }

        return "";
    }

    public function beforeAction($event, ArrayObject $extra)
    {
        $UserNationalityTable = TableRegistry::getTableLocator()->get('User.UserNationalities');
        $users = TableRegistry::getTableLocator()->get('User.Users');
        $userId = $this->getUserID();
        if(empty($userId)) {
            $queryString = $this->getQueryString();
            if (isset($queryString['security_user_id'])) {
                $userId = $queryString['security_user_id'];
            }
        }
        /*POCOR-6396 starts*/
        if ($this->action == 'add' || $this->action == 'edit') {
            $checkUserNationality = $UserNationalityTable->find()
                ->where([$UserNationalityTable->aliasField('security_user_id') => $userId])
                ->first();
            if (!empty($checkUserNationality)) {
                $usersOptions = $users->find('list', [
                    'keyField' => '_matchingData.MainNationalities.id',
                    'valueField' => '_matchingData.MainNationalities.name'
                ])
                    ->matching('MainNationalities')
                    ->where([$users->aliasField('id') => $userId])
                    ->toArray();
                $UsersNationalityOptions = $UserNationalityTable
                    ->find('list', [
                        'keyField' => '_matchingData.NationalitiesLookUp.id',
                        'valueField' => '_matchingData.NationalitiesLookUp.name'
                    ])
                    ->matching('NationalitiesLookUp')
                    ->where([$UserNationalityTable->aliasField('security_user_id') => $userId])
                    ->toArray();
                $NationalityOptions = array_unique($usersOptions + $UsersNationalityOptions);
            }
        }
        /*POCOR-6396 starts*/
        if($this->request->getParam('controller') == 'Staff') {
            $this->field('security_user_id', ['attr' => ['value' => $userId], 'type' => 'hidden']);
        }
        $this->fields['identity_type_id']['type'] = 'select';
        $this->fields['nationality_id']['type'] = 'select';
        $this->fields['nationality_id']['options'] = (!empty($NationalityOptions)) ? $NationalityOptions : ['' => $this->getMessage('general.select.noOptions')]; //POCOR-6396
         // POCOR-8664 start
         $this->fields['preferred']['type'] = 'select';
         $this->fields['preferred']['options'] = $this->getSelectOptions('general.yesno');
         // POCOR-8664 end
        //POCOR-9590: virtual "Synced" field — visible on index/view/edit (readonly), hidden on add. Value computed in onGetSyncStatus().
        $this->field('sync_status', [
            'type' => 'string',
            'visible' => ['index' => true, 'view' => true, 'edit' => true, 'add' => false],
        ]);
        $this->setFieldOrder(['identity_type_id', 'nationality_id', 'number', 'preferred', 'sync_status', 'issue_date', 'expiry_date', 'issue_location', 'comments']); //POCOR-9590: sync_status inserted after preferred

    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->fields['comments']['visible'] = 'false';

        // Start POCOR-5188
        if ($this->request->getParam('controller') == 'Staff') {
            $is_manual_exist = $this->getManualUrl('Institutions', 'Identities', 'Staff - General');
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
            $is_manual_exist = $this->getManualUrl('Institutions', 'Identities', 'Students - General');
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
            $is_manual_exist = $this->getManualUrl('Directory', 'Identities', 'General');
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
            $is_manual_exist = $this->getManualUrl('Personal', 'Identities', 'General');
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

    /*POCOR-6267 Starts*/
    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $userId = $this->getUserID();
        $query->where([$this->aliasField('security_user_id') => $userId]);
    }

    /*POCOR-6267 Ends*/

    public function editOnInitialize(EventInterface $event, Entity $entity)
    {
        // set the defaultDate to false on initialize, for the empty date.
        if (empty($entity->issue_date)) {
            $this->fields['issue_date']['default_date'] = false;
        }

        if (empty($entity->expiry_date)) {
            $this->fields['expiry_date']['default_date'] = false;
        }

        //POCOR-9590: sync_status is system-managed — render as readonly Yes/No on edit
        $this->fields['sync_status']['type'] = 'readonly';

        //POCOR-9590: identity types registered as an external-source lookup key (e.g. NIN) are
        //set-once. The number is editable on insert (admission/data-entry) but immutable on
        //subsequent edits — otherwise the row can be retargeted to another person's NIN and
        //re-synced to import their identity (impersonation by swap).
        if (!$entity->isNew() && $this->isExternalLookupIdentityType((int) $entity->identity_type_id)) {
            $this->fields['number']['type'] = 'readonly';
            $this->fields['identity_type_id']['type'] = 'readonly';
        }
    }

    //POCOR-9590: true when this identity_type_id is registered as the lookup key for any
    //configured external data source. Used by IdentitiesTable + Laravel API guards.
    public function isExternalLookupIdentityType(int $identityTypeId): bool
    {
        if ($identityTypeId <= 0) {
            return false;
        }
        $ExternalAttrs = TableRegistry::getTableLocator()->get('Configuration.ExternalDataSourceAttributes');
        return $ExternalAttrs->find()
            ->where([
                'attribute_field' => 'identity_type_id',
                'value' => (string) $identityTypeId,
            ])
            ->count() > 0;
    }

    public function validationDefault(Validator $validator): Validator
    {
        $requestData = $_REQUEST;

        // POCOR-9404 start
        if (isset($requestData['ImportStudentAdmission'])) {
            $importStudentAdmission = $requestData['ImportStudentAdmission'];

            if (!empty($importStudentAdmission['feature'])) {
                $feature      = $importStudentAdmission['feature'];
                $featureParts = explode('.', $feature);
                $featureName  = end($featureParts);

                // Apply this validation ONLY if not ImportStudentAdmission
                if ($featureName !== 'ImportStudentAdmission') {
                    $validator = parent::validationDefault($validator);
                    $validator->setProvider('custom', $this);

                    $validator
                        ->add('issue_date', 'ruleCompareDate', [
                            'rule' => ['compareDate', 'expiry_date', false],
                            'provider' => 'custom',
                            'on'   => 'create',
                        ])
                        ->allowEmptyDate('issue_date')
                        ->allowEmptyDate('expiry_date')
                        ->add('identity_type_id', 'ruleCustomIdentityType', [
                            'rule'     => ['validateCustomIdentityType'],
                            'provider' => 'table',
                        ])
                        ->add('number', 'ruleCustomIdentityNumber', [
                            'rule'     => ['validateCustomIdentityNumber'],
                            'provider' => 'table',
                            'last'     => true,
                        ])
                        ->add('number', 'ruleUnique', [
                            'rule'     => ['validateUnique', ['scope' => 'identity_type_id']],
                            'provider' => 'table',
                            'message'  => __('This identity number already exists for the nationality'),
                        ])
                        ->notEmpty('nationality_id'); // POCOR-5987
                }
            }
        } elseif (!empty($requestData['Identities'])) { // POCOR-9404
            $validator = parent::validationDefault($validator);
            $validator->setProvider('custom', $this);

            return $validator
                 ->add('issue_date', 'ruleCompareDate', [
                    'rule' => ['compareDate', 'expiry_date', false]
                ])
                ->add('expiry_date', [
                ])
                ->add('identity_type_id', 'ruleCustomIdentityType', [
                    'rule'     => ['validateCustomIdentityType'],
                    'provider' => 'table',
                ])
                ->add('number', 'ruleCustomIdentityNumber', [
                    'rule'     => ['validateCustomIdentityNumber'],
                    'provider' => 'table',
                    'last'     => true,
                ])
                ->add('number', 'ruleUnique', [
                    'rule'     => ['validateUnique', ['scope' => 'identity_type_id']],
                    'provider' => 'table',
                    'message'  => __('This identity number already exists for the nationality'),
                ])
                ->notEmpty('nationality_id'); // POCOR-5987

        } else { // POCOR-9663
            $validator = parent::validationDefault($validator);
            $validator->setProvider('custom', $this);

            return $validator
                 ->add('issue_date', 'ruleCompareDate', [
                    'rule' => ['compareDate', 'expiry_date', false]
                ])
                ->add('expiry_date', [
                ])
                ->add('identity_type_id', 'ruleCustomIdentityType', [
                    'rule'     => ['validateCustomIdentityType'],
                    'provider' => 'table',
                ])
                ->add('number', 'ruleCustomIdentityNumber', [
                    'rule'     => ['validateCustomIdentityNumber'],
                    'provider' => 'table',
                    'last'     => true,
                ])
                ->add('number', 'ruleUnique', [
                    'rule'     => ['validateUnique', ['scope' => 'identity_type_id']],
                    'provider' => 'table',
                    'message'  => __('This identity number already exists for the nationality'),
                ])
                ->notEmpty('nationality_id'); // POCOR-5987
        }

        return $validator;
    }

    public function validationAddByAssociation(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        return $validator->requirePresence('security_user_id', false);
    }

    public function validationNonMandatory(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        return $validator->allowEmpty('number');
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        Log::debug(__FUNCTION__ . '1');
        if (!empty($entity->nationality_id)) {
            $nationalitiesLookUp = TableRegistry::getTableLocator()->get('FieldOption.Nationalities')->get($entity->nationality_id);
            // if($nationalitiesLookUp->identity_type_id == $entity->identity_type_id){
            if ($nationalitiesLookUp) {
                $user = TableRegistry::getTableLocator()->get('User.Users');
                $preferredNationality = TableRegistry::getTableLocator()->get('User.UserNationalities')
                    ->find()
                    ->where(['nationality_id' => $entity->nationality_id,
                        'preferred' => self::ISPREFERRED
                    ])
                    ->first();
                Log::debug(__FUNCTION__ . '2');

                $userDetail = $user->get($entity->security_user_id);
                if (!empty($preferredNationality)) {
                    $userDetail->nationality_id = $entity->nationality_id;
                }
                $userDetail->identity_type_id = $entity->identity_type_id;
                $userDetail->identity_number = $entity->number;
                Log::debug(__FUNCTION__ . '3');

                $user->save($userDetail);
            }
        }
        try {
            $Users = TableRegistry::getTableLocator()->get('User.Users');
            //echo "<pre>";print_r($this->request);die();
            $result = $Users
                ->find()
                ->select(['identity_number', 'identity_type_id'])
                ->where(['id' => $entity->security_user_id])
                ->first();
            if ((($result['identity_number'] == null || $result['identity_number'] == '') && ($result['identity_type_id'] == null || $result['identity_type_id'] == ''))) {
                $Users->updateAll(
                    ['identity_number' => $entity->number,
                        'identity_type_id' => $entity->identity_type_id],    //field
                    ['id' => $entity->security_user_id] //condition
                );
            }
            Log::debug(__FUNCTION__ . '4');

        } catch (\Exception $e) {
        }

        // POCOR-8664 start
        // $listeners = [
        //     TableRegistry::getTableLocator()->get('User.Users')
        // ];
        // $this->dispatchEventToModels('Model.UserIdentities.onChange', [$entity], $this, $listeners);

        if (($entity->isDirty('preferred') && $entity->preferred == 1) || $entity->preferred == 1) {
            $identity = $this->find()
                ->where([
                    $this->aliasField('id !=') => $entity->id,
                    // $this->aliasField('nationality_id') => $entity->nationality_id,
                    $this->aliasField('security_user_id') => $entity->security_user_id
                ]);
            if (!empty($identity->toArray())) {
                foreach ($identity->toArray() as $key => $value) {
                    $value->preferred = 0;
                    $this->save($value);
                }
            }
            $listeners = [
                TableRegistry::getTableLocator()->get('User.Users')
            ];
            $this->dispatchEventToModels('Model.UserIdentities.onChange', [$entity], $this, $listeners);
        }
        //POCOR-8664 end
    }

    public function afterDelete(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $listeners = [
            TableRegistry::getTableLocator()->get('User.Users')
        ];
        $this->dispatchEventToModels('Model.UserIdentities.onChange', [$entity], $this, $listeners);
    }

    public function getLatestDefaultIdentityNo($userId)
    {
        //check identity type that ties to the nationality
        $UserNationalityTable = TableRegistry::getTableLocator()->get('User.UserNationalities');

        $nationalityId = null;
        $identityType = $UserNationalityTable
            ->find()
            ->matching('NationalitiesLookUp')
            ->select(['nationality_id', 'identityTypeId' => 'NationalitiesLookUp.identity_type_id'])
            ->where([
                'security_user_id' => $userId,
                'preferred' => self::ISPREFERRED
            ])
            ->first();

        //get the latest record according to identity type
        $result = null;
        if ($identityType) {
            $nationalityId = $identityType->nationality_id;
            $result = $this
                ->find()
                ->where([
                    $this->aliasField('security_user_id') => $userId,
                    $this->aliasField('identity_type_id') => $identityType->identityTypeId
                ])
                ->order('created DESC')
                ->first();
        }

        if (!empty($result)) {
            return ['nationality_id' => $nationalityId, 'identity_type_id' => $result->identity_type_id, 'identity_no' => $result->number];
        } else {
            return ['nationality_id' => $nationalityId, 'identity_type_id' => null, 'identity_no' => null];
        }
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'identity_type_id') {
            return __('Identity Type');
        } elseif ($field == 'nationality_id') {
            return __('Nationality');
        } elseif ($field == 'number') {
            return __('Number');
        } elseif ($field == 'issue_date') {
            return __('Issue Date');
        } elseif ($field == 'expiry_date') {
            return __('Expiry Date');
        } elseif ($field == 'issue_location') {
            return __('Issuer');
        } elseif ($field == 'comments') {
            return __('Comments');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Modified By');
        } elseif ($field == 'created') {
            return __('Created On');
        } elseif ($field == 'sync_status') {
            return __('Synced'); //POCOR-9590: column header label
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    //POCOR-9590: start — per-row "Synced" value (index/view/edit). Same eligibility rule as
    //plugins/User/templates/Element/UserIdentities/details.php line 16:
    //  eligible = preferred==1 AND identity_type_id == active_source_identity_type_id
    //  Yes iff eligible AND user.sync_status == SYNC_STATUS_SYNCED — otherwise No.
    public function onGetSyncStatus(EventInterface $event, Entity $entity): string
    {
        $this->loadSyncStatusCache(); //POCOR-9590: lazy single-shot — covers index (N rows, 1 load) + view/edit (1 row, 1 load)
        $activeTypeId = $this->_indexActiveIdentityTypeId;
        $eligible = ($entity->preferred == 1)
            && ($activeTypeId !== null)
            && ((int)$entity->identity_type_id === (int)$activeTypeId);
        if ($eligible && $this->_indexSyncStatus === \User\Model\Behavior\UserBehavior::SYNC_STATUS_SYNCED) {
            return __('Yes');
        }
        return __('No');
    }

    //POCOR-9590: load user sync_status + active external identity_type_id once per request.
    //Re-fires are no-ops thanks to the null guards — safe for both index (called N times) and view/edit (called once).
    private function loadSyncStatusCache(): void
    {
        if ($this->_syncCacheLoaded) {
            return;
        }
        $SecurityUsers = TableRegistry::getTableLocator()->get('Security.Users');
        $userRow = $SecurityUsers->find()
            ->select(['sync_status'])
            ->where(['id' => $this->getUserID()])
            ->first();
        $this->_indexSyncStatus = $userRow ? (int)$userRow->sync_status : \User\Model\Behavior\UserBehavior::SYNC_STATUS_LOCAL;
        $this->_indexActiveIdentityTypeId = $SecurityUsers->getActiveExternalSourceIdentityTypeId(); //null is a valid loaded value (no active source)
        $this->_syncCacheLoaded = true;
    }
    //POCOR-9590: end

    //POCOR-9663
    private function checkDuplicateIdentity($entity)
    {
        if (empty($entity->number) || empty($entity->nationality_id)) {
            return "";
        }
        $conditions = [
            'number' => $entity->number,
            'nationality_id' => $entity->nationality_id
        ];

        if (!$entity->isNew() && !empty($entity->id)) {
            $conditions['id !='] = $entity->id;
        }
        $existing = $this->find()
            ->where($conditions)
            ->first();

        if ($existing) {
            return __("This identity number already exists for the  nationality.");
        }
        return "";
    }
}
