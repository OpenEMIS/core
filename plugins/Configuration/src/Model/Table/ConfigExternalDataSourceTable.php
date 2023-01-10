<?php
namespace Configuration\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Network\Request;
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

    public function initialize(array $config)
    {
        $this->table('config_items');
        parent::initialize($config);
        $this->addBehavior('Configuration.ConfigItems');
        // $this->addBehavior('Configuration.ExternalDataSource');
        $this->toggle('remove', false);

        $externalDataSourceRecord = $this
            ->find()
            ->where([$this->aliasField('type') => 'External Data Source'])
            ->first();
        $id = $externalDataSourceRecord->id;
        $this->id = $id;
        $this->externalDataSourceType = $externalDataSourceRecord->value;
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->requirePresence('client_id')
            ->requirePresence('url')
            ->requirePresence('token_uri')
            ->requirePresence('record_uri')
            ->requirePresence('first_name_mapping')
            ->requirePresence('last_name_mapping')
            ->requirePresence('gender_mapping');
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

        return $event->subject()->renderElement('Configuration.external_data_source', ['attr' => $attr]);
    }

    public function onUpdateFieldValue(Event $event, array $attr, $action, Request $request)
    {
        if (in_array($action, ['edit', 'add'])) {
            $id = $this->id;
            if (!empty($id)) {
                $entity = $this->get($id);
                $value = $entity->value;
                if (isset($request->data[$this->alias()]['value'])) {
                    $value = $request->data[$this->alias()]['value'];
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
                    $request->data[$this->alias()][$key] = $value;
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
        if ($requestData[$this->alias()]['value'] == 'OpenEMIS Identity') {
            $url = rtrim(trim($requestData[$this->alias()]['url']), "/");
            $requestData[$this->alias()]['url'] = $url;
            $requestData[$this->alias()]['scope'] = 'Student';
            $requestData[$this->alias()]['first_name_mapping'] = 'first_name';
            $requestData[$this->alias()]['middle_name_mapping'] = 'middle_name';
            $requestData[$this->alias()]['third_name_mapping'] = 'third_name';
            $requestData[$this->alias()]['last_name_mapping'] = 'last_name';
            $requestData[$this->alias()]['date_of_birth_mapping'] = 'date_of_birth';
            $requestData[$this->alias()]['gender_mapping'] = 'gender.name';
            $requestData[$this->alias()]['identity_type_mapping'] = 'main_identity_type.name';
            $requestData[$this->alias()]['identity_number_mapping'] = 'identity_number';
            $requestData[$this->alias()]['nationality_mapping'] = 'main_nationality.name';
            $requestData[$this->alias()]['address_mapping'] = 'address';
            $requestData[$this->alias()]['postal_mapping'] = 'postal_code';
            $requestData[$this->alias()]['external_reference_mapping'] = 'id';
            $requestData[$this->alias()]['token_uri'] = $url .'/api/oauth/token';
            $requestData[$this->alias()]['record_uri'] = $url .'/api/restful/Users.json?_finder=Students[first_name:{first_name};last_name:{last_name};date_of_birth:{date_of_birth};identity_number:{identity_number};limit:{limit};page:{page}]&_flatten=1';
            $requestData[$this->alias()]['user_endpoint_uri'] = $url .'/api/restful/Users/{external_reference}.json?_contain=Genders,MainIdentityType,MainNationality&_flatten=1';
            $patchOption['validate'] = 'OpenEMISIdentity';
        } elseif ($requestData[$this->alias()]['value'] == 'None') {
            $patchOption['validate'] = false;
        } elseif ($requestData[$this->alias()]['value'] == 'Custom') {
            $patchOption['validate'] = 'Custom';
        }
        if (empty($requestData[$this->alias()]['private_key'])) {
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
            $requestData[$this->alias()]['private_key'] = $privateKey. '.' .$protectedKey;
            $requestData[$this->alias()]['public_key'] = $pubKey;
        } else {
            $privKey = $requestData[$this->alias()]['private_key'];
            $protectedKey = Security::hash(microtime(true), 'sha256', true);
            $privateKey = $this->urlsafeB64Encode(Security::encrypt($privKey, $protectedKey));
            $status = openssl_public_encrypt($protectedKey, $key, Configure::read('Application.public.key'));
            $protectedKey = $this->urlsafeB64Encode($key);
            $requestData[$this->alias()]['private_key'] = $privateKey. '.' .$protectedKey;
        }
    }

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $patchOption, ArrayObject $extra)
    {
        $ExternalDataSourceAttributes = TableRegistry::get('Configuration.ExternalDataSourceAttributes');
        $ExternalDataSourceAttributes->deleteAll(['external_data_source_type' => $entity->value]);
        $fields = [
            'url', 'token_uri', 'record_uri', 'user_endpoint_uri', 'client_id', 'scope', 'first_name_mapping', 'middle_name_mapping', 'third_name_mapping', 'last_name_mapping', 'date_of_birth_mapping',
            'external_reference_mapping', 'gender_mapping', 'identity_type_mapping', 'identity_number_mapping', 'nationality_mapping', 'address_mapping', 'postal_mapping', 'private_key', 'public_key'
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

            default:
                break;
        }
    }
}
