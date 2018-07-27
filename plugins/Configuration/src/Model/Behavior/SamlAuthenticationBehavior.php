<?php
namespace Configuration\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Routing\Router;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;

class SamlAuthenticationBehavior extends Behavior
{
    private $model;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->model = $this->_table;
    }

    public function implementedEvents()
    {
        $events = [
            'ControllerAction.Model.beforeAction' => 'beforeAction',
            'ControllerAction.Model.view.afterAction' => 'viewAfterAction',
            'ControllerAction.Model.edit.afterAction' => 'editAfterAction',
            'ControllerAction.Model.addEdit.afterAction' => 'addEditAfterAction',
            'ControllerAction.Model.onUpdateFieldIdpSloBinding' => 'onUpdateFieldIdpSsoBinding',
            'ControllerAction.Model.onUpdateFieldIdpSloBinding' => 'onUpdateFieldIdpSloBinding'
        ];
        return $events;
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $entity->errors($entity->errors('saml'), null, true);
        $this->model->field('idp_entity_id', ['attr' => ['required' => true, 'label' => __('Identity Provider - Entity ID')]]);
        $this->model->field('idp_sso', ['attr' => ['required' => true, 'label' => __('Identity Provider - Single Signon Service')]]);
        $this->model->field('idp_sso_binding', ['attr' => ['required' => true, 'label' => __('Identity Provider - Single Signon Service Binding')]]);
        $this->model->field('idp_slo', ['attr' => ['required' => true, 'label' => __('Identity Provider - Single Logout Service')]]);
        $this->model->field('idp_slo_binding', ['attr' => ['required' => true, 'label' => __('Identity Provider - Single Logout Service Binding')]]);
        $this->model->field('idp_x509cert', ['type' => 'text', 'attr' => ['required' => true, 'label' => __('Identity Provider - X509 Certificate')]]);
        $this->model->field('idp_cert_fingerprint', ['attr' => ['label' => __('Identity Provider - Certificate Fingerprint')]]);
        $this->model->field('idp_cert_fingerprint_algorithm', ['attr' => ['label' => __('Identity Provider - Certificate Fingerprint Algorithm')]]);
        $this->model->field('sp_entity_id', ['type' => 'readonly', 'attr' => ['label' => __('Service Provider - Entity ID')]]);
        $this->model->field('sp_acs', ['type' => 'readonly', 'attr' => ['label' => __('Service Provider - Assertion Consumer Service')]]);
        $this->model->field('sp_slo', ['type' => 'readonly', 'attr' => ['label' => __('Service Provider - Single Logout Service')]]);
        $this->model->field('sp_name_id_format', ['attr' => ['label' => __('Service Provider - Name ID Format')]]);
        $this->model->field('sp_private_key', ['attr' => ['label' => __('Service Provider - Private Key')]]);
        // $this->model->field('sp_metadata', ['type' => 'hidden']);
        if ($entity->errors('code')) {
            $code = uniqid('IDP');
            $this->model->request->data[$this->alias()]['code'] = $code;
            $entity->invalid('code', $code, true);
            $entity->errors('sp_acs', $entity->errors('code'), true);
        }
        $url = Router::url(['plugin' => null, 'controller' => null, 'action' => 'index'], true);
        $this->model->fields['sp_entity_id']['value'] = $url;
        $this->model->fields['sp_entity_id']['attr']['value'] = $url;

        $loginUrl = Router::url(['plugin' => 'User', 'controller' => 'Users', 'action' => 'postLogin', 'Saml', $this->model->request->data[$this->model->alias()]['code']], true);
        $this->model->fields['sp_acs']['value'] = $loginUrl;
        $this->model->fields['sp_acs']['attr']['value'] = $loginUrl;

        $logoutUrl = Router::url(['plugin' => 'User', 'controller' => 'Users', 'action' => 'logout'], true);
        $this->model->fields['sp_slo']['value'] = $logoutUrl;
        $this->model->fields['sp_slo']['attr']['value'] = $logoutUrl;

        $this->model->fields['mapped_username']['type'] = 'string';
        $this->model->fields['mapped_first_name']['type'] = 'string';
        $this->model->fields['mapped_last_name']['type'] = 'string';
        $this->model->fields['mapped_date_of_birth']['type'] = 'string';
        $this->model->fields['mapped_gender']['type'] = 'string';
        $this->model->fields['mapped_role']['type'] = 'string';
        $this->model->fields['mapped_email']['type'] = 'string';

        $this->model->setFieldOrder(['name', 'authentication_type_id', 'status', 'idp_entity_id', 'idp_sso', 'idp_sso_binding', 'idp_slo', 'idp_slo_binding', 'idp_x509cert', 'idp_cert_fingerprint', 'idp_cert_fingerprint_algorithm', 'sp_entity_id', 'sp_acs', 'sp_slo', 'sp_name_id_format', 'sp_private_key', 'allow_create_user', 'mapped_username', 'mapped_first_name', 'mapped_last_name', 'mapped_date_of_birth', 'mapped_gender', 'mapped_role', 'mapped_email']);
    }

    public function onUpdateFieldIdpSsoBinding(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            if (!isset($request->data[$this->model->alias()]['idp_sso_binding'])) {
                $request->data[$this->model->alias()]['idp_sso_binding'] = 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST';
            }
        }
    }

    public function onUpdateFieldIdpSloBinding(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            if (!isset($request->data[$this->model->alias()]['idp_slo_binding'])) {
                $request->data[$this->model->alias()]['idp_slo_binding'] = 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect';
            }
        }
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->model->field('idp_entity_id', ['attr' => ['label' => __('Identity Provider - Entity ID')]]);
        $this->model->field('idp_sso', ['attr' => ['label' => __('Identity Provider - Single Signon Service')]]);
        $this->model->field('idp_sso_binding', ['attr' => ['label' => __('Identity Provider - Single Signon Service Binding')]]);
        $this->model->field('idp_slo', ['attr' => ['label' => __('dentity Provider - Single Logout Service')]]);
        $this->model->field('idp_slo_binding', ['attr' => ['label' => __('Identity Provider - Single Logout Service Binding')]]);
        $this->model->field('idp_x509cert', ['attr' => ['label' => __('Identity Provider - X509 Certificate')]]);
        $this->model->field('idp_cert_fingerprint', ['attr' => ['label' => __('Identity Provider - Certificate Fingerprint')]]);
        $this->model->field('idp_cert_fingerprint_algorithm', ['attr' => ['label' => __('Identity Provider - Certificate Fingerprint Algorithm')]]);
        $this->model->field('sp_entity_id', ['attr' => ['label' => __('Service Provider - Entity ID')]]);
        $this->model->field('sp_acs', ['attr' => ['label' => __('Service Provider - Assertion Consumer Service')]]);
        $this->model->field('sp_slo', ['attr' => ['label' => __('Service Provider - Single Logout Service')]]);
        $this->model->field('sp_name_id_format', ['attr' => ['label' => __('Service Provider - Name ID Format')]]);
        $this->model->field('sp_private_key', ['attr' => ['label' => __('Service Provider - Private Key')]]);
        $this->model->field('sp_metadata', ['type' => 'text', 'attr' => ['label' => __('Service Provider - Metadata')]]);
        $this->model->fields['mapped_username']['type'] = 'string';
        $this->model->fields['mapped_first_name']['type'] = 'string';
        $this->model->fields['mapped_last_name']['type'] = 'string';
        $this->model->fields['mapped_date_of_birth']['type'] = 'string';
        $this->model->fields['mapped_gender']['type'] = 'string';
        $this->model->fields['mapped_role']['type'] = 'string';
        $this->model->fields['mapped_email']['type'] = 'string';
        $this->model->setFieldOrder(['name', 'authentication_type_id', 'status', 'idp_entity_id', 'idp_sso', 'idp_sso_binding', 'idp_slo', 'idp_slo_binding', 'idp_x509cert', 'idp_cert_fingerprint', 'idp_cert_fingerprint_algorithm', 'sp_entity_id', 'sp_acs', 'sp_slo', 'sp_name_id_format', 'sp_private_key', 'sp_metadata', 'allow_create_user', 'mapped_username', 'mapped_first_name', 'mapped_last_name', 'mapped_date_of_birth', 'mapped_gender', 'mapped_role', 'mapped_email']);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->model->fields['name']['type'] = 'readonly';
        $this->model->fields['idp_entity_id']['attr']['value'] = $entity->saml->idp_entity_id;
        $this->model->fields['idp_sso']['attr']['value'] = $entity->saml->idp_sso;
        $this->model->fields['idp_sso_binding']['attr']['value'] = $entity->saml->idp_sso_binding;
        $this->model->fields['idp_slo']['attr']['value'] = $entity->saml->idp_slo;
        $this->model->fields['idp_slo_binding']['attr']['value'] = $entity->saml->idp_slo_binding;
        $this->model->fields['idp_x509cert']['attr']['value'] = $entity->saml->idp_x509cert;
        $this->model->fields['idp_cert_fingerprint']['attr']['value'] = $entity->saml->idp_cert_fingerprint;
        $this->model->fields['idp_cert_fingerprint_algorithm']['attr']['value'] = $entity->saml->idp_cert_fingerprint_algorithm;
        $this->model->fields['sp_entity_id']['attr']['value'] = $entity->saml->sp_entity_id;
        $this->model->fields['sp_entity_id']['value'] = $entity->saml->sp_entity_id;
        $this->model->fields['sp_acs']['value'] = $entity->saml->sp_acs;
        $this->model->fields['sp_acs']['attr']['value'] = $entity->saml->sp_acs;
        $this->model->fields['sp_slo']['value'] = $entity->saml->sp_slo;
        $this->model->fields['sp_slo']['attr']['value'] = $entity->saml->sp_slo;
        $this->model->fields['sp_name_id_format']['attr']['value'] = $entity->saml->sp_name_id_format;
        $this->model->fields['sp_private_key']['attr']['value'] = $entity->saml->sp_private_key;
    }

    public function onGetIdpEntityId(Event $event, Entity $entity)
    {
        return $entity->saml->idp_entity_id;
    }

    public function onGetIdpSso(Event $event, Entity $entity)
    {
        return $entity->saml->idp_sso;
    }

    public function onGetIdpSsoBinding(Event $event, Entity $entity)
    {
        return $entity->saml->idp_sso_binding;
    }

    public function onGetIdpSlo(Event $event, Entity $entity)
    {
        return $entity->saml->idp_slo;
    }

    public function onGetIdpSloBinding(Event $event, Entity $entity)
    {
        return $entity->saml->idp_slo_binding;
    }

    public function onGetIdpX509cert(Event $event, Entity $entity)
    {
        return $entity->saml->idp_x509cert;
    }

    public function onGetIdpCertFingerprint(Event $event, Entity $entity)
    {
        return $entity->saml->idp_cert_fingerprint;
    }

    public function onGetIdpCertFingerprintAlgorithm(Event $event, Entity $entity)
    {
        return $entity->saml->idp_cert_fingerprint_algorithm;
    }

    public function onGetSpEntityId(Event $event, Entity $entity)
    {
        return $entity->saml->sp_entity_id;
    }

    public function onGetSpAcs(Event $event, Entity $entity)
    {
        return $entity->saml->sp_acs;
    }

    public function onGetSpSlo(Event $event, Entity $entity)
    {
        return $entity->saml->sp_slo;
    }

    public function onGetSpNameIdFormat(Event $event, Entity $entity)
    {
        return $entity->saml->sp_name_id_format;
    }

    public function onGetSpPrivateKey(Event $event, Entity $entity)
    {
        return $entity->saml->sp_private_key;
    }

    public function onGetSpMetadata(Event $event, Entity $entity)
    {
        return $entity->saml->sp_metadata;
    }
}
