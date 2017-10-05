<?php
namespace Restful\Controller;

use Cake\Core\Configure;
use Cake\Log\Log;

class RestfulController extends AppController
{
    private $restfulComponent = null;
    private $supportedRestful = [
        'v1' => 'v1',
        'v2' => 'v2'
    ];
    private $authorizedUser = null;

    public function initialize()
    {
        parent::initialize();

        $version = $this->getComponentVersion();
        $componentName = 'Restful'. ucfirst($version);
        $this->loadComponent('Restful.' . $componentName);
        $this->loadComponent('RequestHandler');
        $this->loadComponent('Restful.DownloadFile');
        $this->loadComponent('Auth', [
            'authorize' => 'Controller',
            'unauthorizedRedirect' => false
        ]);
        $this->restfulComponent = $this->{$componentName};
        $this->Auth->allow('token');
        $bypassAuth = Configure::read('bypassAuth');
        if ($bypassAuth) {
            $this->Auth->allow();
        }
    }

    private function getComponentVersion()
    {
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
        return $version;
    }

    public function isAuthorized($user = null)
    {
        return $this->restfulComponent->isAuthorized($user);
    }

    public function setAuthorizedUser($user)
    {
        $this->authorizedUser = $user;
    }

    public function getAuthorizedUser()
    {
        return $this->authorizedUser;
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

    public function schema()
    {
        $this->restfulComponent->schema();
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

    public function download($id, $fileNameField, $fileContentField)
    {
        return $this->DownloadFile->download($id, $fileNameField, $fileContentField);
    }

    public function image($id, $fileNameField, $fileContentField)
    {
        $this->DownloadFile->config('base64Encode', true);
        return $this->DownloadFile->download($id, $fileNameField, $fileContentField);
    }

    public function translate()
    {
        $original = $this->request->data;
        $translated = $this->request->data;
        $translateItem = function (&$item, $key) {
            $item = __($item);
        };
        array_walk_recursive($translated, $translateItem);
        $this->set([
            'translated' => $translated,
            'original' => $original,
            '_serialize' => ['translated', 'original']
        ]);
    }
}
