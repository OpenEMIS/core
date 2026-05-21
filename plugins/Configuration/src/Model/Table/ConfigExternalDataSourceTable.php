<?php

namespace Configuration\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Utility\Security;
use Cake\Validation\Validator;
use Cake\ORM\Table; // POCOR-8849
use Cake\Log\Log; // POCOR-9118
use Cake\Datasource\Exception\RecordNotFoundException; // POCOR-9118

// POCOR-8849

class ConfigExternalDataSourceTable extends ControllerActionTable
{
    //POCOR-9590: external data source type names — DB values from config_items.value; never change these without a DB migration
    const SOURCE_JORDAN_CSPD = 'Jordan CSPD';
    const SOURCE_UNHCR       = 'UNHCR';
    const SOURCE_SEYCHELLES  = 'Seychelles Civil Status';
    const SOURCE_SEYCHELLOIS = 'Seychellois'; //POCOR-9590: alias used in some deployments — always normalise to SOURCE_SEYCHELLES
    const SOURCE_OPENEMIS    = 'OpenEMIS Core';
    const SOURCE_CUSTOM      = 'Custom';

    public $id;
    public $authenticationType;

    public function initialize(array $config): void
    {
        $this->setTable('config_items');
        parent::initialize($config);
        $this->addBehavior('Configuration.ConfigItems');
        $this->toggle('remove', false);
//        $this->hasMany('WebhookEvents', ['className' => 'Webhook.WebhookEvents', 'dependent' => true, 'cascadeCallBack' => true, 'saveStrategy' => 'replace', 'foreignKey' => 'webhook_id', 'joinType' => 'INNER']);
    }

    public function validationCustom(Validator $validator): Validator
    {
        $validator = $this->validationDefault($validator);
        return $validator->requirePresence('url', false);
    }

    public function validationDefault(Validator $validator): Validator // POCOR-8849
    {

        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this); // POCOR-8849
        //POCOR-6930, 7981 Starts
        $requestData = $this->request->getData(); // POCOR-8849
        $alias = $this->getAlias(); // POCOR-8849
        $data = $requestData[$alias];
        $source = $data['label'];
        if ($source == self::SOURCE_JORDAN_CSPD) {
            return $validator
                ->requirePresence('url')
                ->requirePresence('username')
                ->requirePresence('password')
                ->requirePresence('first_name_mapping')
                ->requirePresence('last_name_mapping')
                ->requirePresence('gender_mapping');
        } elseif ($source == self::SOURCE_UNHCR) {
            return $validator
//                ->requirePresence('username')
//                ->requirePresence('password')
                ->requirePresence('url')
                ->requirePresence('application_id')
                ->requirePresence('secret_code');
        } elseif ($source == self::SOURCE_SEYCHELLES || $source == self::SOURCE_SEYCHELLOIS) { // POCOR-9481 //POCOR-9590
            return $validator
                ->requirePresence('client_id')->notEmptyString('client_id')
                ->requirePresence('token_uri')->notEmptyString('token_uri')
                ->requirePresence('api_url')->notEmptyString('api_url')
                ->requirePresence('client_secret')
                ->notEmptyString('client_secret', 'Please enter a secret', 'create')
                // on UPDATE: allow blank so we can restore original
                ->allowEmptyString('client_secret', null, 'update')
                ->requirePresence('identity_type_id')->notEmptyString('identity_type_id') //POCOR-9590: required so Sync knows which user_identities row to use
                ->requirePresence('grant_type')->notEmptyString('grant_type')
                ->requirePresence('scopes')->notEmptyString('scopes')
                ->requirePresence('first_name_mapping')->notEmptyString('first_name_mapping')
                ->requirePresence('last_name_mapping')->notEmptyString('last_name_mapping')
                ->requirePresence('date_of_birth_mapping')->notEmptyString('date_of_birth_mapping')
                ->requirePresence('gender_mapping')->notEmptyString('gender_mapping')
                ->requirePresence('nationality_mapping')->notEmptyString('nationality_mapping');
        } elseif ($source == self::SOURCE_OPENEMIS) {
            // POCOR-9118 start: refactor validation
            // username, api_url, identity_type_id as before
            $validator
                ->requirePresence('username')
                ->notEmptyString('username')
                ->requirePresence('api_url')
                ->notEmptyString('api_url')
                ->requirePresence('identity_type_id')
                ->notEmptyString('identity_type_id');

            // password rules:
            $validator
                // on CREATE: must be present and non-empty
                ->requirePresence('password', 'create')
                ->notEmptyString('password', 'Please enter a password', 'create')
                // on UPDATE: allow blank so we can restore original
                ->allowEmptyString('password', null, 'update');

            // api_key rules:
            $validator
                ->requirePresence('api_key', 'create')
                ->notEmptyString('api_key', 'Please enter an API key', 'create')
                ->allowEmptyString('api_key', null, 'update');
            return $validator;
            // POCOR-9118 end
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

    public function validationOpenEMISIdentity(Validator $validator): Validator // POCOR-8849
    {
        $validator = $this->validationDefault($validator);
        return $validator->requirePresence('url');
    }

    //POCOR-6930 Starts
    public function validationJordanCSPD(Validator $validator): Validator // POCOR-8849
    {
        $validator = $this->validationDefault($validator);
        return $validator;

    }//POCOR-6930 Ends

    public function beforeAction(EventInterface $event, ArrayObject $extra)
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
            $this->field('value_selection', ['visible' => false]); // POCOR-8849

            $this->field('type', ['visible' => ['view' => true, 'edit' => true], 'type' => 'readonly']);
            $this->field('label', ['visible' => ['view' => true, 'edit' => true], 'type' => 'readonly']);

            if ($this->action == 'view') {
                $extra['elements']['controls'] = $this->buildSystemConfigFilters();
                $this->checkController();
            }
            // POCOR-7981 END
        }

