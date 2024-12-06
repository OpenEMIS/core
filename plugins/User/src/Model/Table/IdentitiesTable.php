<?php

namespace User\Model\Table;

use Exception;
use DateTime;
use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Event\Event;
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

    public function beforeSave(Event $event, Entity $entity, ArrayObject $extra)
    {
        //$this->log('beforeSave', 'debug');
        //$this->log($entity, 'debug');
        //POCOR-8243
        $options = [];
        $options['identity_type_id'] = $entity->identity_type_id;
        $options['identity_number'] = $entity->number;
        if ($entity->isNew()) {
            $userID = $this->getUserID();
            if ($userID) {
                $entity['security_user_id'] = $userID;
            }
        }
        $message = $this->checkCustomIdentityNumber($options);
        if ($message != "") {
            $message = __('Wrong identity number');
            $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
            $event->stopPropagation();
        }
    }

    public function afterSaveUsers(Event $event, Entity $entity)
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
        $UserNationalityTable = TableRegistry::get('User.UserNationalities');
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
        $this->setFieldOrder(['identity_type_id', 'nationality_id', 'number','preferred','issue_date', 'expiry_date', 'issue_location', 'comments']);

    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
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
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $userId = $this->getUserID();
        $query->where([$this->aliasField('security_user_id') => $userId]);
    }

    /*POCOR-6267 Ends*/

    public function editOnInitialize(Event $event, Entity $entity)
    {
        // set the defaultDate to false on initialize, for the empty date.
        if (empty($entity->issue_date)) {
            $this->fields['issue_date']['default_date'] = false;
        }

        if (empty($entity->expiry_date)) {
            $this->fields['expiry_date']['default_date'] = false;
        }
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        return $validator
            ->add('issue_date', 'ruleCompareDate', [
                'rule' => ['compareDate', 'expiry_date', false]
            ])
            ->add('expiry_date', [
            ])
            ->add('identity_type_id', 'ruleCustomIdentityType', [
                'rule' => ['validateCustomIdentityType'],
                'provider' => 'table',
            ])
            ->add('number', 'ruleCustomIdentityNumber', [
                'rule' => ['validateCustomIdentityNumber'],
                'provider' => 'table',
                'last' => true
            ])
            ->add('number', [
                'ruleUnique' => [
                    'rule' => ['validateUnique', ['scope' => 'identity_type_id']],
                    'provider' => 'table'
                ]
            ])
            //POCOR-5987 starts
            ->notEmpty('nationality_id');
        //POCOR-5987 ends
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

    public function afterSave(Event $event, Entity $entity, ArrayObject $extra)
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

    public function afterDelete(Event $event, Entity $entity, ArrayObject $extra)
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

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
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
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
