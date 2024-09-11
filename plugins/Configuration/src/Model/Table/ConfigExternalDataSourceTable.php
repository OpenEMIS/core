<<<<<<< HEAD
<?php
namespace Configuration\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Http\ServerRequest;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Utility\Security;
use Cake\Core\Configure;
use Cake\Validation\Validator;

class ConfigExternalDataSourceTable extends ControllerActionTable
{
    public $id;
    public $authenticationType;

    public function initialize(array $config): void
    {
        $this->setTable('config_items');
        parent::initialize($config);
        $this->addBehavior('Configuration.ConfigItems');
        // $this->addBehavior('Configuration.ExternalDataSource');
        $this->toggle('remove', false);

        $externalDataSourceRecord = $this
            ->find()
            ->where([$this->aliasField('type') => 'External Data Source - Identity'])
            ->first();
        $id = $externalDataSourceRecord->id;
        $this->id = $id;
        $this->externalDataSourceType = $externalDataSourceRecord->value;
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        //POCOR-6930 Starts
        $validator->setProvider('custom', $this);
        if($this->request->getData()['ConfigExternalDataSource']['value'] == 'Jordan CSPD'){
            return $validator
                ->requirePresence('url')
                ->requirePresence('username')
                ->requirePresence('password')
                ->requirePresence('first_name_mapping')
                ->requirePresence('last_name_mapping')
                ->requirePresence('gender_mapping');
        }else{//POCOR-6930 Ends
            return $validator
                ->requirePresence('client_id')
                ->requirePresence('url')
                ->requirePresence('token_uri')
                ->requirePresence('record_uri')
                ->requirePresence('first_name_mapping')
                ->requirePresence('last_name_mapping')
                ->requirePresence('gender_mapping');
        }
    }

    public function validationCustom(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        return $validator->requirePresence('url', false);
    }

    public function validationOpenEMISIdentity(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        return $validator->requirePresence('url');
    }
    //POCOR-6930 Starts
    public function validationJordanCSPD(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        return $validator;
                
    }//POCOR-6930 Ends

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('visible', ['visible' => false]);
        $this->field('editable', ['visible' => false]);
        $this->field('field_type', ['visible' => false]);
        $this->field('option_type', ['visible' => false]);
        $this->field('code', ['visible' => false]);
        $this->field('name', ['visible' => ['index'=>true]]);
        $this->field('default_value', ['visible' => ['view'=>true]]);
        $this->field('type', ['visible' => ['view'=>true, 'edit'=>true], 'type' => 'readonly']);
        $this->field('label', ['visible' => ['view'=>true, 'edit'=>true], 'type' => 'readonly']);

