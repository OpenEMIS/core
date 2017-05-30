<?php
namespace Configuration\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Routing\Router;
use Cake\Validation\Validator;
use OneLogin_Saml2_Constants;
use OneLogin_Saml_Settings;
use OneLogin_Saml2_Error;

class AuthenticationBehavior extends Behavior
{
    private $alias;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->alias = $this->_table->alias();
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'ControllerAction.Model.beforeAction' => ['callable' => 'beforeAction', 'priority' => 11],
            'Model.custom.onUpdateToolbarButtons' => 'onUpdateToolbarButtons',
            'ControllerAction.Model.edit.afterSave'     => 'editAfterSave',
            'ControllerAction.Model.afterAction'    => 'afterAction',
            'ControllerAction.Model.view.beforeAction'  => 'viewBeforeAction',
            'ControllerAction.Model.edit.beforeAction'  => 'editBeforeAction',
            'ControllerAction.Model.edit.beforePatch'   => 'editBeforePatch'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function beforeAction(Event $event)
    {
        if ($this->_table->action == 'view' || $this->_table->action == 'edit') {
            $key = $this->_table->id;
            if (!empty($key)) {
                $configItem = $this->_table->get($key);
                if ($configItem->type == 'Authentication' && $configItem->code == 'authentication_type') {
                    if (isset($this->_table->request->data[$this->alias]['value']) && !empty($this->_table->request->data[$this->alias]['value'])) {
                        $value = $this->_table->request->data[$this->alias]['value'];
                    } else {
                        $value = $this->_table->authenticationType;
                        $this->_table->request->data[$this->alias]['value'] = $value;
                    }
                    if ($value != 'Local') {
                        $this->_table->field('custom_authentication', ['type' => 'authentication_type', 'valueClass' => 'table-full-width', 'visible' => [ 'edit' => true, 'view' => true ]]);
                    }
                }
            }
        }
    }

    public function afterAction(Event $event)
    {
        if ($this->_table->action == 'view' || $this->_table->action == 'edit') {
            $key = $this->_table->id;
            if (!empty($key)) {
                $configItem = $this->_table->get($key);
                if ($configItem->type == 'Authentication' && $configItem->code == 'authentication_type') {
                    $this->_table->field('default_value', ['visible' => false]);
                    $value = $this->_table->request->data[$this->alias]['value'];
                }
            }
        }
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        if (isset($extra['toolbarButtons']['back'])) {
            unset($extra['toolbarButtons']['back']);
        }
    }

    public function editBeforeAction(Event $event, ArrayObject $extra)
    {
        if (isset($extra['toolbarButtons']['list'])) {
            unset($extra['toolbarButtons']['list']);
        }
    }

