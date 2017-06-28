<?php
namespace Configuration\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;
use Cake\ORM\Query;
use App\Model\Traits\OptionsTrait;
use Cake\Routing\Router;
use OneLogin_Saml2_Error;
use OneLogin_Saml2_Settings;


class ConfigSamlTable extends ControllerActionTable
{
    use OptionsTrait;

    public function initialize(array $config)
    {
        $this->table('system_authentications');
        parent::initialize($config);
        $this->hasOne('Saml', ['className' => 'SSO.IdpSaml', 'foreignKey' => 'system_authentication_id', 'joinType' => 'INNER', 'dependent' => true]);
        $this->belongsTo('AuthenticationTypes', ['className' => 'SSO.AuthenticationTypes', 'foreignKey' => 'authentication_type_id', 'joinType' => 'INNER']);
        $this->addBehavior('Configuration.Authentication');
    }

    public function validationDefault(Validator $validator)
    {
        return $validator
            ->requirePresence('idp_entity_id')
            ->notEmpty('idp_entity_id')
            ->requirePresence('idp_sso')
            ->notEmpty('idp_sso_binding')
            ->requirePresence('idp_slo')
            ->notEmpty('idp_slo_binding')
            ->requirePresence('idp_x509cert')
            ->notEmpty('idp_x509cert')
            ->requirePresence('sp_entity_id')
            ->notEmpty('sp_entity_id')
            ->requirePresence('sp_acs')
            ->notEmpty('sp_acs')
            ->requirePresence('sp_slo')
            ->notEmpty('sp_slo')
            ->requirePresence('name', 'create')
            ->notEmpty('name', [], 'create')
            ->requirePresence('status', 'create')
            ->notEmpty('status', [], 'create')
            ->requirePresence('code', 'create')
            ->notEmpty('code', [], 'create')
            ->requirePresence('mapped_username')
            ->notEmpty('mapped_username')
            ->requirePresence('allow_create_user')
            ->notEmpty('allow_create_user')
            ->add('code', 'ruleUnique', [
                'rule' => ['validateUnique'],
                'provider' => 'table'
            ]);
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        $setting['sp'] = [
            'entityId' => $data['sp_entity_id'],
            'assertionConsumerService' => [
                'url' => $data['sp_acs'],
            ],
            'singleLogoutService' => [
                'url' => $data['sp_slo'],
            ],
            'NameIDFormat' => $data['sp_name_id_format'],
        ];

        $data['sp_metadata'] = htmlentities($this->getSPMetaData($setting));

        $data['saml'] = [
            'idp_entity_id' => $data['idp_entity_id'],
            'idp_sso' => $data['idp_sso'],
            'idp_sso_binding' => $data['idp_sso_binding'],
            'idp_slo' => $data['idp_slo'],
            'idp_slo_binding' => $data['idp_slo_binding'],
            'idp_x509cert' => $data['idp_x509cert'],
            'idp_cert_fingerprint' => $data['idp_cert_fingerprint'],
            'idp_cert_fingerprint_algorithm' => $data['idp_cert_fingerprint_algorithm'],
            'sp_entity_id' => $data['sp_entity_id'],
            'sp_acs' => $data['sp_acs'],
            'sp_slo' => $data['sp_slo'],
            'sp_name_id_format' => $data['sp_name_id_format'],
            'sp_private_key' => $data['sp_private_key'],
            'sp_metadata' => $data['sp_metadata']
        ];
    }

    public function addOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->request->data[$this->alias()]['code'] = uniqid('IDP');
        $this->request->data[$this->alias()]['idp_slo_binding'] = 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect';
        $this->request->data[$this->alias()]['idp_sso_binding'] = 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST';
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $extra['config']['selectedLink'] = ['controller' => 'Configurations', 'action' => 'index'];
        $authenticationTypeId = $this->AuthenticationTypes->getId('Saml');
        $this->field('name');
        $this->field('code', ['type' => 'hidden']);
        $this->field('authentication_type_id', ['type' => 'hidden', 'value' => $authenticationTypeId]);
        $this->field('status', ['type' => 'select', 'options' => $this->getSelectOptions('general.active')]);

