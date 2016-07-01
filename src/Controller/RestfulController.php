<?php
namespace App\Controller;

use Exception;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;
use Restful\Controller\RestfulController as BaseController;

class RestfulController extends BaseController
{
    public function initialize($config) {
        parent::initialize($config);
        $this->Auth->config('authenticate', [
            'Form' => [
                'userModel' => 'User.Users',
                'passwordHasher' => [
                    'className' => 'Fallback',
                    'hashers' => ['Default', 'Legacy']
                ]
            ]
        ]);
        $this->Auth->config('loginAction', [
            'plugin' => 'User',
            'controller' => 'Users',
            'action' => 'login'
        ]);
        $this->Auth->config('logoutRedirect', [
            'plugin' => 'User',
            'controller' => 'Users',
            'action' => 'login'
        ]);
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->Auth->config('authenticate', [
            'ADmad/JwtAuth.Jwt' => [
                'parameter' => 'token',
                'userModel' => 'Users',
                'scope' => ['Users.status' => 1],
                'fields' => [
                    'username' => 'id'
                ],
                'queryDatasource' => true
            ]
        ]);
    }
}