    protected function processAuthentication(&$attribute, $authenticationType)
    {
        $AuthenticationTypeAttributesTable = TableRegistry::get('SSO.AuthenticationTypeAttributes');
        $attributesArray = $AuthenticationTypeAttributesTable->find()->where([$AuthenticationTypeAttributesTable->aliasField('authentication_type') => $authenticationType])->toArray();
        $attributeFieldsArray = $this->_table->array_column($attributesArray, 'attribute_field');
        foreach ($attribute as $key => $values) {
            $attributeValue = '';
            if (array_search($key, $attributeFieldsArray) !== false) {
                $attributeValue = $attributesArray[array_search($key, $attributeFieldsArray)]['value'];
            }
            if (method_exists($this, strtolower($authenticationType).'ModifyValue')) {
                $method = strtolower($authenticationType).'ModifyValue';
                $result = $this->$method($key, $attributeValue);
                if ($result !== false) {
                    $attributeValue = $result;
                }
            }
            $attribute[$key]['value'] = $attributeValue;
        }
    }

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if (empty($entity->errors())) {
            $AuthenticationTypeAttributesTable = TableRegistry::get('SSO.AuthenticationTypeAttributes');
            $authenticationType = $data[$this->alias]['value'];
            $AuthenticationTypeAttributesTable->deleteAll(
                ['authentication_type' => $authenticationType]
            );
            if (!isset($data['AuthenticationTypeAttributes'])) {
                $data['AuthenticationTypeAttributes'] = [];
            }
            foreach ($data['AuthenticationTypeAttributes'] as $key => $value) {
                $entityData = [
                    'authentication_type' => $authenticationType,
                    'attribute_field' => $key,
                    'attribute_name' => $value['name'],
                    'value' => trim($value['value'])
                ];
                $entity = $AuthenticationTypeAttributesTable->newEntity($entityData);
                $AuthenticationTypeAttributesTable->save($entity);
            }

            if (method_exists($this, strtolower($authenticationType).'AfterSave')) {
                $method = strtolower($authenticationType).'AfterSave';
                $this->$method($data['AuthenticationTypeAttributes']);
            }
        }
    }

    public function saml2AfterSave($samlAttributes)
    {
        $setting['sp'] = [
            'entityId' => $samlAttributes['sp_entity_id']['value'],
            'assertionConsumerService' => [
                'url' => $samlAttributes['sp_acs']['value'],
            ],
            'singleLogoutService' => [
                'url' => $samlAttributes['sp_slo']['value'],
            ],
            'NameIDFormat' => $samlAttributes['sp_name_id_format']['value'],
        ];

        $message = $this->getSPMetaData($setting);

        $AuthenticationTypeAttributesTable = TableRegistry::get('SSO.AuthenticationTypeAttributes');
        $entity = $AuthenticationTypeAttributesTable->find()->where([
                $AuthenticationTypeAttributesTable->aliasField('authentication_type') => 'Saml2',
                $AuthenticationTypeAttributesTable->aliasField('attribute_field') => 'sp_metadata'
            ])
            ->first();

        if (!empty($entity)) {
            $entity->value = htmlentities($message);
            $AuthenticationTypeAttributesTable->save($entity);
        }
    }

    public function getSPMetaData($settingsInfo)
    {
        try {
            // Now we only validate SP settings
            $settings = new OneLogin_Saml2_Settings($settingsInfo, true);
            $metadata = $settings->getSPMetadata();
            $errors = $settings->validateMetadata($metadata);
            if (empty($errors)) {
                header('Content-Type: text/xml');
                return $metadata;
            } else {
                throw new OneLogin_Saml2_Error(
                    'Invalid SP metadata: '.implode(', ', $errors),
                    OneLogin_Saml2_Error::METADATA_SP_INVALID
                );
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $configItem = $data[$this->_table->alias()];
        if ($configItem['type'] == 'Authentication') {
            $methodName = strtolower($configItem['value']).'AuthenticationValidation';
            if (method_exists($this, $methodName) && !$this->$methodName($data['AuthenticationTypeAttributes'])) {
                $this->_table->Alert->error('security.emptyFields', ['reset' => true]);
                ;
                $entity->errors('error', ['There are invalid Authentication Attributes']);
            }
        }
    }

    public function saml2AuthenticationValidation($authenticationAttributes)
    {
        $attribute = [];
        $this->saml2Authentication($attribute);
        foreach ($attribute as $key => $values) {
            if (!isset($values['required'])) {
                if (empty($authenticationAttributes[$key]['value']) && $values['type'] != 'select') {
                    return false;
                }
            }
        }
        return true;
    }

    public function googleAuthenticationValidation($authenticationAttributes)
    {
        $attribute = [];
        $this->googleAuthentication($attribute);
        foreach ($attribute as $key => $values) {
            if (!isset($values['required'])) {
                if (empty($authenticationAttributes[$key]['value']) && $values['type'] != 'select') {
                    return false;
                }
            }
        }
        return true;
    }

    public function oAuth2OpenIDConnectAuthenticationValidation($authenticationAttributes)
    {
        $attribute = [];
        $this->oAuth2OpenIDConnectAuthentication($attribute);
        foreach ($attribute as $key => $values) {
            if (!isset($values['required'])) {
                if (empty($authenticationAttributes[$key]['value']) && $values['type'] != 'select') {
                    return false;
                }
            }
        }
        return true;
    }

    public function saml2Authentication(&$attribute)
    {
        $attribute['idp_entity_id'] = ['label' => 'Identity Provider - Entity ID', 'type' => 'text'];
        $attribute['idp_sso'] = ['label' => 'Identity Provider - Single Signon Service', 'type' => 'text'];
        $attribute['idp_sso_binding'] = ['label' => 'Identity Provider - Single Signon Service Binding', 'type' => 'text'];
        $attribute['idp_slo'] = ['label' => 'Identity Provider - Single Logout Service', 'type' => 'text'];
        $attribute['idp_slo_binding'] = ['label' => 'Identity Provider - Single Logout Service Binding', 'type' => 'text'];
        $attribute['idp_x509cert'] = ['label' => 'Identity Provider - X509 Certificate', 'type' => 'textarea', 'maxlength' => 1500, 'required' => false];
        $attribute['idp_certFingerprint'] = ['label' => 'Identity Provider - Certificate Fingerprint', 'type' => 'text', 'required' => false];
        $attribute['idp_certFingerprintAlgorithm'] = ['label' => 'Identity Provider - Certificate Fingerprint Algorithm', 'type' => 'text', 'required' => false];
        $attribute['sp_entity_id'] = ['label' => 'Service Provider - Entity ID', 'type' => 'text', 'readonly' => true];
        $attribute['sp_acs'] = ['label' => 'Service Provider - Assertion Consumer Service', 'type' => 'text', 'readonly' => true];
        $attribute['sp_slo'] = ['label' => 'Service Provider - Single Logout Service', 'type' => 'text', 'readonly' => true];
        $attribute['sp_name_id_format'] = ['label' => 'Service Provider - Name ID Format', 'type' => 'text', 'required' => false];
        $attribute['sp_privateKey'] = ['label' => 'Service Provider - Private Key', 'type' => 'textarea', 'maxlength' => 1500, 'required' => false];
        $attribute['saml_username_mapping'] = ['label' => 'Username Mapping', 'type' => 'text'];
        $attribute['allow_create_user'] = ['label' => 'Allow User Creation', 'type' => 'select', 'options' => $this->_table->getSelectOptions('Authentication.yesno')];
        $attribute['saml_first_name_mapping'] = ['label' => 'First Name Mapping', 'type' => 'text', 'required' => false];
        $attribute['saml_last_name_mapping'] = ['label' => 'Last Name Mapping', 'type' => 'text', 'required' => false];
        $attribute['saml_gender_mapping'] = ['label' => 'Gender Mapping', 'type' => 'text', 'required' => false];
        $attribute['saml_date_of_birth_mapping'] = ['label' => 'Date of birth mapping', 'type' => 'text', 'required' => false];
        $attribute['saml_role_mapping'] = ['label' => 'Role mapping', 'type' => 'hidden', 'required' => false];
        $attribute['sp_metadata'] = ['label' => 'Service Provider - Metadata', 'type' => 'hidden', 'required' => false];
    }

    public function saml2ModifyValue($key, $attributeValue)
    {
        if ($key == 'sp_entity_id') {
            return Router::url(['plugin' => null, 'controller' => null, 'action' => 'index'], true);
        } elseif ($key == 'sp_slo') {
            return Router::url(['plugin' => null, 'controller' => 'Users', 'action' => 'logout'], true);
        } elseif ($key == 'idp_sso_binding' && empty($attributeValue)) {
            return OneLogin_Saml2_Constants::BINDING_HTTP_POST;
        } elseif ($key == 'idp_slo_binding' && empty($attributeValue)) {
            return OneLogin_Saml2_Constants::BINDING_HTTP_REDIRECT;
        } elseif ($key == 'sp_acs') {
            return Router::url(['plugin' => null, 'controller' => 'Users', 'action' => 'postLogin'], true);
        }
        return false;
    }

    public function googleAuthentication(&$attribute)
    {
        $attribute['client_id'] = ['label' => 'Client ID', 'type' => 'text'];
        $attribute['client_secret'] = ['label' => 'Client Secret', 'type' => 'text'];
        $attribute['redirect_uri'] = ['label' => 'Redirect URI', 'type' => 'text', 'readonly' => true];
        $attribute['hd'] = ['label' => 'Hosted Domain', 'type' => 'text', 'required' => false];
        $attribute['allow_create_user'] = ['label' => 'Allow User Creation', 'type' => 'select', 'options' => $this->_table->getSelectOptions('Authentication.yesno')];
    }

    public function oAuth2OpenIDConnectAuthentication(&$attribute)
    {
        $attribute['client_id'] = ['label' => 'Client ID', 'type' => 'text'];
        $attribute['client_secret'] = ['label' => 'Client Secret', 'type' => 'text'];
        $attribute['redirect_uri'] = ['label' => 'Redirect URI', 'type' => 'text', 'readonly' => true];
        $attribute['openid_configuration'] = ['label' => 'OpenID Configuration URI', 'type' => 'text', 'required' => false, 'onblur' => 'Authentication.populate(this.value);'];
        $attribute['auth_uri'] = ['label' => 'Authentication URI', 'type' => 'text', 'id' => 'authUri'];
        $attribute['token_uri'] = ['label' => 'Token URI', 'type' => 'text', 'id' => 'tokenUri'];
        $attribute['userInfo_uri'] = ['label' => 'User Information URI', 'type' => 'text', 'id' => 'userInfoUri'];
        $attribute['issuer'] = ['label' => 'Issuer', 'type' => 'text', 'id' => 'issuer'];
        $attribute['jwk_uri'] = ['label' => 'Public Key URI', 'type' => 'text', 'id' => 'jwksUri'];
        $attribute['username_mapping'] = ['label' => 'Username Mapping', 'type' => 'text'];
        $attribute['allow_create_user'] = ['label' => 'Allow User Creation', 'type' => 'select', 'options' => $this->_table->getSelectOptions('Authentication.yesno')];
        $attribute['firstName_mapping'] = ['label' => 'First Name Mapping', 'type' => 'text', 'required' => false];
        $attribute['lastName_mapping'] = ['label' => 'Last Name Mapping', 'type' => 'text', 'required' => false];
        $attribute['dob_mapping'] = ['label' => 'Date of Birth Mapping', 'type' => 'text', 'required' => false];
        $attribute['gender_mapping'] = ['label' => 'Gender Mapping', 'type' => 'text', 'required' => false];
        $attribute['role_mapping'] = ['label' => 'Role Mapping', 'type' => 'hidden', 'required' => false];
    }

    public function googleModifyValue($key, $attributeValue)
    {
        if ($key == 'redirect_uri') {
            return Router::url(['plugin' => null, 'controller' => 'Users', 'action' => 'postLogin'], true);
        }
        return false;
    }

    public function oAuth2OpenIDConnectModifyValue($key, $attributeValue)
    {
        if ($key == 'redirect_uri') {
            return Router::url(['plugin' => null, 'controller' => 'Users', 'action' => 'postLogin'], true);
        }
        return false;
    }

    public function onGetAuthenticationTypeElement(Event $event, $action, $entity, $attr, $options = [])
    {
        switch ($action) {
            case "view":
                $authenticationType = $this->_table->request->data[$this->alias]['value'];
                $attribute = [];
                $methodName = strtolower($authenticationType).'Authentication';
                if (method_exists($this, $methodName)) {
                    $this->$methodName($attribute);
                    $this->processAuthentication($attribute, $authenticationType);
                }

                $tableHeaders = [__('Attribute Name'), __('Value')];
                $tableCells = [];
                foreach ($attribute as $value) {
                    $row = [];
                    $row[] = $value['label'];
                    if ($value['label'] == 'Allow User Creation') {
                        $value['value'] = __($this->_table->getSelectOptions('Authentication.yesno')[$value['value']]);
                    }
                    $row[] = $value['value'];
                    $tableCells[] = $row;
                }
                $attr['tableHeaders'] = $tableHeaders;
                $attr['tableCells'] = $tableCells;
                break;

            case "edit":
                $authenticationType = $this->_table->request->data[$this->alias]['value'];
                $attribute = [];
                $methodName = strtolower($authenticationType).'Authentication';
                if (method_exists($this, $methodName)) {
                    $this->$methodName($attribute);
                    $this->processAuthentication($attribute, $authenticationType);
                }

                $attr = $attribute;
                break;
        }
        return $event->subject()->renderElement('Configuration.authentication', ['attr' => $attr]);
    }
}
