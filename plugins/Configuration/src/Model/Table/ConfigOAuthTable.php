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
use Cake\ORM\TableRegistry;

class ConfigOAuthTable extends ControllerActionTable
{
    use OptionsTrait;

    public function initialize(array $config)
    {
        $this->table('system_authentications');
        parent::initialize($config);
        $this->hasOne('OAuth', ['className' => 'SSO.IdpOAuth', 'foreignKey' => 'system_authentication_id', 'joinType' => 'INNER', 'dependent' => true]);
        $this->belongsTo('AuthenticationTypes', ['className' => 'SSO.AuthenticationTypes', 'foreignKey' => 'authentication_type_id', 'joinType' => 'INNER']);
        $this->addBehavior('Configuration.Authentication');
    }

    public function validationDefault(Validator $validator)
    {
        return $validator
            ->requirePresence('client_id')
            ->notEmpty('client_id')
            ->requirePresence('client_secret')
            ->notEmpty('client_secret')
            ->requirePresence('redirect_uri')
            ->notEmpty('redirect_uri')
            ->requirePresence('authorization_endpoint')
            ->notEmpty('authorization_endpoint')
            ->requirePresence('token_endpoint')
            ->notEmpty('token_endpoint')
            ->requirePresence('userinfo_endpoint')
            ->notEmpty('userinfo_endpoint')
            ->requirePresence('issuer')
            ->notEmpty('issuer')
            ->requirePresence('jwks_uri')
            ->notEmpty('jwks_uri')
            ->requirePresence('name')
            ->notEmpty('name')
            ->requirePresence('status')
            ->notEmpty('status')
            ->requirePresence('code', 'create')
            ->notEmpty('code', [], 'create')
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
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {

        $data['o_auth'] = [
            'client_id' => $data['client_id'],
            'client_secret' => $data['client_secret'],
            'redirect_uri' => $data['redirect_uri'],
            'well_known_uri' => $data['well_known_uri'],
            'authorization_endpoint' => $data['authorization_endpoint'],
            'token_endpoint' => $data['token_endpoint'],
            'userinfo_endpoint' => $data['userinfo_endpoint'],
            'issuer' => $data['issuer'],
            'jwks_uri' => $data['jwks_uri']
        ];
    }

    public function addOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->request->data[$this->alias()]['code'] = uniqid('IDP');
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $extra['elements']['controls'] = $this->buildSystemConfigFilters();
        $this->checkController();
        $extra['config']['selectedLink'] = ['controller' => 'Configurations', 'action' => 'index'];
        $authenticationTypeId = $this->AuthenticationTypes->getId('OAuth');
        $this->field('name');
        $this->field('code', ['type' => 'hidden']);
        $this->field('authentication_type_id', ['type' => 'hidden', 'value' => $authenticationTypeId]);
        $this->field('status', ['type' => 'select', 'options' => $this->getSelectOptions('general.active')]);

        $this->field('client_id', ['visible' => ['add' => 'true', 'edit' => true, 'view' => true]]);
        $this->field('client_secret', ['visible' => ['add' => 'true', 'edit' => true, 'view' => true]]);
        $this->field('redirect_uri', ['type' => 'readonly', 'visible' => ['add' => 'true', 'edit' => true, 'view' => true]]);
        $this->field('well_known_uri', ['visible' => ['add' => 'true', 'edit' => true, 'view' => true]]);
        $this->field('authorization_endpoint', ['visible' => ['add' => 'true', 'edit' => true, 'view' => true]]);
        $this->field('token_endpoint', ['visible' => ['add' => 'true', 'edit' => true, 'view' => true]]);
        $this->field('userinfo_endpoint', ['visible' => ['add' => 'true', 'edit' => true, 'view' => true]]);
        $this->field('issuer', ['visible' => ['add' => 'true', 'edit' => true, 'view' => true]]);
        $this->field('jwks_uri', ['visible' => ['add' => 'true', 'edit' => true, 'view' => true]]);

        $this->field('mapped_username', ['after' => 'jwks_uri', 'visible' => ['add' => 'true', 'edit' => true, 'view' => true]]);
        $this->field('allow_create_user', ['after' => 'mapped_username', 'type' => 'select', 'options' => $this->getSelectOptions('general.yesno')]);
        $this->field('mapped_first_name', ['after' => 'allow_create_user', 'visible' => ['add' => 'true', 'edit' => true, 'view' => true]]);
        $this->field('mapped_last_name', ['after' => 'mapped_first_name', 'visible' => ['add' => 'true', 'edit' => true, 'view' => true]]);
        $this->field('mapped_date_of_birth', ['after' => 'mapped_last_name', 'visible' => ['add' => 'true', 'edit' => true, 'view' => true]]);
        $this->field('mapped_gender', ['after' => 'mapped_mapped_date_of_birth', 'visible' => ['add' => 'true', 'edit' => true, 'view' => true]]);
        $this->field('mapped_role', ['after' => 'mapped_mapped_gender', 'visible' => ['add' => 'true', 'edit' => true, 'view' => true]]);
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['sp_metadata']['type'] = 'text';
    }

