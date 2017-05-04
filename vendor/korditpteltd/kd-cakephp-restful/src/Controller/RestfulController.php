<?php
namespace Restful\Controller;

use Exception;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;
use Cake\Utility\Inflector;
use Restful\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Core\Configure;

class RestfulController extends AppController
{
    private $_debug = false;
    private $model = null;
    protected $controllerAction = null;
    private $restfulComponent = null;
    private $supportedRestful = [
        'v1' => 'v1',
        'v2' => 'v2'
    ];
    private $user = null;

    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('RequestHandler');
        $this->loadComponent('Auth', [
            'authorize' => 'Controller',
            'unauthorizedRedirect' => false
        ]);
        $this->Auth->allow('token');
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);

        if (empty($this->request->params['_ext'])) {
            $this->request->params['_ext'] = 'json';
        }
        $this->controllerAction = $this->request->header('controlleraction');
        if (isset($this->request->model)) {
            $tableAlias = $this->request->model;
            $model = $this->_instantiateModel($tableAlias);
            if ($model != false) {
                $this->model = $model;

                // Event to get allowed action and allowed table to be accessible via restful
                $event = $model->dispatchEvent('Restful.Model.onGetAllowedActions', null, $this);
                if (is_array($event->result)) {
                    $this->Auth->allow($event->result);
                }
            }
        }

        if (isset($this->request->version)) {
            $version = $this->request->version;
            if ($version == 'latest') {
                $version = array_pop($this->supportedRestful);
            } else {
                if (isset($this->supportedRestful[$version])) {
                    $version = $this->supportedRestful[$version];
                } else {
                    $version = array_shift($this->supportedRestful);
                }
            }
        } else {
            $version = array_shift($this->supportedRestful);
        }

        $componentName = 'Restful'. ucfirst($version);
        $this->loadComponent('Restful.'.$componentName, ['model' => $this->model]);
        $this->restfulComponent = $this->{$componentName};
    }

    public function isAuthorized($user = null)
    {
        $this->user = $user;
        $model = $this->model;
        $scope = $this->controllerAction;
        $action = $this->request->params['action'];
        $request = $this->request;
        $extra = new ArrayObject(['request' => $request]);
        if ($action == 'translate') {
            return true;
        }
        $event = $model->dispatchEvent('Restful.Model.isAuthorized', [$scope, $action, $extra], $this);
        if ($event->result) {
            return $event->result;
        }
        return false;
    }

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function beforeRender(Event $event)
    {
        parent::beforeRender($event);
        if ($this->_debug) {
            $_serialize = array_merge(['request_method', 'action'], $this->viewVars['_serialize']);
            $this->set([
                'request_method' => $this->request->method(),
                'action' => $this->request->params['action'],
                '_serialize' => $_serialize
            ]);
        }
    }

    public function token()
    {
        $this->restfulComponent->token();
    }

    public function nothing()
    {
        $this->restfulComponent->nothing();
    }

    public function options()
    {
        $this->autoRender = false;
        $this->restfulComponent->options();
    }

    public function index()
    {
        $this->restfulComponent->index();
    }

    public function add()
    {
        $this->restfulComponent->add();
    }

    public function view($id)
    {
        $this->restfulComponent->view($id);
    }

    public function edit()
    {
        $this->restfulComponent->edit();
    }

    public function delete()
    {
        $this->restfulComponent->delete();
    }

    public function translate()
    {
        $original = $this->request->data;
        $translated = $this->request->data;
        $this->restfulComponent->translate($translated);
        $this->set([
            'translated' => $translated,
            'original' => $original,
            '_serialize' => ['translated', 'original']
        ]);
    }

    private function initTable(Table $table, $connectionName = 'default')
    {
        $_connectionName = $this->request->query('_db') ? $this->request->query('_db') : $connectionName;
        $table::setConnectionName($_connectionName);
        return $table;
    }

    private function _instantiateModel($model)
    {
        $model = str_replace('-', '.', $model);
        if (Configure::read('debug')) {
            $_connectionName = $this->request->query('_db') ? $this->request->query('_db') : 'default';
            $target = TableRegistry::get($model, ['connectionName' => $_connectionName]);
        } else {
            $target = TableRegistry::get($model);
        }

        try {
            $data = $target->find('all')->limit('1');
            return $target;
        } catch (Exception $e) {
            $this->_outputError();
            return false;
        }
    }

    private function _outputError($message = 'Requested Plugin-Model does not exists')
    {
        $model = str_replace('-', '.', $this->request->params['model']);
        $this->set([
            'model' => $model,
            'error' => $message,
            '_serialize' => ['request_method', 'action', 'model', 'error']
        ]);
    }
}
