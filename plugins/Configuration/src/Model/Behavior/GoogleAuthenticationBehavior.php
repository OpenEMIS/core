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
use Cake\Utility\Inflector;

class GoogleAuthenticationBehavior extends Behavior
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
        $entity->errors($entity->errors('google'), null, true);
        $this->model->field('client_id', ['attr' => ['required' => true, 'label' => __('Client ID')]]);
        $this->model->field('client_secret', ['attr' => ['required' => true]]);
        $this->model->field('redirect_uri', ['type' => 'readonly', 'attr' => ['required' => true]]);
        $this->model->field('hd', ['attr' => ['label' => __('Hosted Domain')]]);
        if ($entity->errors('code')) {
            $code = uniqid('IDP');
            $this->model->request->data[$this->alias()]['code'] = $code;
            $entity->invalid('code', $code, true);
            $entity->errors('redirect_uri', $entity->errors('code'), true);
        }
        $url = Router::url(['plugin' => 'User', 'controller' => 'Users', 'action' => 'postLogin', 'Google', $this->model->request->data[$this->model->alias()]['code']], true);
        $this->model->fields['redirect_uri']['value'] = $url;
        $this->model->fields['redirect_uri']['attr']['value'] = $url;

        $this->model->setFieldOrder(['name', 'authentication_type_id', 'status', 'client_id', 'client_secret', 'redirect_uri', 'hd', 'allow_create_user']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->model->field('client_id', ['attr' => ['required' => true, 'label' => __('Client ID')]]);
        $this->model->field('client_secret', ['attr' => ['required' => true]]);
        $this->model->field('redirect_uri', ['type' => 'readonly', 'attr' => ['required' => true]]);
        $this->model->field('hd', ['attr' => ['label' => __('Hosted Domain')]]);
        $this->model->setFieldOrder(['name', 'authentication_type_id', 'status', 'client_id', 'client_secret', 'redirect_uri', 'hd', 'allow_create_user']);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->model->fields['name']['type'] = 'readonly';
        $this->model->fields['redirect_uri']['value'] = $entity->google->redirect_uri;
        $this->model->fields['redirect_uri']['attr']['value'] = $entity->google->redirect_uri;
        $this->model->fields['client_id']['attr']['value'] = $entity->google->client_id;
        $this->model->fields['client_secret']['attr']['value'] = $entity->google->client_secret;
        $this->model->fields['hd']['attr']['value'] = $entity->google->hd;
    }

    public function onGetClientId(Event $event, Entity $entity)
    {
        return $entity->google->client_id;
    }

    public function onGetClientSecret(Event $event, Entity $entity)
    {
        return $entity->google->client_secret;
    }

    public function onGetRedirectUri(Event $event, Entity $entity)
    {
        return $entity->google->redirect_uri;
    }

    public function onGetHd(Event $event, Entity $entity)
    {
        return $entity->google->hd;
    }
}