    public function onGetClientId(Event $event, Entity $entity)
    {
        return $entity->o_auth->client_id;
    }

    public function onGetClientSecret(Event $event, Entity $entity)
    {
        return $entity->o_auth->client_secret;
    }

    public function onGetRedirectUri(Event $event, Entity $entity)
    {
        return $entity->o_auth->redirect_uri;
    }

    public function onGetAuthorizationEndpoint(Event $event, Entity $entity)
    {
        return $entity->o_auth->authorization_endpoint;
    }

    public function onGetTokenEndpoint(Event $event, Entity $entity)
    {
        return $entity->o_auth->token_endpoint;
    }

    public function onGetUserinfoEndpoint(Event $event, Entity $entity)
    {
        return $entity->o_auth->userinfo_endpoint;
    }

    public function onGetIssuer(Event $event, Entity $entity)
    {
        return $entity->o_auth->issuer;
    }

    public function onGetJwksUri(Event $event, Entity $entity)
    {
        return $entity->o_auth->jwks_uri;
    }

    public function onGetWellKnownUri(Event $event, Entity $entity)
    {
        return $entity->o_auth->well_known_uri;
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($entity->errors('code')) {
            $code = uniqid('IDP');
            $this->request->data[$this->alias()]['code'] = $code;
            $entity->invalid('code', $code, true);
            $entity->errors('redirect_uri', $entity->errors('code'), true);
        }

        $loginUrl = Router::url(['plugin' => 'User', 'controller' => 'Users', 'action' => 'postLogin', 'OAuth', $this->request->data[$this->alias()]['code']], true);
        $this->fields['redirect_uri']['value'] = $loginUrl;
        $this->fields['redirect_uri']['attr']['value'] = $loginUrl;
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->fields['name']['type'] = 'readonly';
        $this->fields['client_id']['attr']['value'] = $entity->o_auth->client_id;
        $this->fields['client_secret']['attr']['value'] = $entity->o_auth->client_secret;
        $this->fields['redirect_uri']['attr']['value'] = $entity->o_auth->redirect_uri;
        $this->fields['redirect_uri']['value'] = $entity->o_auth->redirect_uri;
        $this->fields['well_known_uri']['attr']['value'] = $entity->o_auth->well_known_uri;
        $this->fields['authorization_endpoint']['attr']['value'] = $entity->o_auth->authorization_endpoint;
        $this->fields['token_endpoint']['attr']['value'] = $entity->o_auth->token_endpoint;
        $this->fields['userinfo_endpoint']['attr']['value'] = $entity->o_auth->userinfo_endpoint;
        $this->fields['issuer']['attr']['value'] = $entity->o_auth->issuer;
        $this->fields['jwks_uri']['attr']['value'] = $entity->o_auth->jwks_uri;
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain(['OAuth']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain(['OAuth']);
    }
}
