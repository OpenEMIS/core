<?php
namespace Configuration\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Routing\Router;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use OneLogin_Saml2_Error;
use OneLogin_Saml2_Settings;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class ConfigSystemAuthenticationsTable extends ControllerActionTable
{
    use OptionsTrait;

    private $authenticationTypeOptions;

    public function initialize(array $config)
    {
        $this->table('system_authentications');
        parent::initialize($config);
        $this->hasOne('Google', ['className' => 'SSO.IdpGoogle', 'foreignKey' => 'system_authentication_id', 'dependent' => true]);
        $this->hasOne('Saml', ['className' => 'SSO.IdpSaml', 'foreignKey' => 'system_authentication_id', 'dependent' => true]);
        $this->hasOne('OAuth', ['className' => 'SSO.IdpOAuth', 'foreignKey' => 'system_authentication_id', 'dependent' => true]);
        $this->belongsTo('AuthenticationTypes', ['className' => 'SSO.AuthenticationTypes', 'foreignKey' => 'authentication_type_id', 'joinType' => 'INNER']);
        $this->addBehavior('Configuration.Authentication');
        $authenticationTypeOptions = $this->AuthenticationTypes->find('list')->toArray();
        foreach ($authenticationTypeOptions as &$value) {
            $value = Inflector::underscore($value);
        }
        $this->authenticationTypeOptions = $authenticationTypeOptions;
        $this->fields['mapped_username']['length'] = 100;
        $this->fields['mapped_first_name']['length'] = 100;
        $this->fields['mapped_last_name']['length'] = 100;
    }

    public function validationDefault(Validator $validator)
    {
        return $validator
            ->requirePresence('name')
            ->notEmpty('name')
            ->requirePresence('status')
            ->add('status', 'ruleLocalLogin', [
                'rule' => 'checkIDPLogin'
            ])
            ->requirePresence('mapped_username')
            ->notEmpty('mapped_username')
            ->requirePresence('allow_create_user')
            ->notEmpty('allow_create_user')
            ->add('code', 'ruleUnique', [
                'rule' => ['validateUnique'],
                'provider' => 'table'
            ])
            ->add('name', 'ruleUnique', [
                'rule' => ['validateUnique'],
                'provider' => 'table'
            ])
            ->add('mapped_first_name', 'ruleMaxLength', [
                'rule' => ['maxLength', 100]
            ])
            ->add('mapped_last_name', 'ruleMaxLength', [
                'rule' => ['maxLength', 100]
            ]);
    }

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $authenticationTypeId = $data[$this->alias()]['authentication_type_id'];
        $idpType = isset($this->authenticationTypeOptions[$authenticationTypeId]) ? $this->authenticationTypeOptions[$authenticationTypeId] : '';
        switch ($idpType) {
            case 'google':
                $data[$this->alias()]['google'] = [
                    'client_id' => $data[$this->alias()]['client_id'],
                    'client_secret' => $data[$this->alias()]['client_secret'],
                    'redirect_uri' => $data[$this->alias()]['redirect_uri'],
                    'hd' => $data[$this->alias()]['hd']
                ];
                $data['mapped_username'] = 'email';
                break;

            case 'saml':
                $setting['sp'] = [
                    'entityId' => $data[$this->alias()]['sp_entity_id'],
                    'assertionConsumerService' => [
                        'url' => $data[$this->alias()]['sp_acs'],
                    ],
                    'singleLogoutService' => [
                        'url' => $data[$this->alias()]['sp_slo'],
                    ],
                    'NameIDFormat' => $data[$this->alias()]['sp_name_id_format'],
                ];

                $data[$this->alias()]['sp_metadata'] = htmlentities($this->getSPMetaData($setting));

                $data[$this->alias()]['saml'] = [
                    'idp_entity_id' => $data[$this->alias()]['idp_entity_id'],
                    'idp_sso' => $data[$this->alias()]['idp_sso'],
                    'idp_sso_binding' => $data[$this->alias()]['idp_sso_binding'],
                    'idp_slo' => $data[$this->alias()]['idp_slo'],
                    'idp_slo_binding' => $data[$this->alias()]['idp_slo_binding'],
                    'idp_x509cert' => $data[$this->alias()]['idp_x509cert'],
                    'idp_cert_fingerprint' => $data[$this->alias()]['idp_cert_fingerprint'],
                    'idp_cert_fingerprint_algorithm' => $data[$this->alias()]['idp_cert_fingerprint_algorithm'],
                    'sp_entity_id' => $data[$this->alias()]['sp_entity_id'],
                    'sp_acs' => $data[$this->alias()]['sp_acs'],
                    'sp_slo' => $data[$this->alias()]['sp_slo'],
                    'sp_name_id_format' => $data[$this->alias()]['sp_name_id_format'],
                    'sp_private_key' => $data[$this->alias()]['sp_private_key'],
                    'sp_metadata' => $data[$this->alias()]['sp_metadata']
                ];
                break;

            case 'o_auth':
                $data[$this->alias()]['o_auth'] = [
                    'client_id' => $data[$this->alias()]['client_id'],
                    'client_secret' => $data[$this->alias()]['client_secret'],
                    'redirect_uri' => $data[$this->alias()]['redirect_uri'],
                    'well_known_uri' => $data[$this->alias()]['well_known_uri'],
                    'authorization_endpoint' => $data[$this->alias()]['authorization_endpoint'],
                    'token_endpoint' => $data[$this->alias()]['token_endpoint'],
                    'userinfo_endpoint' => $data[$this->alias()]['userinfo_endpoint'],
                    'issuer' => $data[$this->alias()]['issuer'],
                    'jwks_uri' => $data[$this->alias()]['jwks_uri']
                ];
                break;
        }
    }

    private function getSPMetaData($settingsInfo)
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

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain(['Google', 'Saml', 'OAuth']);
        $this->request->data[$this->alias()]['authentication_type_id'] = $query->first()->authentication_type_id;
        $this->field('authentication_type_id');
    }

    public function addOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->request->data[$this->alias()]['code'] = uniqid('IDP');
    }

    public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->request->data[$this->alias()]['code'] = $entity->code;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $extra['elements']['controls'] = $this->buildSystemConfigFilters();
        $extra['config']['selectedLink'] = ['controller' => 'Configurations', 'action' => 'index'];
        $this->field('name');
        $this->field('code', ['type' => 'hidden']);
        $this->field('authentication_type_id', ['type' => 'select', 'options' => $this->AuthenticationTypes->find('list')->toArray()]);
        $this->field('status', ['type' => 'select', 'options' => $this->getSelectOptions('general.active')]);
        $this->field('allow_create_user', ['type' => 'select', 'options' => $this->getSelectOptions('general.yesno')]);
        $this->field('mapped_username', ['type' => 'hidden', 'attr' => ['label' => __('Username Mapping')]]);
        $this->field('mapped_first_name', ['type' => 'hidden', 'attr' => ['label' => __('First Name Mapping')]]);
        $this->field('mapped_last_name', ['type' => 'hidden', 'attr' => ['label' => __('Last Name Mapping')]]);
        $this->field('mapped_date_of_birth', ['type' => 'hidden', 'attr' => ['label' => __('Date Of Birth Mapping')]]);
        $this->field('mapped_gender', ['type' => 'hidden', 'attr' => ['label' => __('Gender Mapping')]]);
        $this->field('mapped_role', ['type' => 'hidden', 'attr' => ['label' => __('Role Mapping')]]);
        $this->field('mapped_email', ['type' => 'hidden', 'attr' => ['label' => __('Email Mapping')]]);
    }

    public function onUpdateFieldAuthenticationTypeId(Event $event, array $attr, $action, Request $request)
    {
        $attr['onChangeReload'] = true;
        if (isset($request->data[$this->alias()]['authentication_type_id'])) {
            $authenticationTypeId = $request->data[$this->alias()]['authentication_type_id'];
            $idpType = isset($this->authenticationTypeOptions[$authenticationTypeId]) ? $this->authenticationTypeOptions[$authenticationTypeId] : '';
            switch ($idpType) {
                case 'google':
                    $this->addBehavior('Configuration.GoogleAuthentication');
                    break;
                case 'saml':
                    $this->addBehavior('Configuration.SamlAuthentication');
                    break;
                case 'o_auth':
                    $this->addBehavior('Configuration.OAuthAuthentication');
                    break;
            }

        }

        if ($action == 'edit') {
            $attr['type'] = 'readonly';
        }
        return $attr;
    }

    public function onBeforeDelete(Event $event, Entity $entity, ArrayObject $extra)
    {
        $count = $this
            ->find()
            ->where([
                $this->aliasField('status') => 1
            ])
            ->count();

        $enableLocalLogin = TableRegistry::get('Configuration.ConfigItems')->value('enable_local_login');

        if (!$enableLocalLogin && $count == 1 && $entity->status == 1) {
            $event->stopPropagation();
            $this->Alert->error('Configuration.ConfigSystemAuthentications.removeActive', ['reset' => true]);
            return false;
        }
    }
}
