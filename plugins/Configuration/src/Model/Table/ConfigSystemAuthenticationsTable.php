<?php
namespace Configuration\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Routing\Router;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use OneLogin_Saml2_Error;
use OneLogin_Saml2_Settings;
use OneLogin\Saml2\Settings;
use OneLogin\Saml2\Error;
use Cake\Http\ServerRequest;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class ConfigSystemAuthenticationsTable extends ControllerActionTable
{
    use OptionsTrait;

    private $authenticationTypeOptions;

    public function initialize(array $config) : void
    {
        $this->setTable('system_authentications');
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

    public function validationDefault(Validator $validator) : Validator
    {
        $validator->setProvider('custom', $this);
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
            ]);
            //Start POCOR-6697
            // ->add('mapped_first_name', 'ruleMaxLength', [
            //     'rule' => ['maxLength', 100]
            // ])
            // ->add('mapped_last_name', 'ruleMaxLength', [
            //     'rule' => ['maxLength', 100]
            // ]);
            //End POCOR-6697
    }

    public function addEditBeforePatch(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $authenticationTypeId = $data[$this->getAlias()]['authentication_type_id'];
        $idpType = isset($this->authenticationTypeOptions[$authenticationTypeId]) ? $this->authenticationTypeOptions[$authenticationTypeId] : '';
        switch ($idpType) {
            case 'google':
                $data[$this->getAlias()]['google'] = [
                    'client_id' => $data[$this->getAlias()]['client_id'],
                    'client_secret' => $data[$this->getAlias()]['client_secret'],
                    'redirect_uri' => $data[$this->getAlias()]['redirect_uri'],
                    'hd' => $data[$this->getAlias()]['hd']
                ];
                $data['mapped_username'] = 'email';
                break;

            case 'saml':
                $setting['sp'] = [
                    'entityId' => $data[$this->getAlias()]['sp_entity_id'],
                    'assertionConsumerService' => [
                        'url' => $data[$this->getAlias()]['sp_acs'],
                    ],
                    'singleLogoutService' => [
                        'url' => $data[$this->getAlias()]['sp_slo'],
                    ],
                    'NameIDFormat' => $data[$this->getAlias()]['sp_name_id_format'],
                ];

                $data[$this->getAlias()]['sp_metadata'] = htmlentities($this->getSPMetaData($setting));

                $data[$this->getAlias()]['saml'] = [
                    'idp_entity_id' => $data[$this->getAlias()]['idp_entity_id'],
                    'idp_sso' => $data[$this->getAlias()]['idp_sso'],
                    'idp_sso_binding' => $data[$this->getAlias()]['idp_sso_binding'],
                    'idp_slo' => $data[$this->getAlias()]['idp_slo'],
                    'idp_slo_binding' => $data[$this->getAlias()]['idp_slo_binding'],
                    'idp_x509cert' => $data[$this->getAlias()]['idp_x509cert'],
                    'idp_cert_fingerprint' => $data[$this->getAlias()]['idp_cert_fingerprint'],
                    'idp_cert_fingerprint_algorithm' => $data[$this->getAlias()]['idp_cert_fingerprint_algorithm'],
                    'sp_entity_id' => $data[$this->getAlias()]['sp_entity_id'],
                    'sp_acs' => $data[$this->getAlias()]['sp_acs'],
                    'sp_slo' => $data[$this->getAlias()]['sp_slo'],
                    'sp_name_id_format' => $data[$this->getAlias()]['sp_name_id_format'],
                    'sp_private_key' => $data[$this->getAlias()]['sp_private_key'],
                    'sp_metadata' => $data[$this->getAlias()]['sp_metadata']
                ];
                break;

            case 'o_auth':
                $data[$this->getAlias()]['o_auth'] = [
                    'client_id' => $data[$this->getAlias()]['client_id'],
                    'client_secret' => $data[$this->getAlias()]['client_secret'],
                    'redirect_uri' => $data[$this->getAlias()]['redirect_uri'],
                    'well_known_uri' => $data[$this->getAlias()]['well_known_uri'],
                    'authorization_endpoint' => $data[$this->getAlias()]['authorization_endpoint'],
                    'token_endpoint' => $data[$this->getAlias()]['token_endpoint'],
                    'userinfo_endpoint' => $data[$this->getAlias()]['userinfo_endpoint'],
                    'issuer' => $data[$this->getAlias()]['issuer'],
                    'jwks_uri' => $data[$this->getAlias()]['jwks_uri']
                ];
                break;
        }
    }

    private function getSPMetaData($settingsInfo)
    {
        try {
            // Now we only validate SP settings
            $settings = new Settings($settingsInfo, true);
            $metadata = $settings->getSPMetadata();
            $errors = $settings->validateMetadata($metadata);
            if (empty($errors)) {
                header('Content-Type: text/xml');
                return $metadata;
            } else {
                throw new Error(
                    'Invalid SP metadata: '.implode(', ', $errors),
                    Error::METADATA_SP_INVALID
                );
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function viewEditBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain(['Google', 'Saml', 'OAuth']);
       // $this->request->getdata()[$this->getAlias()]['authentication_type_id'] = $query->first()->authentication_type_id;
        $data = $this->request->getData();

        // Modify the data as needed
        $data[$this->getAlias()]['authentication_type_id'] = $query->first()->authentication_type_id;
        $this->request = $this->request->withData($this->getAlias(), $data[$this->getAlias()]);
        $this->field('authentication_type_id');
    }

    public function addOnInitialize(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $data = $this->request->getData();
        $data[$this->getAlias()]['code'] = uniqid('IDP');
        $this->request = $this->request->withData($this->getAlias(), $data[$this->getAlias()]);
    }

    public function editOnInitialize(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $data = $this->request->getData(); 
        $data[$this->getAlias()]['code'] = $entity->code;
        $this->request = $this->request->withData($this->getAlias(), $data[$this->getAlias()]);
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
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

    public function onUpdateFieldAuthenticationTypeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $request = $this->request;
        $attr['onChangeReload'] = true;
        if (isset($request->getData()[$this->getAlias()]['authentication_type_id'])) {
            $authenticationTypeId = $request->getData()[$this->getAlias()]['authentication_type_id'];
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

    public function onBeforeDelete(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $count = $this
            ->find()
            ->where([
                $this->aliasField('status') => 1
            ])
            ->count();

        $enableLocalLogin = TableRegistry::getTableLocator()->get('Configuration.ConfigItems')->value('enable_local_login');

        if (!$enableLocalLogin && $count == 1 && $entity->status == 1) {
            $event->stopPropagation();
            $this->Alert->error('Configuration.ConfigSystemAuthentications.removeActive', ['reset' => true]);
            return false;
        }
    }
}
