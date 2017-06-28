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

class ConfigGoogleTable extends ControllerActionTable
{
    use OptionsTrait;

    public function initialize(array $config)
    {
        $this->table('system_authentications');
        parent::initialize($config);
        $this->hasOne('Google', ['className' => 'SSO.IdpGoogle', 'foreignKey' => 'system_authentication_id', 'joinType' => 'INNER', 'dependent' => true]);
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
        $data['google'] = [
            'client_id' => $data['client_id'],
            'client_secret' => $data['client_secret'],
            'redirect_uri' => $data['redirect_uri'],
            'hd' => $data['hd']
        ];
    }

    public function addOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->request->data[$this->alias()]['code'] = uniqid('IDP');
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $extra['config']['selectedLink'] = ['controller' => 'Configurations', 'action' => 'index'];
        $authenticationTypeId = $this->AuthenticationTypes->getId('Google');
        $this->field('name');
        $this->field('code', ['type' => 'hidden']);
        $this->field('authentication_type_id', ['type' => 'hidden', 'value' => $authenticationTypeId]);
        $this->field('status', ['type' => 'select', 'options' => $this->getSelectOptions('general.active')]);
        $this->field('client_id');
        $this->field('client_secret', ['visible' => ['add' => 'true', 'edit' => true, 'view' => true]]);
        $this->field('redirect_uri', ['type' => 'readonly']);
        $this->field('hd');
        $this->field('mapped_username', ['after' => 'hd', 'type' => 'hidden', 'value' => 'email']);
        $this->field('allow_create_user', ['after' => 'mapped_username', 'type' => 'select', 'options' => $this->getSelectOptions('general.yesno')]);
        $this->field('mapped_first_name', ['after' => 'allow_create_user', 'type' => 'hidden']);
        $this->field('mapped_last_name', ['after' => 'mapped_first_name', 'type' => 'hidden']);
        $this->field('mapped_date_of_birth', ['after' => 'mapped_last_name', 'type' => 'hidden']);
        $this->field('mapped_gender', ['after' => 'mapped_mapped_date_of_birth', 'type' => 'hidden']);
        $this->field('mapped_role', ['after' => 'mapped_mapped_gender', 'type' => 'hidden']);
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

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($entity->errors('code')) {
            $code = uniqid('IDP');
            $this->request->data[$this->alias()]['code'] = $code;
            $entity->invalid('code', $code, true);
            $entity->errors('redirect_uri', $entity->errors('code'), true);
        }
        $url = Router::url(['plugin' => 'User', 'controller' => 'Users', 'action' => 'postLogin', 'Google', $this->request->data[$this->alias()]['code']], true);
        $this->fields['redirect_uri']['value'] = $url;
        $this->fields['redirect_uri']['attr']['value'] = $url;
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->fields['redirect_uri']['value'] = $entity->google->redirect_uri;
        $this->fields['redirect_uri']['attr']['value'] = $entity->google->redirect_uri;
        $this->fields['client_id']['attr']['value'] = $entity->google->client_id;
        $this->fields['client_secret']['attr']['value'] = $entity->google->client_secret;
        $this->fields['hd']['attr']['value'] = $entity->google->hd;
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain(['Google']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain(['Google']);
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
