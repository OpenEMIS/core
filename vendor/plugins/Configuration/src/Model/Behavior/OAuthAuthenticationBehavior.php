<?php
namespace Configuration\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;

class OAuthAuthenticationBehavior extends Behavior
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
            'ControllerAction.Model.addEdit.afterAction' => 'addEditAfterAction'
        ];
        return $events;
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $entity->errors($entity->errors('o_auth'), null, true);
        $this->model->field('client_id', ['attr' => ['required' => true, 'label' => __('Client ID')]]);
        $this->model->field('client_secret', ['attr' => ['required' => true]]);
        $this->model->field('redirect_uri', ['attr' => ['required' => true], 'type' => 'readonly']);
        $this->model->field('well_known_uri', ['attr' => ['label' => __('Well-known Uri'), 'onblur' => 'Authentication.populate(this.value);']]);
        $this->model->field('authorization_endpoint', ['attr' => ['required' => true]]);
        $this->model->field('token_endpoint', ['attr' => ['required' => true]]);
        $this->model->field('userinfo_endpoint', ['attr' => ['required' => true, 'label' => __('User Information Endpoint')]]);
        $this->model->field('issuer', ['attr' => ['required' => true]]);
        $this->model->field('jwks_uri', ['attr' => ['required' => true, 'label' => __('JSON Web Token Keys Uri')]]);
        if ($entity->errors('code')) {
            $code = uniqid('IDP');
            $this->model->request->data[$this->alias()]['code'] = $code;
            $entity->invalid('code', $code, true);
            $entity->errors('redirect_uri', $entity->errors('code'), true);
        }
        $url = Router::url(['plugin' => 'User', 'controller' => 'Users', 'action' => 'postLogin', 'OAuth', $this->model->request->data[$this->model->alias()]['code']], true);
        $this->model->fields['redirect_uri']['value'] = $url;
        $this->model->fields['redirect_uri']['attr']['value'] = $url;

        $this->model->fields['mapped_username']['type'] = 'string';
        $this->model->fields['mapped_first_name']['type'] = 'string';
        $this->model->fields['mapped_last_name']['type'] = 'string';
        $this->model->fields['mapped_date_of_birth']['type'] = 'string';
        $this->model->fields['mapped_gender']['type'] = 'string';
        $this->model->fields['mapped_role']['type'] = 'string';
        $this->model->fields['mapped_email']['type'] = 'string';

        $this->model->setFieldOrder(['name', 'authentication_type_id', 'status', 'client_id', 'client_secret', 'redirect_uri', 'well_known_uri', 'authorization_endpoint', 'token_endpoint', 'userinfo_endpoint', 'issuer', 'jwks_uri', 'allow_create_user', 'mapped_username', 'mapped_first_name', 'mapped_last_name', 'mapped_date_of_birth', 'mapped_gender', 'mapped_role', 'mapped_email']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->model->field('client_id', ['attr' => ['label' => __('Client ID')]]);
        $this->model->field('client_secret');
        $this->model->field('redirect_uri');
        $this->model->field('well_known_uri', ['attr' => ['label' => __('Well-Known Uri')]]);
        $this->model->field('authorization_endpoint');
        $this->model->field('token_endpoint');
        $this->model->field('userinfo_endpoint', ['attr' => ['label' => __('User Information Endpoint')]]);
        $this->model->field('issuer');
        $this->model->field('jwks_uri', ['attr' => ['label' => __('JSON Web Token Keys Uri')]]);
        $this->model->fields['mapped_username']['type'] = 'string';
        $this->model->fields['mapped_first_name']['type'] = 'string';
        $this->model->fields['mapped_last_name']['type'] = 'string';
        $this->model->fields['mapped_date_of_birth']['type'] = 'string';
        $this->model->fields['mapped_gender']['type'] = 'string';
        $this->model->fields['mapped_role']['type'] = 'string';
        $this->model->fields['mapped_email']['type'] = 'string';
        $this->model->setFieldOrder(['name', 'authentication_type_id', 'status', 'client_id', 'client_secret', 'redirect_uri', 'well_known_uri', 'authorization_endpoint', 'token_endpoint', 'userinfo_endpoint', 'issuer', 'jwks_uri', 'allow_create_user', 'mapped_username', 'mapped_first_name', 'mapped_last_name', 'mapped_date_of_birth', 'mapped_gender', 'mapped_role', 'mapped_email']);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->model->fields['name']['type'] = 'readonly';
        $this->model->fields['client_id']['attr']['value'] = $entity->o_auth->client_id;
        $this->model->fields['client_secret']['attr']['value'] = $entity->o_auth->client_secret;
        $this->model->fields['redirect_uri']['attr']['value'] = $entity->o_auth->redirect_uri;
        $this->model->fields['redirect_uri']['value'] = $entity->o_auth->redirect_uri;
        $this->model->fields['well_known_uri']['attr']['value'] = $entity->o_auth->well_known_uri;
        $this->model->fields['authorization_endpoint']['attr']['value'] = $entity->o_auth->authorization_endpoint;
        $this->model->fields['token_endpoint']['attr']['value'] = $entity->o_auth->token_endpoint;
        $this->model->fields['userinfo_endpoint']['attr']['value'] = $entity->o_auth->userinfo_endpoint;
        $this->model->fields['issuer']['attr']['value'] = $entity->o_auth->issuer;
        $this->model->fields['jwks_uri']['attr']['value'] = $entity->o_auth->jwks_uri;
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
}