        $this->field('idp_entity_id', ['visible' => ['add' => 'true', 'edit' => true, 'view' => true]]);
        $this->field('idp_sso', ['visible' => ['add' => 'true', 'edit' => true, 'view' => true]]);
        $this->field('idp_sso_binding', ['visible' => ['add' => 'true', 'edit' => true, 'view' => true]]);
        $this->field('idp_slo', ['visible' => ['add' => 'true', 'edit' => true, 'view' => true]]);
        $this->field('idp_slo_binding', ['visible' => ['add' => 'true', 'edit' => true, 'view' => true]]);
        $this->field('idp_x509cert', ['visible' => ['add' => 'true', 'edit' => true, 'view' => true]]);
        $this->field('idp_cert_fingerprint', ['visible' => ['add' => 'true', 'edit' => true, 'view' => true]]);
        $this->field('idp_cert_fingerprint_algorithm', ['visible' => ['add' => 'true', 'edit' => true, 'view' => true]]);
        $this->field('sp_entity_id', ['type' => 'readonly', 'visible' => ['add' => 'true', 'edit' => true, 'view' => true]]);
        $this->field('sp_acs', ['type' => 'readonly', 'visible' => ['add' => 'true', 'edit' => true, 'view' => true]]);
        $this->field('sp_slo', ['type' => 'readonly', 'visible' => ['add' => 'true', 'edit' => true, 'view' => true]]);
        $this->field('sp_name_id_format', ['visible' => ['add' => 'true', 'edit' => true, 'view' => true]]);
        $this->field('sp_private_key', ['visible' => ['add' => 'true', 'edit' => true, 'view' => true]]);
        $this->field('sp_metadata', ['type' => 'hidden', 'visible' => ['add' => 'true', 'edit' => true, 'view' => true]]);


        $this->field('mapped_username', ['after' => 'sp_metadata']);
        $this->field('allow_create_user', ['after' => 'mapped_username', 'type' => 'select', 'options' => $this->getSelectOptions('general.yesno')]);
        $this->field('mapped_first_name', ['after' => 'allow_create_user']);
        $this->field('mapped_last_name', ['after' => 'mapped_first_name']);
        $this->field('mapped_date_of_birth', ['after' => 'mapped_last_name']);
        $this->field('mapped_gender', ['after' => 'mapped_mapped_date_of_birth']);
        $this->field('mapped_role', ['after' => 'mapped_mapped_gender']);
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['sp_metadata']['type'] = 'text';
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

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($entity->errors('code')) {
            $code = uniqid('IDP');
            $this->request->data[$this->alias()]['code'] = $code;
            $entity->invalid('code', $code, true);
            $entity->errors('redirect_uri', $entity->errors('code'), true);
        }
        $url = Router::url(['plugin' => null, 'controller' => null, 'action' => 'index'], true);
        $this->fields['sp_entity_id']['value'] = $url;
        $this->fields['sp_entity_id']['attr']['value'] = $url;

        $loginUrl = Router::url(['plugin' => 'User', 'controller' => 'Users', 'action' => 'postLogin', 'Saml', $this->request->data[$this->alias()]['code']], true);
        $this->fields['sp_acs']['value'] = $loginUrl;
        $this->fields['sp_acs']['attr']['value'] = $loginUrl;

        $logoutUrl = Router::url(['plugin' => 'User', 'controller' => 'Users', 'action' => 'logout'], true);
        $this->fields['sp_slo']['value'] = $logoutUrl;
        $this->fields['sp_slo']['attr']['value'] = $logoutUrl;
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->fields['idp_entity_id']['attr']['value'] = $entity->saml->idp_entity_id;
        $this->fields['idp_sso']['attr']['value'] = $entity->saml->idp_sso;
        $this->fields['idp_sso_binding']['attr']['value'] = $entity->saml->idp_sso_binding;
        $this->fields['idp_slo']['attr']['value'] = $entity->saml->idp_slo;
        $this->fields['idp_slo_binding']['attr']['value'] = $entity->saml->idp_slo_binding;
        $this->fields['idp_x509cert']['attr']['value'] = $entity->saml->idp_x509cert;
        $this->fields['idp_cert_fingerprint']['attr']['value'] = $entity->saml->idp_cert_fingerprint;
        $this->fields['idp_cert_fingerprint_algorithm']['attr']['value'] = $entity->saml->idp_cert_fingerprint_algorithm;
        $this->fields['sp_entity_id']['attr']['value'] = $entity->saml->sp_entity_id;
        $this->fields['sp_acs']['attr']['value'] = $entity->saml->sp_acs;
        $this->fields['sp_slo']['attr']['value'] = $entity->saml->sp_slo;
        $this->fields['sp_name_id_format']['attr']['value'] = $entity->saml->sp_name_id_format;
        $this->fields['sp_private_key']['attr']['value'] = $entity->saml->sp_private_key;
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain(['Saml']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain(['Saml']);
    }

    public function checkController()
    {
        $typeValue = $this->request->query['type_value'];
        $typeValue = Inflector::camelize($typeValue, ' ');
        $url = $this->url('index');
        unset($url['authentication_type']);
        $action = $this->request->params['action'];
        if (method_exists($this->controller, $typeValue) && $action != $typeValue && $typeValue != 'Authentication') {
            $url['action'] = $typeValue;
            $this->controller->redirect($url);
        } elseif ($action != $typeValue && $action != 'index' && $typeValue != 'Authentication') {
            $this->controller->redirect([
                'plugin' => 'Configuration',
                'controller' => 'Configurations',
                'action' => 'index',
                'type' => $this->selectedType]);
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->checkController();
    }
}
