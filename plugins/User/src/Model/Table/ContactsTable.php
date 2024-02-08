<?php

namespace User\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Http\ServerRequest;
use Cake\Event\Event;
use Cake\ORM\Query;
use App\Model\Traits\OptionsTrait;

use App\Model\Table\ControllerActionTable;

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
        $this->addBehavior('User.SetupTab');

        $this->ContactOptionsTable = TableRegistry::get('User.ContactOptions');
        $this->contactOptionsArray = $this->ContactOptionsTable->findCodeList();
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
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

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // POCOR-8080-1
        // they need entity to set value in EDIT or restart
        $this->field('contact_option_id', ['type' => 'select', 'entity' => $entity]);
        $this->field('contact_type_id', ['type' => 'select', 'entity' => $entity]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['preferred']['type'] = 'select';
        $this->fields['preferred']['options'] = $this->getSelectOptions('general.yesno');
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        //to check if contact is new for its type. if yes, then set as preferred
        if ($entity->isNew()) {
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

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
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
            if ($contactOption == $this->contactOptionsArray['EMA']) { //if updating preferred email
                //update information on security user table
                $listeners = [
                    TableRegistry::get('User.Users')
                ];
                $this->dispatchEventToModels('Model.UserContacts.onChange', [$entity], $this, $listeners);
            }
        }
    }

    //POCOR-7767 Asked to remove this check
    public function _beforeDelete(Event $event, Entity $entity, ArrayObject $extra)
    {
        //for email, check whether has minimum one email record.
        $contactOption = $this->ContactTypes->get($entity->contact_type_id)->contact_option_id;
        $extra['contactOption'] = $contactOption;//to be passed to afterDelete
        // POCOR-8080-1
        // I've checked the new code
        if ($contactOption == $this->contactOptionsArray['EMA']) {
            $query = $this
                ->find()
                ->matching('ContactTypes', function ($q) use ($contactOption) {
                    return $q->where(['ContactTypes.contact_option_id' => $contactOption]);
                })
                ->where([
                    $this->aliasField('id != ') => $entity->id,
                    $this->aliasField('security_user_id') => $entity->security_user_id
                ])
                ->count();

            if (!$query) {
                $this->Alert->warning('UserContacts.noEmailRemain', ['reset' => true]);
                return false;
            }
        }
    }

    public function afterDelete(Event $event, Entity $entity, ArrayObject $extra)
    {
        $contactOption = $extra['contactOption'];

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
                if ($contactOption == $this->contactOptionsArray['EMA']) { //if the deleted contact option is email
                    //update information on security user table
                    $listeners = [
                        TableRegistry::get('User.Users')
                    ];
                    $this->dispatchEventToModels('Model.UserContacts.onChange', [$query], $this, $listeners);
                }
            }
        }
    }

    public function validationDefault(Validator $validator): Validator
    {
        // POCOR-8080-1
        // I've checked the new code
        $validator->setProvider('custom', $this); //POCOR-8080 here is the

        $validator
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
                    if(!is_numeric($contactOptionId)){
                        $contactOption = $this->ContactOptionsTable
                            ->find('all')
                            ->select([ 'id' => $this->ContactOptionsTable->aliasField('id')])
                            ->where([$this->ContactOptionsTable->aliasField('name') => $contactOptionId])
                            ->first();
                        if($contactOption){
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

                    if (!$query) {
                        $contactOptionId = 0;
                    }
                    $in_array = 'false';
                    if(in_array($contactOptionId, [$this->contactOptionsArray['MOB'], $this->contactOptionsArray['PHO'], $this->contactOptionsArray['FAX']])){
                        $in_array = 'true';
                    };

                    return $in_array;
                    // POCOR-8080-1 end
                },
            ])
            ->add('value', 'ruleValidateEmail', [
                'rule' => ['email', 'notBlank'],
                'on' => function ($context) {
                    // POCOR-8080-1 start
                    // I've cleaned the new code
                    $contactOptionId = (isset($context['data']['contact_option_id'])) ? $context['data']['contact_option_id'] : null;
                    if(!is_numeric($contactOptionId)){
                        $contactOption = $this->ContactOptionsTable
                            ->find('all')
                            ->select([ 'id' => $this->ContactOptionsTable->aliasField('id')])
                            ->where([$this->ContactOptionsTable->aliasField('name') => $contactOptionId])
                            ->first();
                        if($contactOption){
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
                    if(!is_numeric($contactOptionId)){
                        $contactOption = $this->ContactOptionsTable
                            ->find('all')
                            ->select([ 'id' => $this->ContactOptionsTable->aliasField('id')])
                            ->where([$this->ContactOptionsTable->aliasField('name') => $contactOptionId])
                            ->first();
                        if($contactOption){
                            $contactOptionId = $contactOption->id;
                        }
                    }
                    return ($contactOptionId == $this->contactOptionsArray['EMG']);
                    // POCOR-8080-1 end
                },
            ]);

        return $validator;

    }

    public function onUpdateFieldContactOptionId(Event $event, array $attr, $action, ServerRequest $request)
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
            $attr['value'] = $contactOption->name;
            $attr['attr']['value'] = $contactOption->name;
            $attr['type'] = 'readonly';

        }
        // POCOR-8080-1 end
        return $attr;
    }

    public function onUpdateFieldContactTypeId(Event $event, array $attr, $action, ServerRequest $request)
    {
        // POCOR-8080-1 start
        $queryData = $request->getData();
        $alias = $this->getAlias();
        $contactOptionId = null;
        $entity = $attr['entity'];
        if ($action == 'add' || $action == 'edit' ) {
            if (isset($queryData[$alias])) {
                if (isset($queryData[$alias]['contact_option_id']) &&
                    is_numeric($queryData[$alias]['contact_option_id'])) {
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
    function addEditOnChangeContactOption(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
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
    function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->getSession();
        $queryString = $this->getQueryString();
        
        if (!empty($queryString['security_user_id'])) {
            $userId = $queryString['security_user_id'];
        } else {
            $userId = $session->read('Student.Students.id');
        }

        $query->where([$this->aliasField('security_user_id IS') => $userId]); 
    }

    /*POCOR-6267 Ends*/

    public
    function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'description') {
            return __('Description');
        } elseif ($field == 'value') {
            return __('Value');
        } elseif ($field == 'preferred') {
            return __('Preferred');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

}
