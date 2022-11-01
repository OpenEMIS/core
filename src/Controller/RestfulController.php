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
use Cake\Network\Request;
use Cake\Core\Configure;
use Firebase\JWT\JWT;
use Restful\Controller\RestfulController as BaseController;
use Page\Traits\EncodingTrait;

class RestfulController extends BaseController
{
    use EncodingTrait;

    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('Csrf');
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

        // do not load localization component if connecting from external system
        if (!$this->request->header('authorization')) {
            $this->loadComponent('Localization.Localization', [
                'productName' => 'OpenEMIS Core'
            ]);
        }
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);

        $isBearer = false;
        $queryDatasource = true;
        $authorisationHeader = $this->request->header('authorization');
        $token = '';
        if ($authorisationHeader) {
            $token = str_ireplace('Bearer ', '', $authorisationHeader);

            $tks = explode('.', $token);
            if (count($tks) == 3) {
                list($headb64, $bodyb64, $cryptob64) = $tks;
                $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($bodyb64));
                if (property_exists($payload, 'iss')) {
                    $queryDatasource = false;
                    $this->Auth->config('storage', 'Memory');
                }
                if (property_exists($payload, 'scope')) {
                    $this->controllerAction = $payload->scope;
                }
                
                $isBearer = true;
            }
        }

        $this->Auth->config('authenticate', [
            'ADmad/JwtAuth.Jwt' => [
                'parameter' => 'token',
                'userModel' => 'User.Users',
                'scope' => ['Users.status' => 1],
                'fields' => [
                    'username' => 'id'
                ],
                'allowedAlgs' => ['RS256'],
                'key' => Configure::read('Application.public.key'),
                'queryDatasource' => $queryDatasource
            ]
        ]);

        if (!empty($token) && true === $isBearer) {
            $this->eventManager()->off($this->Csrf);
        }

        if ($this->request->is(['put', 'post', 'delete', 'patch']) || !empty($this->request->data)) {
            $token = isset($this->request->cookies['csrfToken']) ? $this->request->cookies['csrfToken'] : '';
            $this->request->env('HTTP_X_CSRF_TOKEN', $token);
        }
    }

    public function image($id, $fileNameField, $fileContentField)
    {
        $tempId = $this->decode($id);
        if (is_null($tempId)) {
            $tempId = $id;
        }
        return parent::image($tempId, $fileNameField, $fileContentField);
    }

    public function download($id, $fileNameField, $fileContentField)
    {
        $tempId = $this->decode($id);
        if (is_null($tempId)) {
            $tempId = $id;
        }
        return parent::download($tempId, $fileNameField, $fileContentField);
    }
}
