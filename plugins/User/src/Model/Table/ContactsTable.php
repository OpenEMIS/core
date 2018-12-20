<?php
namespace User\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;
use App\Model\Traits\OptionsTrait;

use App\Model\Table\ControllerActionTable;

class ContactsTable extends ControllerActionTable
{
    use OptionsTrait;

    private $ContactOptionsTable;
    private $contactOptionsArray;

    public function initialize(array $config)
    {
        $this->table('user_contacts');
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
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('contact_option_id', ['type' => 'select']);
        $this->field('contact_type_id', ['type' => 'select']);
    }

    public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        $contactOptionId = $this->ContactTypes->get($entity->contact_type_id)->contact_option_id;
        $entity->contact_option_id = $contactOptionId;
        $this->request->query['contact_option'] = $contactOptionId;
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
        if (($entity->dirty('preferred') && $entity->preferred == 1) || $entity->preferred == 1) {
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

            if ($contactOption == $this->contactOptionsArray['EMAIL']) { //if updating preferred email
                //update information on security user table
                $listeners = [
                    TableRegistry::get('User.Users')
                ];
                $this->dispatchEventToModels('Model.UserContacts.onChange', [$entity], $this, $listeners);
            }
        }
    }

    public function beforeDelete(Event $event, Entity $entity, ArrayObject $extra)
    {
        //for email, check whether has minimum one email record.
        $contactOption = $this->ContactTypes->get($entity->contact_type_id)->contact_option_id;
        $extra['contactOption'] = $contactOption;//to be passed to afterDelete

        if ($contactOption == $this->contactOptionsArray['EMAIL']) {
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
                $this->Alert->warning('UserContacts.noEmailRemain', ['reset'=>true]);
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

                if ($contactOption == $this->contactOptionsArray['EMAIL']) { //if the deleted contact option is email
                    //update information on security user table
                    $listeners = [
                        TableRegistry::get('User.Users')
                    ];
                    $this->dispatchEventToModels('Model.UserContacts.onChange', [$query], $this, $listeners);
                }
            }
        }
    }

    // public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
    // 	//Required by patchEntity for associated data
    // 	$newOptions = [];
    // 	$newOptions['validate'] = 'default';

    // 	$arrayOptions = $options->getArrayCopy();
    // 	$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
    // 	$options->exchangeArray($arrayOptions);
    // }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $validator->remove('value', 'notBlank');

        $validator = $this->buildBaseValidator($validator);
        $validator
            //validate at least one preferred on each contact type
            ->add('preferred', 'ruleValidatePreferred', [
                'rule' => ['validateContact'],
            ])
            //validate unique contact value per contact type
            ->add('value', 'ruleUniqueContactValue', [
                    'rule' => ['validateContact']
            ]);

        // validation code must always be set because this is also being used by prefererences 'usercontacts'
        $this->setValidationCode('value.ruleNotBlank', 'User.Contacts');
        $this->setValidationCode('value.ruleValidateNumeric', 'User.Contacts');
        $this->setValidationCode('value.ruleValidateEmail', 'User.Contacts');
        $this->setValidationCode('value.ruleValidateEmergency', 'User.Contacts');
        $this->setValidationCode('preferred.ruleValidatePreferred', 'User.Contacts');
        $this->setValidationCode('value.ruleUniqueContactValue', 'User.Contacts');

        return $validator;
    }

    public function validationImportType(Validator $validator)
    {
        $validator = $this->buildBaseValidator($validator);
        $this->setValidationCode('value.ruleNotBlank', 'User.Contacts');
        $this->setValidationCode('value.ruleValidateNumeric', 'User.Contacts');
        $this->setValidationCode('value.ruleValidateEmail', 'User.Contacts');
        $this->setValidationCode('value.ruleValidateEmergency', 'User.Contacts');
        $this->setValidationCode('preferred.ruleValidatePreferred', 'User.Contacts');
        $this->setValidationCode('value.ruleUniqueContactValue', 'User.Contacts');

        return $validator;
    }

    private function buildBaseValidator(Validator $validator)
    {
        $validator
            ->requirePresence('contact_option_id')
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
                    $contactOptionId = (array_key_exists('contact_option_id', $context['data']))? $context['data']['contact_option_id']: null;
                    if (is_null($contactOptionId)) {
                        if (array_key_exists('contact_type_id', $context['data'])) {
                            $query = $this->ContactTypes
                                ->find()
                                ->where([$this->ContactTypes->aliasField($this->ContactTypes->primaryKey()) => $contactTypeId])
                                ->first();
                                ;
                            if ($query) {
                                $contactOptionId = $query->contact_option_id;
                            }
                        }
                    } else {
                        $query = $this->ContactTypes
                                ->find()
                                ->where([
                                    $this->ContactTypes->aliasField($this->ContactTypes->primaryKey()) => $contactTypeId,
                                    $this->ContactTypes->aliasField('validation_pattern').' IS NULL'
                                ])
                                ->first();

                        // if Contact Types Validation Pattern is not NULL,
                        // skip numericPositive validation check because the validation pattern will check via regex
                        if (!$query) {
                            $contactOptionId = null;
                        }
                    }
                    return in_array($contactOptionId, [$this->contactOptionsArray['MOBILE'], $this->contactOptionsArray['PHONE'], $this->contactOptionsArray['FAX']]);
                },
            ])
            ->add('value', 'ruleValidateEmail', [
                'rule' => ['email', 'notBlank'],
                'on' => function ($context) {
                    $contactOptionId = (array_key_exists('contact_option_id', $context['data']))? $context['data']['contact_option_id']: null;
                    if (is_null($contactOptionId)) {
                        if (array_key_exists('contact_type_id', $context['data'])) {
                            $contactTypeId = $context['data']['contact_type_id'];
                            $query = $this->ContactTypes
                                ->find()
                                ->where([$this->ContactTypes->aliasField($this->ContactTypes->primaryKey()) => $contactTypeId])
                                ->first();
                                ;
                            if ($query) {
                                $contactOptionId = $query->contact_option_id;
                            }
                        }
                    }
                    return ($contactOptionId == $this->contactOptionsArray['EMAIL']);
                },
            ])
            ->add('value', 'ruleValidateEmergency', [
                'rule' => 'notBlank',
                'on' => function ($context) {
                    $contactOptionId = (array_key_exists('contact_option_id', $context['data']))? $context['data']['contact_option_id']: null;
                    if (is_null($contactOptionId)) {
                        if (array_key_exists('contact_type_id', $context['data'])) {
                            $contactTypeId = $context['data']['contact_type_id'];
                            $query = $this->ContactTypes
                                ->find()
                                ->where([$this->ContactTypes->aliasField($this->ContactTypes->primaryKey()) => $contactTypeId])
                                ->first();
                                ;
                            if ($query) {
                                $contactOptionId = $query->contact_option_id;
                            }
                        }
                    }
                    return ($contactOptionId == $this->contactOptionsArray['EMERGENCY']);
                },
            ]);

        return $validator;
    }

    public function validationNonMandatory(Validator $validator)
    {
        $this->validationDefault($validator);
        return $validator->allowEmpty('value');
    }

    public function onUpdateFieldContactOptionId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $contactOptions = $this->ContactOptionsTable
                                ->find('list')
                                ->find('order')
                                ->toArray();

            $attr['options'] = $contactOptions;
            $attr['onChangeReload'] = 'changeContactOption';
            $attr['attr']['required'] = true;
        }
        return $attr;
    }

    public function onUpdateFieldContactTypeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            if (array_key_exists('contact_option', $request->query)) {
                $contactOptionId = $request->query['contact_option'];
                $contactTypes = $this->ContactTypes
                    ->find('list')
                    ->find('order')
                    ->where([$this->ContactTypes->aliasField('contact_option_id') => $contactOptionId])
                    ->toArray();
            } else {
                $contactTypes = [];
            }
            $attr['options'] = $contactTypes;
        }
        return $attr;
    }

    public function addEditOnChangeContactOption(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['contact_option']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('contact_option_id', $request->data[$this->alias()])) {
                    $request->query['contact_option'] = $request->data[$this->alias()]['contact_option_id'];
                }
            }
        }
    }
}