        if ($this->action == 'index') {
            $url = $this->url('view');
            $url[1] = $this->paramsEncode(['id' => $this->id]);
            $this->controller->redirect($url);
        } elseif ($this->action == 'view') {
            $extra['elements']['controls'] = $this->buildSystemConfigFilters();
            $this->checkController();
        }

        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Administration','External Data Source - Identity','System Configurations');       
		if(!empty($is_manual_exist)){
			$btnAttr = [
				'class' => 'btn btn-xs btn-default icon-big',
				'data-toggle' => 'tooltip',
				'data-placement' => 'bottom',
				'escape' => false,
				'target'=>'_blank'
			];

			$helpBtn['url'] = $is_manual_exist['url'];
			$helpBtn['type'] = 'button';
			$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
			$helpBtn['attr'] = $btnAttr;
			$helpBtn['attr']['title'] = __('Help');
			$extra['toolbarButtons']['help'] = $helpBtn;
		}
		// End POCOR-5188
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('value', ['visible' => true]);
        if ($entity->value != 'None') {
            $this->field('attributes', ['type' => 'custom_external_source']);
        }
    }

    public function onGetCustomExternalSourceElement(Event $event, $action, Entity $entity, $attr, $options = [])
    {
        $tableHeaders = [__('Attribute Name'), __('Value')];
        $tableCells = [];
        $ExternalDataSourceAttributes = TableRegistry::get('Configuration.ExternalDataSourceAttributes');
        $attributes = $ExternalDataSourceAttributes
            ->find('list', [
                'keyField' => 'attribute_field',
                'valueField' => 'value'
            ])
            ->where([
                $ExternalDataSourceAttributes->aliasField('external_data_source_type') => $entity->value
            ])
            ->order('attribute_field')
            ->toArray();
        if (isset($attributes['private_key'])) {
            unset($attributes['private_key']);
        }

        if ($entity->value == 'OpenEMIS Identity') {
            $newAttributes = [];
            $newAttributes['client_id'] = $attributes['client_id'];
            $newAttributes['url'] = $attributes['url'];
            $newAttributes['public_key'] = $attributes['public_key'];
            $attributes = $newAttributes;
        }

        if ($action == 'view') {
            foreach ($attributes as $key => $obj) {
                $rowData = [];
                $rowData[] = __(Inflector::humanize($key));
                $rowData[] = nl2br($obj);
                $tableCells[] = $rowData;
            }
        }
        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

        return $event->getSubject()->renderElement('Configuration.external_data_source', ['attr' => $attr]);
    }

    public function onUpdateFieldValue(Event $event, array $attr, $action, ServerRequest $request)
    {
        if (in_array($action, ['edit', 'add'])) {
            $id = $this->id;
            if (!empty($id)) {
                $entity = $this->get($id);
                $value = $entity->value;
                if (isset($request->data[$this->getAlias()]['value'])) {
                    $value = $request->data[$this->getAlias()]['value'];
                }
                $ExternalDataSourceAttributes = TableRegistry::get('Configuration.ExternalDataSourceAttributes');
                $attributes = $ExternalDataSourceAttributes
                    ->find('list', [
                        'keyField' => 'attribute_field',
                        'valueField' => 'value'
                    ])
                    ->where([
                        $ExternalDataSourceAttributes->aliasField('external_data_source_type') => $value
                    ])
                    ->toArray();
                foreach ($attributes as $key => $value) {
                    if ($key == 'private_key') {
                        $keyAndSecret = explode('.', $value);
                        if (count($keyAndSecret) == 2) {
                            list($privateKey, $secret) = $keyAndSecret;
                            $secret = openssl_private_decrypt($this->urlsafeB64Decode($secret), $protectedKey, Configure::read('Application.private.key'));
                            if ($secret) {
                                $value = Security::decrypt($this->urlsafeB64Decode($privateKey), $protectedKey);
                            } else {
                                $value = '';
                            }
                        } else {
                            $value = '';
                        }
                    }
                    $request->data[$this->getAlias()][$key] = $value;
                }
                if ($entity->field_type == 'Dropdown') {
                    $optionTable = TableRegistry::get('Configuration.ConfigItemOptions');
                    $options = $optionTable->find('list', ['keyField' => 'value', 'valueField' => 'option'])
                        ->where([
                            'ConfigItemOptions.option_type' => $entity->option_type,
                            'ConfigItemOptions.visible' => 1
                        ])
                        ->toArray();
                    $attr['options'] = $options;
                    $attr['onChangeReload'] = true;
                }
            }
        }
        return $attr;
    }

    public function editBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOption, ArrayObject $extra)
    {
        if ($requestData[$this->getAlias()]['value'] == 'OpenEMIS Identity') {
            $url = rtrim(trim($requestData[$this->getAlias()]['url']), "/");
            $requestData[$this->getAlias()]['url'] = $url;
            $requestData[$this->getAlias()]['scope'] = 'Student';
            $requestData[$this->getAlias()]['first_name_mapping'] = 'first_name';
            $requestData[$this->getAlias()]['middle_name_mapping'] = 'middle_name';
            $requestData[$this->getAlias()]['third_name_mapping'] = 'third_name';
            $requestData[$this->getAlias()]['last_name_mapping'] = 'last_name';
            $requestData[$this->getAlias()]['date_of_birth_mapping'] = 'date_of_birth';
            $requestData[$this->getAlias()]['gender_mapping'] = 'gender.name';
            $requestData[$this->getAlias()]['identity_type_mapping'] = 'main_identity_type.name';
            $requestData[$this->getAlias()]['identity_number_mapping'] = 'identity_number';
            $requestData[$this->getAlias()]['nationality_mapping'] = 'main_nationality.name';
            $requestData[$this->getAlias()]['address_mapping'] = 'address';
            $requestData[$this->getAlias()]['postal_mapping'] = 'postal_code';
            $requestData[$this->getAlias()]['external_reference_mapping'] = 'id';
            $requestData[$this->getAlias()]['token_uri'] = $url .'/api/oauth/token';
            $requestData[$this->getAlias()]['record_uri'] = $url .'/api/restful/Users.json?_finder=Students[first_name:{first_name};last_name:{last_name};date_of_birth:{date_of_birth};identity_number:{identity_number};limit:{limit};page:{page}]&_flatten=1';
            $requestData[$this->getAlias()]['user_endpoint_uri'] = $url .'/api/restful/Users/{external_reference}.json?_contain=Genders,MainIdentityType,MainNationality&_flatten=1';
            $patchOption['validate'] = 'OpenEMISIdentity';
        } elseif ($requestData[$this->getAlias()]['value'] == 'None') {
            $patchOption['validate'] = false;
        } elseif ($requestData[$this->getAlias()]['value'] == 'Custom') {
            $patchOption['validate'] = 'Custom';
        } elseif ($requestData[$this->getAlias()]['value'] == 'Jordan CSPD') {//POCOR-6930
            $patchOption['validate'] = 'JordanCSPD';
        } 

        if($requestData[$this->getAlias()]['value'] != 'Jordan CSPD'){//POCOR-6930 add if condition
            if (empty($requestData[$this->getAlias()]['private_key'])) {
                $newKey = openssl_pkey_new([
                    "digest_alg" => "sha256",
                    "private_key_bits" => 1024,
                    "private_key_type" => OPENSSL_KEYTYPE_RSA
                ]);

                $res = openssl_pkey_new();

                openssl_pkey_export($res, $privKey);

                $pubKey = openssl_pkey_get_details($res);
                $pubKey = $pubKey["key"];
                $protectedKey = Security::hash(microtime(true), 'sha256', true);
                $privateKey = $this->urlsafeB64Encode(Security::encrypt($privKey, $protectedKey));
                $status = openssl_public_encrypt($protectedKey, $key, Configure::read('Application.public.key'));
                $protectedKey = $this->urlsafeB64Encode($key);
                $requestData[$this->getAlias()]['private_key'] = $privateKey. '.' .$protectedKey;
                $requestData[$this->getAlias()]['public_key'] = $pubKey;
            } else {
                $privKey = $requestData[$this->getAlias()]['private_key'];
                $protectedKey = Security::hash(microtime(true), 'sha256', true);
                $privateKey = $this->urlsafeB64Encode(Security::encrypt($privKey, $protectedKey));
                $status = openssl_public_encrypt($protectedKey, $key, Configure::read('Application.public.key'));
                $protectedKey = $this->urlsafeB64Encode($key);
                $requestData[$this->getAlias()]['private_key'] = $privateKey. '.' .$protectedKey;
            }
        }
    }

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $patchOption, ArrayObject $extra)
    {
        //POCOR-6930 Starts
        $errors = $entity->errors();
        if (!empty($errors)) {
            $errorMessage = 'Please enter the required details.';
            $this->Alert->error('general.externalSourceDataErr', ['reset'=>true]);
        }else{//POCOR-6930 Ends
            $ExternalDataSourceAttributes = TableRegistry::get('Configuration.ExternalDataSourceAttributes');
            $ExternalDataSourceAttributes->deleteAll(['external_data_source_type' => $entity->value]);
            $fields = [
                'url', 'token_uri', 'record_uri', 'user_endpoint_uri', 'client_id', 'scope', 'first_name_mapping', 'middle_name_mapping', 'third_name_mapping', 'last_name_mapping', 'date_of_birth_mapping',
                'external_reference_mapping', 'gender_mapping', 'identity_type_mapping', 'identity_number_mapping', 'nationality_mapping', 'address_mapping', 'postal_mapping', 'private_key', 'public_key', 'username', 'password'
            ];
            foreach ($fields as $field) {
                if ($entity->has($field)) {
                    $data = [
                        'external_data_source_type' => $entity->value,
                        'attribute_field' => $field,
                        'attribute_name' => $field,
                        'value' => $entity->{$field}
                    ];
                    $newEntity = $ExternalDataSourceAttributes->newEntity($data);
                    $ExternalDataSourceAttributes->save($newEntity);
                }
            }
        }
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $value = $entity->value;
        $this->field('value', ['visible' => true]);
        switch ($value) {
            case 'OpenEMIS Identity':
                $this->field('url');
                $this->field('token_uri', ['type' => 'hidden']);
                $this->field('record_uri', ['type' => 'hidden']);
                $this->field('client_id');
                $this->field('scope', ['type' => 'hidden']);
                $this->field('first_name_mapping', ['type' => 'hidden']);
                $this->field('middle_name_mapping', ['type' => 'hidden']);
                $this->field('third_name_mapping', ['type' => 'hidden']);
                $this->field('last_name_mapping', ['type' => 'hidden']);
                $this->field('date_of_birth_mapping', ['type' => 'hidden']);
                $this->field('external_reference_mapping', ['type' => 'hidden']);
                $this->field('gender_mapping', ['type' => 'hidden']);
                $this->field('identity_type_mapping', ['type' => 'hidden']);
                $this->field('identity_number_mapping', ['type' => 'hidden']);
                $this->field('nationality_mapping', ['type' => 'hidden']);
                $this->field('address_mapping', ['type' => 'hidden']);
                $this->field('postal_mapping', ['type' => 'hidden']);
                $this->field('user_endpoint_uri', ['type' => 'hidden']);
                $this->field('private_key', ['type' => 'text']);
                $this->field('public_key', ['type' => 'text']);
                break;

            case 'Custom':
                $this->field('token_uri');
                $this->field('record_uri');
                $this->field('client_id');
                $this->field('user_endpoint_uri');
                $this->field('scope');
                $this->field('first_name_mapping');
                $this->field('middle_name_mapping');
                $this->field('third_name_mapping');
                $this->field('last_name_mapping');
                $this->field('date_of_birth_mapping');
                $this->field('external_reference_mapping');
                $this->field('gender_mapping');
                $this->field('identity_type_mapping');
                $this->field('identity_number_mapping');
                $this->field('nationality_mapping');
                $this->field('address_mapping');
                $this->field('postal_mapping');
                $this->field('private_key', ['type' => 'text']);
                $this->field('public_key', ['type' => 'text']);
                break;
            //POCOR-6930 Starts    
            case 'Jordan CSPD':
                $this->field('url');
                $this->field('username', ['type' => 'string', 'required' => 'required']);
                $this->field('password', ['type' => 'string', 'required' => 'required']);
                $this->field('first_name_mapping');
                $this->field('middle_name_mapping');
                $this->field('third_name_mapping');
                $this->field('last_name_mapping');
                $this->field('date_of_birth_mapping');
                $this->field('gender_mapping');
                $this->field('identity_type_mapping');
                $this->field('identity_number_mapping');
                $this->field('nationality_mapping');
                $this->field('address_mapping');
                $this->field('postal_mapping');
                break;//POCOR-6930 Ends

            default:
                break;
        }
    }
}
=======
<?php

