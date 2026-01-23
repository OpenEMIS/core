<?php
namespace Configuration\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\Routing\Router;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;

class GoogleAuthenticationBehavior extends Behavior
{
    private $model;

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->model = $this->_table;
    }

    public function implementedEvents() : array
    {
        $events = [
            'ControllerAction.Model.beforeAction' => 'beforeAction',
            'ControllerAction.Model.view.afterAction' => 'viewAfterAction',
            'ControllerAction.Model.edit.afterAction' => 'editAfterAction',
            'ControllerAction.Model.addEdit.afterAction' => 'addEditAfterAction',
        ];
        return $events;
    }

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $entity->getErrors($entity->getErrors('google'), null, true);
        $requestData = $this->_table->request->getData()[$this->_table->getAlias()];
        $this->model->field('client_id', ['attr' => ['required' => true, 'label' => __('Client ID')]]);
        $this->model->field('client_secret', ['attr' => ['required' => true]]);
        $this->model->field('redirect_uri', ['type' => 'readonly', 'attr' => ['required' => true]]);
        $this->model->field('hd', ['attr' => ['label' => __('Hosted Domain')]]);
        $this->model->fields['code']['value'] = $entity->code = $requestData['code'];
        if ($entity->getErrors('code')) {
            $code = uniqid('IDP');
           // $this->model->request->getData()[$this->getAlias()]['code'] = $code;
           // $entity->invalid('code', $code, true);
            $entity->getErrors('redirect_uri', $entity->getErrors('code'), true);
        }
        $url = Router::url(['plugin' => 'User', 'controller' => 'Users', 'action' => 'postLogin', 'Google', $this->model->request->getData()[$this->model->getAlias()]['code']], true);
        if (strpos($url, 'https://') !== 0) { //POCOR-8810
            $url = 'https://' . preg_replace('/^http:\/\//', '', $url);
        }

        $this->model->fields['redirect_uri']['value'] = $url;
        $this->model->fields['redirect_uri']['attr']['value'] = $url;

        $this->model->setFieldOrder(['name', 'authentication_type_id', 'status', 'client_id', 'client_secret', 'redirect_uri', 'hd', 'allow_create_user']);
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->model->field('client_id', ['attr' => ['required' => true, 'label' => __('Client ID')]]);
        $this->model->field('client_secret', ['attr' => ['required' => true]]);
        $this->model->field('redirect_uri', ['type' => 'readonly', 'attr' => ['required' => true]]);
        $this->model->field('hd', ['attr' => ['label' => __('Hosted Domain')]]);
        $this->model->setFieldOrder(['name', 'authentication_type_id', 'status', 'client_id', 'client_secret', 'redirect_uri', 'hd', 'allow_create_user']);
    }

    public function editAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->model->fields['name']['type'] = 'readonly';
        $this->model->fields['redirect_uri']['value'] = $entity->google->redirect_uri;
        $this->model->fields['redirect_uri']['attr']['value'] = $entity->google->redirect_uri;
        $this->model->fields['client_id']['attr']['value'] = $entity->google->client_id;
        $this->model->fields['client_secret']['attr']['value'] = $entity->google->client_secret;
        $this->model->fields['hd']['attr']['value'] = $entity->google->hd;
    }

    public function onGetClientId(EventInterface $event, Entity $entity)
    {
        return $entity->google->client_id;
    }

    public function onGetClientSecret(EventInterface $event, Entity $entity)
    {
        return $entity->google->client_secret;
    }

    public function onGetRedirectUri(EventInterface $event, Entity $entity)
    {
        return $entity->google->redirect_uri;
    }

    public function onGetHd(EventInterface $event, Entity $entity)
    {
        return $entity->google->hd;
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra = null)
    {

    }
}