        //POCOR-9590: add Test Connection button on view page
        //POCOR-9590: hidden — button kept in code for future re-enable, registration disabled
        if (false && $this->action == 'view') {
            $testBtn = [
                'type'  => 'button',
                'label' => '<i class="fa fa-plug"></i>',
                'url'   => [
                    'plugin'     => 'Configuration',
                    'controller' => 'Configurations',
                    'action'     => 'testExternalConnection',
                ],
                'attr'  => [
                    'class'          => 'btn btn-xs btn-info icon-big',
                    'title'          => __('Test Connection'),
                    'id'             => 'btn-test-connection',
                    'data-toggle'    => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape'         => false,
                ],
            ];
            $extra['toolbarButtons']['test_connection'] = $testBtn;
        }
        //POCOR-9590: end Test Connection button

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

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('value', ['visible' => true]);
        // POCOR-7981
        $this->field('attributes', ['type' => 'custom_external_source']);
    }

    public function onGetCustomExternalSourceElement(EventInterface $event, $action, Entity $entity, $attr, $options = [])
    {
// POCOR-9118 start
        $source = $entity->name;

        $this->field('value', ['visible' => true, 'entity' => $entity]); // POCOR-7981
        $this->field('value_selection', ['visible' => false]); //POCOR-7981 not used field
// POCOR-9118 end
        $tableHeaders = [__('Attribute Name'), __('Value')];
        $tableCells = [];
        $ExternalDataSourceAttributes = self::getDynamicTableInstance('Configuration.ExternalDataSourceAttributes'); // POCOR-8849
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
        if (isset($attributes['password'])) { // POCOR-8849
            $attributes['password'] = '*****'; // POCOR-8849
        } // POCOR-8849
        if ($action == 'view') { //  POCOR-9118 start

            if ($source == self::SOURCE_OPENEMIS) {
                if (isset($attributes['api_key'])) {
                    $attributes['api_key'] = '*****';
                }
                $attributes['identity_type_id'] = $this->getRelatedName('FieldOption.IdentityTypes', $attributes['identity_type_id']);
                unset($attributes['first_name_mapping']);
                unset($attributes['middle_name_mapping']);
                unset($attributes['third_name_mapping']);
                unset($attributes['last_name_mapping']);
                unset($attributes['date_of_birth_mapping']);
                unset($attributes['external_reference_mapping']);
                unset($attributes['identity_number_mapping']);
                unset($attributes['nationality_mapping']);
                unset($attributes['gender_mapping']);
                unset($attributes['identity_type_mapping']);
                unset($attributes['address_mapping']);
                unset($attributes['postal_mapping']);
                unset($attributes['public_key']);
                unset($attributes['user_endpoint_uri']);
                // POCOR-9118 end
            }
            if ($source == self::SOURCE_SEYCHELLES || $source == self::SOURCE_SEYCHELLOIS) { // POCOR-9481 //POCOR-9590
                if (isset($attributes['client_secret'])) {
                    $attributes['client_secret'] = '*****';
                }
                //POCOR-9590: render the identity type as its name (e.g. "National Identity Number (NIN)")
                //instead of the raw foreign-key id — mirrors the OpenEMIS Core branch above.
                if (isset($attributes['identity_type_id'])) {
                    $attributes['identity_type_id'] = $this->getRelatedName('FieldOption.IdentityTypes', $attributes['identity_type_id']);
                }
                unset($attributes['middle_name_mapping']);
                unset($attributes['third_name_mapping']);
                unset($attributes['external_reference_mapping']);
                unset($attributes['identity_number_mapping']);
                unset($attributes['identity_type_mapping']);
                unset($attributes['address_mapping']);
                unset($attributes['postal_mapping']);
                unset($attributes['public_key']);
                unset($attributes['user_endpoint_uri']); //POCOR-9590: hidden — Seychelles uses api_url, not user_endpoint_uri
                // POCOR-9118 end
            }
            foreach ($attributes as $key => $obj) {
                $rowData = [];
                $rowData[] = __(Inflector::humanize($key));
                $rowData[] = nl2br($obj);
                $tableCells[] = $rowData;
            }
        }
        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

        return $event->getSubject()->renderElement('Configuration.external_data_source', ['attr' => $attr]); // POCOR-8849
    }

    /**
     * // POCOR-8985
     * common proc to show related field in the index table
     * @param $tableName
     * @param $relatedField
     * @return string
     *
     *     POCOR-9118
     */
    public function getRelatedName($tableName, $relatedField)
    {
        if (!$relatedField) {
            return "";
        }
        $Table = TableRegistry::getTableLocator()->get($tableName);
        try {
            $related = $Table->get($relatedField);
            $name = strval($related->name);
            return $name;
        } catch (RecordNotFoundException $e) {
            return $relatedField;
        }
    }

    public function onUpdateFieldValue(EventInterface $event, array $attr, $action, ServerRequest $request) // POCOR-8849
    {
        // POCOR-7981 START
        if (in_array($action, ['edit'])) {
            $optionTable = self::getDynamicTableInstance('Configuration.ConfigItemOptions'); // POCOR-8849
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

    /** POCOR-9118
     *
     **/
    public function onUpdateFieldIdentityTypeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {

        $IdentityTypesTable = TableRegistry::getTableLocator()->get('FieldOption.IdentityTypes');
        $identityTypes  = $IdentityTypesTable
            ->find('list')
            ->toArray();

        $attr['options'] = $identityTypes;

        return $attr;
    }

    public function editBeforePatch(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOption, ArrayObject $extra)
    {
        // POCOR-7981 START
        $alias = $this->getAlias(); // POCOR-8849
        $data = $requestData[$alias];
        $source = $entity['name'];

        if ($source == self::SOURCE_UNHCR) {
            $patchOption['validate'] = true;
            return;
        }

        if ($source == self::SOURCE_CUSTOM) {
            $patchOption['validate'] = 'Custom';
        }

        if ($source == self::SOURCE_JORDAN_CSPD) {//POCOR-6930
            $patchOption['validate'] = 'JordanCSPD';
        }

        if ($data['value'] != self::SOURCE_JORDAN_CSPD) {//POCOR-6930 add if condition
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

    public function editAfterSave(EventInterface $event, Entity $entity, ArrayObject $patchOption, ArrayObject $extra)
    {
        //POCOR-6930, 7981 Starts
        $source = $entity->name;

// POCOR-9118 Gather and clean up validation errors
        $errors = $entity->getErrors();

// If there are remaining errors, format and display them
        if (!empty($errors)) {
            $messages = [];

            foreach ($errors as $field => $rules) {
                foreach ($rules as $rule => $message) {
                    if ($rule === '_empty') {
                        // Convert field_name to “Field Name”
                        $label = Inflector::humanize($field);
                        $messages[] = "{$label} – {$message}";
                    } else {
                        // Other rules: just append the message
                        $messages[] = $message;
                    }
                }
            }

            $alertText = implode("<br>", $messages);
            $this->Alert->error($alertText, [
                'type'   => 'string',
                'escape' => false,    // allow HTML <br>
                'reset'  => true
            ]);

        } else {//POCOR-6930 Ends
            $this->updateAttributes($source, $entity);
        }
    }

    public function editAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {

        $source = $entity->name;
        $this->field('value', ['visible' => true, 'entity' => $entity]); // POCOR-7981
        $this->field('value_selection', ['visible' => false]); //POCOR-7981 not used field
        switch ($source) {
            case self::SOURCE_OPENEMIS: // POCOR-9118 start
                $this->field('api_url', ['type' => 'string', 'required' => 'required', 'attr' => ['required' => 'required']]);
                $this->field('username', ['type' => 'string', 'required' => 'required', 'attr' => ['required' => 'required']]);
                $this->field('password', ['type' => 'password', 'required' => 'required', 'attr' => ['value' => '', 'required' => 'required'], 'autocomplete' => 'off']);
                $this->field('api_key', ['type' => 'password', 'required' => 'required',  'attr' => ['value' => '', 'required' => 'required'], 'autocomplete' => 'off']);
                $this->field('identity_type_id', [
                    'type' => 'select',
                    'after' => 'api_key',
                    'entity' => $entity,
                    'attr' => ['required' => 'required']
                ]); // POCOR-9118 end
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

                break;
            // POCOR-9481
            case self::SOURCE_SEYCHELLOIS: //POCOR-9590
            case self::SOURCE_SEYCHELLES: // POCOR-9481 start
                $this->field('client_id', ['type' => 'string', 'required' => 'required', 'attr' => ['required' => 'required']]);
                $this->field('token_uri', ['type' => 'string', 'required' => 'required', 'attr' => ['required' => 'required']]);
                $this->field('api_url', ['type' => 'string', 'required' => 'required', 'attr' => ['required' => 'required']]);
                $this->field('client_secret', ['type' => 'password', 'required' => 'required', 'attr' => ['value' => '', 'required' => 'required'], 'autocomplete' => 'off']);
                //POCOR-9590: identity_type_id picks which user_identities row Sync uses;
                //without it, UserBehavior::getActiveExternalSourceIdentityTypeId() returns null and Sync silently no-ops.
                $this->field('identity_type_id', [
                    'type' => 'select',
                    'after' => 'client_secret',
                    'entity' => $entity,
                    'attr' => ['required' => 'required'],
                ]);
                $this->field('grant_type', ['type' => 'string', 'required' => 'required',  'attr' => ['required' => 'required']]);
                $this->field('scopes', ['type' => 'string', 'required' => 'required',  'attr' => ['required' => 'required']]);
                $this->field('first_name_mapping', ['type' => 'string', 'required' => 'required', 'attr' => ['required' => 'required']]);
                $this->field('middle_name_mapping', ['type' => 'hidden']);
                $this->field('third_name_mapping', ['type' => 'hidden']);
                $this->field('last_name_mapping', ['type' => 'string', 'required' => 'required', 'attr' => ['required' => 'required']]);
                $this->field('date_of_birth_mapping', ['type' => 'string', 'required' => 'required', 'attr' => ['required' => 'required']]);
                $this->field('external_reference_mapping', ['type' => 'hidden']);
                $this->field('gender_mapping', ['type' => 'string', 'required' => 'required', 'attr' => ['required' => 'required']]);
                $this->field('identity_type_mapping', ['type' => 'hidden']);
                $this->field('identity_number_mapping', ['type' => 'hidden']);
                $this->field('nationality_mapping', ['type' => 'string', 'required' => 'required', 'attr' => ['required' => 'required']]);
                $this->field('address_mapping', ['type' => 'hidden']);
                $this->field('postal_mapping', ['type' => 'hidden']);
                $this->field('user_endpoint_uri', ['type' => 'hidden']); //POCOR-9590: hidden — api_url is the Seychelles endpoint field

                break;
            // POCOR-7981
            case self::SOURCE_CUSTOM:
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
            case self::SOURCE_JORDAN_CSPD:
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
            case self::SOURCE_UNHCR:
                $this->field('secret_code');
                $this->field('url');
                $this->field('application_id');
                break;//POCOR-7981 Ends
            default:
                break;
        }
    }

    // POCOR-7981
    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        if (isset($extra['toolbarButtons']['add'])) {
            unset($extra['toolbarButtons']['add']);
        }
    }

    //POCOR-7981:Start
    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
//        $optionTable = self::getDynamicTableInstance('Configuration.ConfigItemOptions');
        $query
            ->select(
                [$this->aliasField('id'),
                    $this->aliasField('label'),
                    $this->aliasField('value')]
            )->where([
                $this->aliasField('type') => 'External Data Source - Identity'
            ]);
    }

    public function onGetValue(EventInterface $event, Entity $entity)
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
    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
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

    public function onGetLabel(EventInterface $event, Entity $entity)
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
            $ExternalDataSourceAttributes = self::getDynamicTableInstance('Configuration.ExternalDataSourceAttributes'); // POCOR-8849
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
                $entity->{$key} = $value; // POCOR-8849
            }

        }
    }

    protected function processAuthentication(&$attribute, $authenticationType)
    {
        $ExternalDataSourceAttributesTable = self::getDynamicTableInstance('ExternalDataSourceAttributes'); // POCOR-8849
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
    /** // POCOR-8849
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName . POCOR-8231
     * @return \Cake\ORM\Table
     *
     */
    private static function getDynamicTableInstance(string $tableName): Table
    {
        // Parse plugin and table names if dot notation is used
        // Create a TableLocator instance
        $locator = TableRegistry::getTableLocator();

        try {
            // Try to get the table instance directly
            return $locator->get($tableName);
        } catch (\Exception $e) {
            Log::debug('Error: ' . $e->getMessage());
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

    // POCOR-7981 END

    /**
     * @param mixed $source
     * @param Entity $entity
     * @return void
     */
    private function updateAttributes(mixed $source, Entity $entity): void
    {
        $ExternalDataSourceAttributes = self::getDynamicTableInstance('Configuration.ExternalDataSourceAttributes'); // POCOR-8849
        $existingRecords = $ExternalDataSourceAttributes->find('list', [
            'keyField' => 'attribute_field',
            'valueField' => 'value'
        ])->where(['external_data_source_type' => $source])->toArray();

        $fields = [
            'url', 'token_uri', 'record_uri', 'user_endpoint_uri', 'client_id', 'scope',
            'username', 'password', 'api_url', 'api_key', 'identity_type_id', 'first_name_mapping', 'middle_name_mapping',
            'third_name_mapping', 'last_name_mapping', 'date_of_birth_mapping', 'external_reference_mapping',
            'gender_mapping', 'identity_type_mapping', 'identity_number_mapping', 'nationality_mapping',
            'address_mapping', 'postal_mapping', 'private_key', 'public_key', 'secret_code', 'application_id',
            'gender_id_mapping', 'openemis_no_mapping', 'scopes', 'client_secret', 'grant_type' // POCOR-9481
        ];

        foreach ($fields as $field) {
            if ($entity->has($field)) {
                $newValue = trim($entity->{$field}); // POCOR-9118
                $currentValue = $existingRecords[$field] ?? null;

                // Skip update if password or api_key is empty in entity but present in DB
                if (in_array($field, ['password', 'api_key', 'client_secret']) && empty($newValue) && !empty($currentValue)) { // POCOR-9481
                    continue;
                }

                if ($newValue !== $currentValue) {
                    $data = [
                        'external_data_source_type' => $source,
                        'attribute_field' => $field,
                        'attribute_name' => $field,
                        'value' => $newValue
                    ];

                    $existingEntity = $ExternalDataSourceAttributes->find()->where([
                        'external_data_source_type' => $source,
                        'attribute_field' => $field
                    ])->first();

                    if ($existingEntity) {
                        $ExternalDataSourceAttributes->patchEntity($existingEntity, $data);
                        $ExternalDataSourceAttributes->save($existingEntity);
                    } else {
                        $newEntity = $ExternalDataSourceAttributes->newEntity($data);
                        $ExternalDataSourceAttributes->save($newEntity);
                    }
                }
            }
        }
    }

}