namespace Configuration\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Utility\Security;
use Cake\Validation\Validator;

class ConfigExternalDataSourceTable extends ControllerActionTable
{
    public $id;
    public $authenticationType;

    public function initialize(array $config): void
    {
        $this->setTable('config_items');
        parent::initialize($config);
        $this->addBehavior('Configuration.ConfigItems');
        $this->toggle('remove', false);
        $this->hasMany('WebhookEvents', ['className' => 'Webhook.WebhookEvents', 'dependent' => true, 'cascadeCallBack' => true, 'saveStrategy' => 'replace', 'foreignKey' => 'webhook_id', 'joinType' => 'INNER']);
    }

    public function validationCustom(Validator $validator): Validator
    {
        $validator = $this->validationDefault($validator);
        return $validator->requirePresence('url', false);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        //POCOR-6930, 7981 Starts
        $requestData = $this->request['data'];
        $alias = $this->alias();
        $data = $requestData[$alias];
        $source = $data['label'];
        if ($source == 'Jordan CSPD') {
            return $validator
                ->requirePresence('url')
                ->requirePresence('username')
                ->requirePresence('password')
                ->requirePresence('first_name_mapping')
                ->requirePresence('last_name_mapping')
                ->requirePresence('gender_mapping');
        } elseif ($source == 'UNHCR') {
            return $validator
//                ->requirePresence('username')
//                ->requirePresence('password')
                ->requirePresence('url')
                ->requirePresence('application_id')
                ->requirePresence('secret_code');
        } else {//POCOR-6930, 7981 Ends
            return $validator
                ->requirePresence('client_id')
                ->requirePresence('url')
                ->requirePresence('token_uri')
                ->requirePresence('record_uri')
                ->requirePresence('first_name_mapping')
                ->requirePresence('last_name_mapping')
                ->requirePresence('gender_mapping');
        }
    }

    public function validationOpenEMISIdentity(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        return $validator->requirePresence('url');
    }

    //POCOR-6930 Starts
    public function validationJordanCSPD(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        return $validator;

    }//POCOR-6930 Ends

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        // POCOR-7981 Start
        if ($this->action == 'index') {
            $this->field('visible', ['visible' => false]);
            $this->field('editable', ['visible' => false]);
            $this->field('field_type', ['visible' => false]);
            $this->field('option_type', ['visible' => false]);
            $this->field('code', ['visible' => false]);
            $this->field('name', ['visible' => false]);
            $this->field('value', ['visible' => true]);
            $this->field('value_selection', ['visible' => false]);
            $this->field('default_value', ['visible' => false]);
            $this->field('type', ['visible' => ['view' => true, 'edit' => true], 'type' => 'readonly']);
            $this->field('label', ['visible' => ['index' => true, 'view' => true, 'edit' => true], 'type' => 'readonly']);
            $this->setFieldOrder([
                'label', 'value'
            ]);
        }
        if ($this->action != 'index') {

            $this->field('visible', ['visible' => false]);
            $this->field('editable', ['visible' => false]);
            $this->field('field_type', ['visible' => false]);
            $this->field('option_type', ['visible' => false]);
            $this->field('code', ['visible' => false]);
            $this->field('name', ['visible' => ['index' => true]]);
            $this->field('default_value', ['visible' => false]);

            $this->field('type', ['visible' => ['view' => true, 'edit' => true], 'type' => 'readonly']);
            $this->field('label', ['visible' => ['view' => true, 'edit' => true], 'type' => 'readonly']);

            if ($this->action == 'view') {
                $extra['elements']['controls'] = $this->buildSystemConfigFilters();
                $this->checkController();
            }
            // POCOR-7981 END
        }

        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Administration', 'External Data Source - Identity', 'System Configurations');
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
        // End POCOR-5188
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('value', ['visible' => true]);
        // POCOR-7981
        $this->field('attributes', ['type' => 'custom_external_source']);
    }

    public function onGetCustomExternalSourceElement(Event $event, $action, Entity $entity, $attr, $options = [])
    {
        $tableHeaders = [__('Attribute Name'), __('Value')];
        $tableCells = [];
        $ExternalDataSourceAttributes = TableRegistry::get('Configuration.ExternalDataSourceAttributes');
        $attributes = $ExternalDataSourceAttributes
            ->find('list', [
                'keyField' => 'attribute_field',
                'valueField' => 'value'
            ])
            ->where([
                $ExternalDataSourceAttributes->aliasField('external_data_source_type') => $entity->name // POCOR-7981
            ])
            ->order('attribute_field')
            ->toArray();
        if (isset($attributes['private_key'])) {
            unset($attributes['private_key']);
        }

        if ($action == 'view') {
            foreach ($attributes as $key => $obj) {
                $rowData = [];
                $rowData[] = __(Inflector::humanize($key));
                $rowData[] = nl2br($obj);
                $tableCells[] = $rowData;
            }
        }
        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

        return $event->subject()->renderElement('Configuration.external_data_source', ['attr' => $attr]);
    }

    public function onUpdateFieldValue(Event $event, array $attr, $action, Request $request)
    {
        // POCOR-7981 START
        if (in_array($action, ['edit'])) {
            $optionTable = TableRegistry::get('Configuration.ConfigItemOptions');
            $options = $optionTable->find('list', ['keyField' => 'value', 'valueField' => 'option'])
                ->where([
                    'ConfigItemOptions.option_type' => 'completeness',
                    'ConfigItemOptions.visible' => 1
                ])
                ->toArray();
            $attr['options'] = $options;
            $attr['onChangeReload'] = true;
            $this->setExternalAttributes($attr['entity']);
        }
        // POCOR-7981 END

        return $attr;
    }

    public function editBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOption, ArrayObject $extra)
    {
        // POCOR-7981 START
        $alias = $this->alias();
        $data = $requestData[$alias];
        $source = $entity['name'];

        if ($source == 'UNHCR') {
            $patchOption['validate'] = true;
            return;
        }

        if ($source == 'Custom') {
            $patchOption['validate'] = 'Custom';
        }

        if ($source == 'Jordan CSPD') {//POCOR-6930
            $patchOption['validate'] = 'JordanCSPD';
        }

        if ($data['value'] != 'Jordan CSPD') {//POCOR-6930 add if condition
            if (empty($data['private_key'])) {
                $newKey = openssl_pkey_new([
                    "digest_alg" => "sha256",
                    "private_key_bits" => 1024,
                    "private_key_type" => OPENSSL_KEYTYPE_RSA
                ]);

                $res = openssl_pkey_new();

                openssl_pkey_export($res, $privKey);

                $pubKey = openssl_pkey_get_details($res);
                $pubKey = $pubKey["key"];
                $protectedKey = Security::hash(microtime(true), 'sha256', true);
                $privateKey = $this->urlsafeB64Encode(Security::encrypt($privKey, $protectedKey));
                $status = openssl_public_encrypt($protectedKey, $key, Configure::read('Application.public.key'));
                $protectedKey = $this->urlsafeB64Encode($key);
                $requestData[$alias]['private_key'] = $privateKey . '.' . $protectedKey;
                $requestData[$alias]['public_key'] = $pubKey;
            } else {
                $privKey = $data['private_key'];
                $protectedKey = Security::hash(microtime(true), 'sha256', true);
                $privateKey = $this->urlsafeB64Encode(Security::encrypt($privKey, $protectedKey));
                $status = openssl_public_encrypt($protectedKey, $key, Configure::read('Application.public.key'));
                $protectedKey = $this->urlsafeB64Encode($key);
                $requestData[$alias]['private_key'] = $privateKey . '.' . $protectedKey;
            }
        }
        // POCOR-7981 END
    }

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $patchOption, ArrayObject $extra)
    {
        //POCOR-6930, 7981 Starts
        $errors = $entity->errors();
        $source = $entity->name;
        if (!empty($errors)) {
            $errorMessage = 'Please enter the required details.';
            //POCOR-7981:starts
            $error_prefix = __CLASS__ . ':' . __FILE__ . __FUNCTION__ . __LINE__;
            $this->log($error_prefix);
            $this->log($errorMessage);
            $this->log($errors);
            //POCOR-7981:ends
            $this->Alert->error('general.externalSourceDataErr', ['reset' => true]);
        } else {//POCOR-6930 Ends
            $ExternalDataSourceAttributes = TableRegistry::get('Configuration.ExternalDataSourceAttributes');
            $ExternalDataSourceAttributes->deleteAll(['external_data_source_type' => $source]);
            $fields = [
                'url',
                'token_uri',
                'record_uri',
                'user_endpoint_uri',
                'client_id',
                'scope',
                'first_name_mapping',
                'middle_name_mapping',
                'third_name_mapping',
                'last_name_mapping',
                'date_of_birth_mapping',
                'external_reference_mapping',
                'gender_mapping',
                'identity_type_mapping',
                'identity_number_mapping',
                'nationality_mapping',
                'address_mapping',
                'postal_mapping',
                'private_key',
                'public_key',
                'secret_code',
                'application_id',
            ];
            // POCOR-7981 END

            foreach ($fields as $field) {
                if ($entity->has($field)) {
                    $data = [
                        'external_data_source_type' => $source,
                        'attribute_field' => $field,
                        'attribute_name' => $field,
                        'value' => $entity->{$field}
                    ];
                    $newEntity = $ExternalDataSourceAttributes->newEntity($data);
                    $ExternalDataSourceAttributes->save($newEntity);
                }
            }
        }
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {

        $source = $entity->name;
        $this->field('value', ['visible' => true, 'entity' => $entity]); // POCOR-7981
        $this->field('value_selection', ['visible' => false]); //POCOR-7981 not used field
        switch ($source) {
            // POCOR-7981
            case 'Custom':
                $this->field('token_uri');
                $this->field('record_uri');
                $this->field('client_id');
                $this->field('user_endpoint_uri');
                $this->field('scope');
                $this->field('first_name_mapping');
                $this->field('middle_name_mapping');
                $this->field('third_name_mapping');
                $this->field('last_name_mapping');
                $this->field('date_of_birth_mapping');
                $this->field('external_reference_mapping');
                $this->field('gender_mapping');
                $this->field('identity_type_mapping');
                $this->field('identity_number_mapping');
                $this->field('nationality_mapping');
                $this->field('address_mapping');
                $this->field('postal_mapping');
                $this->field('private_key', ['type' => 'text']);
                $this->field('public_key', ['type' => 'text']);
                break;
            //POCOR-6930 Starts
            case 'Jordan CSPD':
                $this->field('url');
                $this->field('username', ['type' => 'string', 'required' => 'required']);
                $this->field('password', ['type' => 'string', 'required' => 'required']);
                $this->field('first_name_mapping');
                $this->field('middle_name_mapping');
                $this->field('third_name_mapping');
                $this->field('last_name_mapping');
                $this->field('date_of_birth_mapping');
                $this->field('gender_mapping');
                $this->field('identity_type_mapping');
                $this->field('identity_number_mapping');
                $this->field('nationality_mapping');
                $this->field('address_mapping');
                $this->field('postal_mapping');
                break;//POCOR-6930 Ends
            // POCOR-7981 END
            // POCOR-7981 START
            case 'UNHCR':
                $this->field('secret_code');
                $this->field('url');
                $this->field('application_id');
                break;//POCOR-7981 Ends
            default:
                break;
        }
    }

    // POCOR-7981
    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        if (isset($extra['toolbarButtons']['add'])) {
            unset($extra['toolbarButtons']['add']);
        }
    }

    //POCOR-7981:Start
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $optionTable = TableRegistry::get('Configuration.ConfigItemOptions');
        $query
            ->select(
                [$this->aliasField('id'),
                    $this->aliasField('label'),
                    $this->aliasField('value')]
            )->where([
                $this->aliasField('type') => 'External Data Source - Identity'
            ]);
    }

    public function onGetValue(Event $event, Entity $entity)
    {
        $valueField = 'value';
//        return 'Disabled';
        if ($entity->{$valueField} == 0) {
            $value = __('Disabled');
        } else {
            $value = __('Enabled');
        }

        return $value;
    }
    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'value') {
            return __('Status');
        } elseif ($field == 'label') {
            return __('Source');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    //POCOR-7981:End

    public function onGetLabel(Event $event, Entity $entity)
    {
        return __($entity->label);
    }

    public function openEMISIdentityValidation($attributes)
    {
        $attribute = [];
        $this->openEMISIdentityExternalSource($attribute);
        foreach ($attribute as $key => $values) {
            if (!isset($values['required'])) {
                if (empty($attributes[$key]['value'])) {
                    return false;
                }
            }
        }
        return true;
    }

    public function openEMISIdentityExternalSource(&$attribute)
    {
        $attribute['token_uri'] = ['label' => 'Token URI', 'type' => 'text'];
        $attribute['refresh_token'] = ['label' => 'Refresh Token', 'type' => 'textarea'];
        $attribute['client_id'] = ['label' => 'Client ID', 'type' => 'text'];
        $attribute['client_secret'] = ['label' => 'Client Secret', 'type' => 'text'];
        // $attribute['redirect_uri'] = ['label' => 'Redirect URI', 'type' => 'text', 'readonly' => true];
        // $attribute['hd'] = ['label' => 'Hosted Domain', 'type' => 'text', 'required' => false];
        $attribute['record_uri'] = ['label' => 'Record URI', 'type' => 'text'];
    }

    /**
     * @param $entity
     * @return void
     */

    public function setExternalAttributes($entity)
    {
        $id = $entity->id;
        $source = $entity->name;
        if (!empty($id)) {
            $ExternalDataSourceAttributes = TableRegistry::get('Configuration.ExternalDataSourceAttributes');
            $attributes = $ExternalDataSourceAttributes
                ->find('list', [
                    'keyField' => 'attribute_field',
                    'valueField' => 'value'
                ])
                ->where([
                    $ExternalDataSourceAttributes->aliasField('external_data_source_type') => $source
                ])
                ->toArray();
            foreach ($attributes as $key => $value) {
                $request = $this->request;
                $request->data[$this->alias()][$key] = $value;
            }
        }
    }

    protected function processAuthentication(&$attribute, $authenticationType)
    {
        $ExternalDataSourceAttributesTable = TableRegistry::get('ExternalDataSourceAttributes');
        $attributesArray = $ExternalDataSourceAttributesTable->find()->where([$ExternalDataSourceAttributesTable->aliasField('external_data_source_type') => $authenticationType])->toArray();
        $attributeFieldsArray = $this->_table->array_column($attributesArray, 'attribute_field');
        foreach ($attribute as $key => $values) {
            $attributeValue = '';
            if (array_search($key, $attributeFieldsArray) !== false) {
                $attributeValue = $attributesArray[array_search($key, $attributeFieldsArray)]['value'];
            }
            if (method_exists($this, lcfirst(Inflector::camelize($authenticationType, ' ')) . 'ModifyValue')) {
                $method = lcfirst(Inflector::camelize($authenticationType, ' ')) . 'ModifyValue';
                $result = $this->$method($key, $attributeValue);
                if ($result !== false) {
                    $attributeValue = $result;
                }
            }
            $attribute[$key]['value'] = $attributeValue;
        }
    }
    // POCOR-7981 END
}
>>>>>>> origin
